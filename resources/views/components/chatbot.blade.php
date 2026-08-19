<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<style>
    /* CSS cho khung chat đẹp hơn */
    #chat-messages { scroll-behavior: smooth; }
    .message-content ul { margin-bottom: 0; padding-left: 20px; }
    .message-content p { margin-bottom: 5px; }
    /* Tin nhắn user */
    .msg-user { background-color: #0d6efd; color: white; border-radius: 15px 15px 0 15px; }
    /* Tin nhắn bot */
    .msg-bot { background-color: #f1f3f5; color: #212529; border-radius: 15px 15px 15px 0; }
</style>

<button id="chat-toggle-btn" class="btn btn-primary rounded-circle" style="position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
    <i class="fas fa-comment-dots fa-lg"></i>
</button>

<div id="chat-widget" class="card shadow-lg" style="position: fixed; bottom: 100px; right: 30px; width: 350px; height: 500px; z-index: 9999; display: none; flex-direction: column; transition: all 0.3s ease;">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h6 class="m-0"><i class="fas fa-robot me-2"></i>Trợ lý Đề tài</h6>
        <div>
            <button type="button" class="btn btn-sm btn-light me-2" id="expand-chat-btn" title="Mở rộng toàn màn hình">
                <i class="fas fa-expand"></i>
            </button>
            <button type="button" class="btn-close btn-close-white" id="close-chat-btn"></button>
        </div>
    </div>
    
    <div class="card-body p-3" id="chat-messages" style="overflow-y: auto; flex: 1; background: #fff;">
        <div class="d-flex flex-row justify-content-start mb-3">
            <div class="p-3 msg-bot shadow-sm" style="max-width: 85%;">
                Xin chào! 👋 Tôi có thể giúp bạn tìm đề tài hoặc hướng dẫn cách làm đồ án.
            </div>
        </div>
    </div>
    
    <div class="card-footer p-2 bg-white border-top">
        <div class="input-group">
            <input type="text" id="chat-input" class="form-control border-0 bg-light" placeholder="Nhập câu hỏi..." autocomplete="off">
            <button class="btn btn-primary" id="send-btn"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('chat-toggle-btn');
        const closeBtn = document.getElementById('close-chat-btn');
        const expandBtn = document.getElementById('expand-chat-btn');
        const chatWidget = document.getElementById('chat-widget');
        const sendBtn = document.getElementById('send-btn');
        const input = document.getElementById('chat-input');
        const messages = document.getElementById('chat-messages');

        let isExpanded = false;

        // Toggle Chat
        toggleBtn.addEventListener('click', () => chatWidget.style.display = 'flex');
        closeBtn.addEventListener('click', () => {
            chatWidget.style.display = 'none';
            // Reset về kích thước ban đầu khi đóng
            if (isExpanded) {
                isExpanded = false;
                resetChatSize();
            }
        });

        // Expand/Collapse toàn màn hình
        expandBtn.addEventListener('click', () => {
            isExpanded = !isExpanded;
            if (isExpanded) {
                chatWidget.style.top = '0';
                chatWidget.style.left = '0';
                chatWidget.style.bottom = '0';
                chatWidget.style.right = '0';
                chatWidget.style.width = '100vw';
                chatWidget.style.height = '100vh';
                chatWidget.style.borderRadius = '0';
                expandBtn.innerHTML = '<i class="fas fa-compress"></i>';
                expandBtn.title = 'Thu nhỏ';
            } else {
                resetChatSize();
            }
        });

        function resetChatSize() {
            chatWidget.style.top = '';
            chatWidget.style.left = '';
            chatWidget.style.bottom = '100px';
            chatWidget.style.right = '30px';
            chatWidget.style.width = '350px';
            chatWidget.style.height = '500px';
            chatWidget.style.borderRadius = '';
            expandBtn.innerHTML = '<i class="fas fa-expand"></i>';
            expandBtn.title = 'Mở rộng toàn màn hình';
        }

        // Hàm thêm tin nhắn vào khung chat
        function appendMessage(text, sender) {
            const div = document.createElement('div');
            const isUser = sender === 'user';
            
            div.className = `d-flex flex-row justify-content-${isUser ? 'end' : 'start'} mb-3`;
            
            // Nếu là Bot thì parse Markdown, nếu là User thì để text thường (tránh XSS)
            const content = isUser ? text : marked.parse(text);
            
            div.innerHTML = `
                <div class="p-3 ${isUser ? 'msg-user' : 'msg-bot'} shadow-sm message-content" style="max-width: 85%;">
                    ${content}
                </div>
            `;
            
            messages.appendChild(div);
            messages.scrollTop = messages.scrollHeight;
            return div; // Trả về element để có thể xóa nếu cần (ví dụ loading)
        }

        // Xử lý gửi tin
        async function sendMessage() {
            const text = input.value.trim();
            if (!text) return;

            // 1. Hiển thị tin nhắn User NGAY LẬP TỨC
            appendMessage(text, 'user');
            input.value = '';
            input.disabled = true;

            // 2. Hiển thị hiệu ứng "Đang gõ..."
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'd-flex flex-row justify-content-start mb-3';
            loadingDiv.innerHTML = `<div class="p-3 msg-bot shadow-sm"><i class="fas fa-circle-notch fa-spin"></i> Đang suy nghĩ...</div>`;
            messages.appendChild(loadingDiv);
            messages.scrollTop = messages.scrollHeight;

            try {
                const response = await fetch('{{ route("chatbot.ask") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ message: text })
                });

                const data = await response.json();
                
                // Xóa loading và hiện tin nhắn Bot
                loadingDiv.remove();
                appendMessage(data.reply, 'bot');

            } catch (error) {
                loadingDiv.remove();
                appendMessage('⚠️ Lỗi kết nối, vui lòng thử lại.', 'bot');
            } finally {
                input.disabled = false;
                input.focus();
            }
        }

        sendBtn.addEventListener('click', sendMessage);
        input.addEventListener('keypress', (e) => { if (e.key === 'Enter') sendMessage(); });
    });
</script>