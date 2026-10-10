<?php

namespace App\Http\Controllers;

use App\Models\BlockedUser;
use App\Models\User;
use App\Services\LoginHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * L09 (Mức 2) — "Thiết lập tài khoản" chuẩn SaaS: 3 tab mới
 * Bảo mật (login history + phiên + remember token), Hồ sơ (avatar/locale/timezone)
 * và Riêng tư (danh sách chặn + ẩn online + ai được mời vào nhóm).
 *
 *  - Dùng chung với 2 tab cũ: Thông tin chung (`users.profile.info`) và
 *    Đổi mật khẩu (`users.profile.password`).
 *  - Mọi thao tác đều nhắm đúng user đang đăng nhập (không có ID trên URL) nên
 *    không thể sửa hộ người khác.
 */
class SettingsController extends Controller
{
    /** Trang TỔNG QUAN "Thiết lập tài khoản": liệt kê 5 tab con. */
    public function index()
    {
        return view('users.settings.index', ['user' => Auth::user()]);
    }

    /** Tab BẢO MẬT: lịch sử đăng nhập + các phiên đang mở. */
    public function security(LoginHistoryService $loginHistories)
    {
        $user = Auth::user();
        $currentSessionId = request()->session()->getId();

        // Bó-4 (fix): bảng `sessions` chỉ tồn tại sau migration L09 — kiểm tra để trang
        // không báo lỗi 500 trên DB chưa chạy migrate (vd môi trường mới).
        $activeSessions = Schema::hasTable('sessions')
            ? DB::table('sessions')
                ->where('user_id', $user->user_id)
                ->orderByDesc('last_activity')
                ->get()
            : collect();

        return view('users.settings.security', [
            'user' => $user,
            'histories' => $loginHistories->latestFor($user, 10),
            'loginCount' => $loginHistories->countFor($user),
            'activeSessions' => $activeSessions,
            'currentSessionId' => $currentSessionId,
            // Dòng "Phiên hiện tại": null khi chưa có dữ liệu (vd SESSION_DRIVER != database).
            'currentSession' => $activeSessions->firstWhere('id', $currentSessionId),
        ]);
    }

    /** Tab HỒ SƠ: ảnh đại diện + ngôn ngữ + múi giờ. */
    public function updateProfile(Request $request)
    {
        // 2026-10-10: form tab Hồ sơ chỉ còn ẢNH ĐẠI DIỆN (bỏ ô Ngôn ngữ hiển thị + Múi giờ).
        // locale/timezone vẫn nhận nếu client cũ gửi lên (backward-compatible) nhưng KHÔNG bắt buộc.
        $validated = $request->validate([
            'locale' => ['nullable', 'string', Rule::in(User::LOCALES)],
            'timezone' => ['nullable', 'string', 'timezone'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ], [
            'locale.in' => 'Ngôn ngữ không hợp lệ.',
            'timezone.timezone' => 'Múi giờ không hợp lệ.',
            'avatar.image' => 'Ảnh đại diện phải là file ảnh.',
            'avatar.max' => 'Ảnh đại diện tối đa 2MB.',
        ]);

        /** @var User $user */
        $user = Auth::user();

        $update = ['locale' => $validated['locale'] ?? $user->locale ?? 'vi'];

        // Chỉ ghi timezone khi client thực sự gửi giá trị (không gửi ⇒ giữ nguyên).
        if (! empty($validated['timezone'])) {
            $update['timezone'] = $validated['timezone'];
        }

        if ($request->hasFile('avatar')) {
            $this->deleteAvatarFile($user);
            $update['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($update);

        return back()->with('success', 'Đã lưu tuỳ chọn hồ sơ.');
    }

    /** Xoá ảnh đại diện (quay về chữ cái đầu). */
    public function destroyAvatar()
    {
        /** @var User $user */
        $user = Auth::user();

        $this->deleteAvatarFile($user);
        $user->update(['avatar_path' => null]);

        return back()->with('success', 'Đã xoá ảnh đại diện.');
    }

    /** Tab RIÊNG TƯ: ẩn trạng thái online + ai được mời mình vào nhóm. */
    public function updatePrivacy(Request $request)
    {
        // 2026-10-10: mục "Ai được mời tôi vào nhóm?" chỉ hiện với SINH VIÊN ⇒
        // admin/giảng viên gửi form không có invite_policy vẫn phải lưu được.
        $validated = $request->validate([
            'invite_policy' => ['nullable', 'string', Rule::in(User::INVITE_POLICIES)],
        ], [
            'invite_policy.in' => 'Lựa chọn không hợp lệ.',
        ]);

        /** @var User $user */
        $user = Auth::user();

        $update = ['hide_online' => $request->boolean('hide_online')];

        if (! empty($validated['invite_policy'])) {
            $update['invite_policy'] = $validated['invite_policy'];
        }

        $user->update($update);

        return back()->with('success', 'Đã lưu tuỳ chọn riêng tư.');
    }

    /** Tab HỒ SƠ (GET). */
    public function profile()
    {
        return view('users.settings.profile', ['user' => Auth::user()]);
    }

    /** Tab RIÊNG TƯ (GET): danh sách người đã chặn. */
    public function privacy()
    {
        $user = Auth::user();

        return view('users.settings.privacy', [
            'user' => $user,
            'blockedUsers' => BlockedUser::where('blocker_id', $user->user_id)
                ->with('blocked')
                ->latest('id')
                ->get(),
        ]);
    }

    /**
     * "Đăng xuất khỏi các phiên khác": xoá mọi phiên của user trong bảng `sessions`
     * TRỪ phiên hiện tại (cần SESSION_DRIVER=database để có dữ liệu thật).
     */
    public function revokeOtherSessions(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $currentSessionId = $request->session()->getId();

        $deleted = DB::table('sessions')
            ->where('user_id', $user->user_id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        if ($deleted === 0) {
            return back()->with('info', 'Bạn hiện chỉ có 1 phiên đăng nhập.');
        }

        return back()->with('success', "Đã đăng xuất {$deleted} phiên khác trên các thiết bị khác.");
    }

    /**
     * Thu hồi "ghi nhớ đăng nhập": đổi `remember_token` ⇒ mọi cookie remember cũ
     * trên mọi thiết bị trở nên vô hiệu (phiên hiện tại không bị ảnh hưởng).
     */
    public function revokeRememberToken()
    {
        /** @var User $user */
        $user = Auth::user();

        $user->forceFill(['remember_token' => Str::random(60)])->save();

        return back()->with('success', 'Đã thu hồi "ghi nhớ đăng nhập" trên tất cả thiết bị.');
    }

    /** Bỏ chặn 1 người dùng ngay trong trang Cài đặt (server-rendered). */
    public function unblockUser(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,user_id'],
        ]);

        BlockedUser::where('blocker_id', Auth::id())
            ->where('blocked_id', (int) $validated['user_id'])
            ->delete();

        return back()->with('success', 'Đã bỏ chặn người dùng này.');
    }

    private function deleteAvatarFile(User $user): void
    {
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }
    }
}
