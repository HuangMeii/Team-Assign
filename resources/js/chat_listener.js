// resources/js/chat_listener.js

// Hàm này để định dạng tin nhắn mới, phải được định nghĩa ở đây
function renderMessage(message) {
    const chatBox = document.getElementById('chat-box');
    const userId = parseInt(chatBox.dataset.userId);

    const isSelf = message.user_id === userId;
    const alignClass = isSelf ? 'text-end' : 'text-start';
    const bgClass = isSelf ? 'bg-primary text-white rounded-start' : 'bg-light text-dark rounded-end border';
     const userName = isSelf ? 'Bạn' : (message.user?.name ?? 'Người dùng không xác định');

    // Định dạng thời gian (Đảm bảo message.created_at có)
    const date = new Date(message.created_at);
    const time = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    return `
        <div class="message mb-2 ${alignClass}">
            <small class="text-muted">${userName}:</small>
            <div class="p-2 ${bgClass} d-inline-block" style="max-width: 70%; word-wrap: break-word;">
                ${message.content}
            </div>
            <small class="text-muted d-block">${time}</small>
        </div>
    `;
}

// Lấy thông tin cần thiết từ DOM
const chatBox = document.getElementById('chat-box');
// Lấy data-group-id từ form, hoặc từ chat-box (đảm bảo bạn thêm data-group-id vào chat-box trong Blade)
const groupId = document.getElementById('send-message-form').getAttribute('data-group-id'); 

// 1. Lắng nghe Real-time với Laravel Echo
if (window.Echo) {
    console.log("Echo tải thành công! Bắt đầu lắng nghe kênh nhóm:", groupId); 
    
    window.Echo.private(`chat.group.${groupId}`)
        .listen('.new-message', (e) => { // Sự kiện đã được định nghĩa là 'new-message'
            console.log('Tin nhắn real-time đã đến:', e.message);

            // Render tin nhắn mới
            const newMessageHtml = renderMessage(e.message);
            chatBox.insertAdjacentHTML('beforeend', newMessageHtml);

            // Cuộn xuống cuối
            chatBox.scrollTop = chatBox.scrollHeight;
        })
        .error((error) => {
            console.error("Lỗi kết nối kênh chat (Authorization/Transport):", error);
        });
} else {
    // Nếu Echo không tải được, logic AJAX gửi tin nhắn vẫn sẽ hoạt động thủ công
    console.warn("Laravel Echo không được tải sau khi tất cả các module đã chạy.");
}

// Giữ lại logic xử lý gửi tin nhắn AJAX của bạn trong view Blade, 
// hoặc chuyển nó vào đây nếu bạn muốn tất cả logic JS trong file module. 
// Nếu bạn muốn giữ logic AJAX trong Blade (để dễ dàng truy cập token/URL): 
// chỉ cần xóa logic lắng nghe Echo khỏi Blade.