<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Topics;
use App\Services\ChatbotService;
use Illuminate\Support\Facades\Auth;

class ChatbotController extends Controller
{
    public function __construct(
        private readonly ChatbotService $chatbot,
    ) {}

    public function ask(Request $request)
    {
        $question = trim((string) $request->input('message'));
        if ($question === '') {
            return response()->json(['reply' => 'Bạn hãy nhập câu hỏi nhé!']);
        }

        $user = Auth::user();
        $userName = $user->name ?? 'Bạn';

        // 1. LẤY DỮ LIỆU (CONTEXT)
        // Lấy tối đa 20 đề tài mới nhất để làm context
        $topics = Topics::with(['class', 'subject'])
            ->orderBy('created_at', 'desc') // Ưu tiên đề tài mới tạo
            ->take(20)
            ->get();

        $dbData = "";
        foreach ($topics as $t) {
            $status = $t->assigned_group_id ? "Đã có nhóm" : "Còn trống";
            $subject = $t->subject ? $t->subject->subject_name : "Chưa rõ môn";
            $dbData .= "- [{$t->topic_id}] {$t->name} (GV: {$t->lecturer}) - {$status}" . PHP_EOL;
        }

        // 2. PROMPT NÂNG CAO (PHÂN LOẠI Ý ĐỊNH)
        $prompt = "
        Bạn là Trợ lý ảo thông minh của hệ thống quản lý đề tài khoa CNTT.
        Người dùng: $userName.
        Câu hỏi: {$question}

        DỮ LIỆU ĐỀ TÀI TRONG HỆ THỐNG (Chỉ dùng khi cần tra cứu):
        ----------------
        $dbData
        ----------------

        CHỈ THỊ XỬ LÝ:
        Hãy phân tích câu hỏi của người dùng và chọn 1 trong 3 kịch bản sau để trả lời:

        1. **Kịch bản Xã giao** (Chào hỏi, cảm ơn, khen ngợi):
           - Trả lời ngắn gọn, thân thiện.
           - Ví dụ: 'Chào bạn! Mình có thể giúp gì về đề tài hôm nay?'
           - TUYỆT ĐỐI KHÔNG liệt kê danh sách đề tài ở kịch bản này.

        2. **Kịch bản Tra cứu** (Hỏi về đề tài có sẵn, hỏi gợi ý đề tài):
           - Tìm trong 'DỮ LIỆU ĐỀ TÀI' ở trên.
           - Nếu tìm thấy đề tài khớp từ khóa, hãy liệt kê ra (Ghi rõ ID và Trạng thái).
           - Nếu không thấy, hãy báo không có và gợi ý hướng khác.

        3. **Kịch bản Chuyên môn** (Hỏi cách làm, hỏi công nghệ, roadmap):
           - KHÔNG cần check dữ liệu đề tài (trừ khi người dùng hỏi cụ thể về đề tài ID nào).
           - Tập trung tư vấn các bước thực hiện, công nghệ nên dùng (Laravel, React, Python...).
           - Trình bày dạng danh sách (bullet points) cho dễ đọc.

        Hãy trả lời bằng tiếng Việt, định dạng Markdown đẹp mắt.
        ";

        // 3. GỌI LLM (Groq/Gemini qua ChatbotService) — đọc qua config() (an toàn với config:cache).
        //    Thiếu key => trả thông báo thân thiện, KHÔNG gọi API, KHÔNG lỗi 500.
        if (! $this->chatbot->configured()) {
            return response()->json([
                'reply' => 'Trợ lý ảo chưa được cấu hình (thiếu API key trong file .env). Vui lòng liên hệ quản trị viên.',
            ]);
        }

        $result = $this->chatbot->reply($prompt);

        if ($result['ok']) {
            return response()->json(['reply' => $result['reply']]);
        }

        return response()->json(['reply' => $result['message']], 503);
    }
}
