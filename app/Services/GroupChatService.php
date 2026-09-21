<?php

namespace App\Services;

use App\Events\NewChatMessage;
use App\Models\ChatMessage;
use App\Models\Groups;
use App\Models\User;
use App\Services\ViolationDetection\FlagHelper;
use App\Services\ViolationDetection\SensitiveModerationService;
use App\Services\ViolationDetection\TextModerationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Gửi tin nhắn vào KHUNG CHAT NHÓM — dùng chung cho 2 luồng:
 *
 *  1) Thành viên / trưởng nhóm gửi tin nhắn thường (type = member):
 *     phải là thành viên, nội dung bị kiểm duyệt theo hướng FLAG-ONLY.
 *  2) Admin gửi thông báo / cảnh báo vào bất kỳ nhóm nào (type = announcement|warning):
 *     KHÔNG cần là thành viên và KHÔNG tạo dòng group_members, nhưng tin nhắn vẫn
 *     nằm trong khung chat nhóm và vẫn cộng badge chưa đọc cho thành viên.
 */
class GroupChatService
{
    /**
     * User này có gửi được tin nhắn thường vào nhóm không (thành viên hoặc trưởng nhóm)?
     */
    public function isMember(Groups $group, User $sender): bool
    {
        if ((int) $group->leader_id === (int) $sender->user_id) {
            return true;
        }

        return $group->members()
            ->where('group_members.user_id', $sender->user_id)
            ->exists();
    }

    /**
     * Gửi 1 tin nhắn vào khung chat nhóm và trả về tin nhắn đã kèm quan hệ 'user'.
     *
     * @param  string  $type  ChatMessage::TYPE_MEMBER | TYPE_ANNOUNCEMENT | TYPE_WARNING
     *
     * @throws AuthorizationException khi thành viên không thuộc nhóm mà gửi tin thường
     */
    public function send(
        Groups $group,
        User $sender,
        ?string $content,
        ?UploadedFile $attachment = null,
        string $type = ChatMessage::TYPE_MEMBER
    ): ChatMessage {
        $isAdminMessage = in_array($type, ChatMessage::ADMIN_TYPES, true);

        // Tin nhắn thường: chỉ thành viên / trưởng nhóm mới được gửi.
        // Thông báo - cảnh báo của admin: bỏ qua kiểm tra thành viên (không tạo
        // group_members để admin không bị tính vào badge "chưa đọc" của nhóm).
        if (!$isAdminMessage && !$this->isMember($group, $sender)) {
            throw new AuthorizationException('Bạn không thể gửi tin nhắn vào nhóm này.');
        }

        $content = $content ?? '';

        $messageData = [
            'group_id' => $group->group_id,
            'user_id'  => $sender->user_id,
            'content'  => $content,
            'type'     => $type,
        ];

        $imageCheck = null;

        // Xử lý upload ảnh đính kèm (gắn cờ ảnh nhạy cảm, không chặn, không xoá file)
        if ($attachment) {
            $path = $attachment->store('chat-attachments', 'public');

            $imageCheck = ImageModerationService::checkStoredImage($path);
            if (!$imageCheck['passed']) {
                Log::warning('Group chat image flagged by Vision (not blocked): ' . ($imageCheck['violations'] ?? 'unknown'));
            }

            $messageData['attachment'] = $path;
        }

        // Flag-only moderation: text (rule-based từ dataset, sau này PhoBERT)
        // + image (Vision) đều CHỈ gắn cờ, KHÔNG chặn.
        // Tin thông báo/cảnh báo của admin là nội dung hệ thống nên KHÔNG gắn cờ
        // (tránh chính tin của admin hiện trong tab "Bị gắn cờ").
        if (!$isAdminMessage) {
            $textCheck = TextModerationService::check($content);
            $sensitiveCheck = SensitiveModerationService::check($content);

            $messageData = array_merge($messageData, FlagHelper::merge($textCheck, $imageCheck, $sensitiveCheck));
        }

        $message = ChatMessage::create($messageData);

        $this->bumpUnreadCounters($group, $sender);
        $this->broadcast($message);

        return $message->load('user');
    }

    /**
     * Badge: tăng số tin nhắn chưa đọc cho mọi thành viên KHÁC (trừ người gửi).
     * Dùng chung cột users.unread_message_count với chat 1-1.
     */
    private function bumpUnreadCounters(Groups $group, User $sender): void
    {
        $recipientIds = $group->members()
            ->pluck('users.user_id')
            ->push($group->leader_id)
            ->unique()
            ->reject(fn ($id) => (int) $id === (int) $sender->user_id);

        if ($recipientIds->isNotEmpty()) {
            User::whereIn('user_id', $recipientIds->all())->increment('unread_message_count');
        }
    }

    /** Broadcast real-time tới nhóm (fail-open: Reverb chết thì bỏ qua). */
    private function broadcast(ChatMessage $message): void
    {
        try {
            broadcast(new NewChatMessage($message));
        } catch (\Throwable $e) {
            Log::warning('Broadcast group message failed (Reverb offline?): ' . $e->getMessage());
        }
    }
}
