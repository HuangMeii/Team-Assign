<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Topics;
use Illuminate\Support\Facades\Auth;

class ChatbotController extends Controller
{
    public function ask(Request $request)
    {
        $question = $request->input('message');
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
            $dbData .= "- [{$t->topic_id}] {$t->name} (GV: {$t->lecturer}) - {$status}\n";
        }

        // 2. PROMPT NÂNG CAO (PHÂN LOẠI Ý ĐỊNH)
        $prompt = "
        Bạn là Trợ lý ảo thông minh của hệ thống quản lý đề tài khoa CNTT.
        Người dùng: $userName.
        Câu hỏi: \"$question\"

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

        // 3. GỌI GEMINI API
        $apiKey = env('GEMINI_API_KEY');
        $url = env('GEMINI_BASE_URL') . "?key={$apiKey}";

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'contents' => [['parts' => [['text' => $prompt]]]]
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Xin lỗi, tôi không thể phản hồi.';
                return response()->json(['reply' => $reply]);
            } 
            
            Log::error('Gemini Error: ' . $response->body());
            return response()->json(['reply' => 'Hệ thống đang bận, vui lòng thử lại sau.'], 500);

        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['reply' => 'Lỗi kết nối.'], 500);
        }
    }
}