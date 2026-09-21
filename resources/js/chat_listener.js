// resources/js/chat_listener.js

// Hàm này để định dạng tin nhắn mới, phải được định nghĩa ở đây
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function (char) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char];
    });
}

function renderMessage(message) {
    const chatBox = document.getElementById('chat-box');
    const userId = parseInt(chatBox.dataset.userId);

    // Định dạng thời gian (Đảm bảo message.created_at có)
    const date = new Date(message.created_at);
    const time = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    // Thông báo / cảnh báo do ADMIN gửi thẳng vào khung chat nhóm -> hiện giữa khung.
    // Khớp với cách render server-side trong resources/views/groups/chat.blade.php.
    if (message.type === 'announcement' || message.type === 'warning') {
        const isWarning = message.type === 'warning';
        const title = isWarning ? '⚠️ Cảnh báo từ Admin' : '📢 Thông báo từ Admin';

        return `
            <div class="message mb-3 text-center" data-message-id="${escapeHtml(message.id ?? '')}">
                <div class="alert ${isWarning ? 'alert-warning' : 'alert-info'} d-inline-block text-start mb-1"
                     style="max-width: 85%; word-wrap: break-word;">
                    <strong>${title}</strong>
                    <small class="text-muted">— ${escapeHtml(message.user?.name ?? 'Admin')}</small>
                    <div class="mt-1">${escapeHtml(message.content)}</div>
                    <small class="text-muted d-block">${time}</small>
                </div>
            </div>
        `;
    }

    const isSelf = message.user_id === userId;
    const alignClass = isSelf ? 'text-end' : 'text-start';
    const bgClass = isSelf ? 'bg-primary text-white rounded-start' : 'bg-light text-dark rounded-end border';
     const userName = isSelf ? 'Bạn' : (message.user?.name ?? 'Người dùng không xác định');

    // Ảnh đính kèm cũng phải hiện ngay khi nhận realtime (không cần reload)
    const attachment = message.attachment_url
        ? `<img src="${escapeHtml(message.attachment_url)}" alt="Ảnh đính kèm" class="d-block mt-2 rounded" style="max-width: 240px; max-height: 180px;">`
        : '';

    return `
        <div class="message mb-2 ${alignClass}" data-message-id="${escapeHtml(message.id ?? '')}">
            <small class="text-muted">${escapeHtml(userName)}:</small>
            <div class="p-2 ${bgClass} d-inline-block" style="max-width: 70%; word-wrap: break-word;">
                ${escapeHtml(message.content)}${attachment}
            </div>
            <small class="text-muted d-block">${time}</small>
        </div>
    `;
}

// Polling fallback: khung chat hỏi lại server định kỳ để KHÔNG bỏ sót tin khi
// WebSocket (Reverb/Echo) không khả dụng (Reverb tắt, /broadcasting/auth lỗi,
// mạng chập, máy vừa ngủ dậy). Tab đang bị ẩn thì không gọi cho đỡ tốn request.
const POLL_INTERVAL_MS = 10000;

// Lấy thông tin cần thiết từ DOM
const chatBox = document.getElementById('chat-box');
const sendForm = document.getElementById('send-message-form');
const groupId = sendForm?.getAttribute('data-group-id');

/** Tin nhắn này đã hiển thị trong khung chat chưa? (chống trùng Echo vs polling) */
function hasMessage(id) {
    return !!id && !!chatBox.querySelector(`[data-message-id="${id}"]`);
}

/** Id tin nhắn mới nhất đang hiển thị — mốc để hỏi server các tin mới hơn. */
function lastMessageId() {
    const nodes = chatBox.querySelectorAll('[data-message-id]');
    const last = nodes.length ? Number(nodes[nodes.length - 1].dataset.messageId) : 0;

    if (Number.isFinite(last) && last > 0) return last;

    return Number(chatBox.dataset.lastMessageId || 0);
}

/** Chèn 1 tin nhắn vào khung chat; bỏ qua nếu đã có (id trùng). */
function appendMessage(message) {
    if (!message || hasMessage(message.id)) return false;

    chatBox.insertAdjacentHTML('beforeend', renderMessage(message));

    const newest = Math.max(Number(chatBox.dataset.lastMessageId || 0), Number(message.id) || 0);
    chatBox.dataset.lastMessageId = String(newest);
    chatBox.scrollTop = chatBox.scrollHeight;

    return true;
}

if (!chatBox || !sendForm || !groupId) {
    // This bundle is loaded on every page; group chat exists only on its own page.
} else {

// 1. Lắng nghe Real-time với Laravel Echo
if (window.Echo) {
    console.log("Echo tải thành công! Bắt đầu lắng nghe kênh nhóm:", groupId);

    window.Echo.private(`chat.group.${groupId}`)
        .listen('.new-message', (e) => { // Sự kiện đã được định nghĩa là 'new-message'
            console.log('Tin nhắn real-time đã đến:', e.message);

            // Render tin nhắn mới (bỏ qua tin đã hiện do polling mang về)
            appendMessage(e.message);
        })
        .error((error) => {
            console.error("Lỗi kết nối kênh chat (Authorization/Transport):", error);
        });
} else {
    // Echo không tải được thì polling bên dưới vẫn cập nhật khung chat.
    console.warn("Laravel Echo không được tải sau khi tất cả các module đã chạy.");
}

// 2. Polling fallback (chỉ chạy ở trang khung chat nhóm)
const messagesUrl = chatBox.dataset.messagesUrl;

if (messagesUrl) {
    let polling = false;

    const pollNewMessages = () => {
        if (polling || document.hidden) return; // tab ẩn -> không tốn request
        polling = true;

        const separator = messagesUrl.includes('?') ? '&' : '?';
        const url = `${messagesUrl}${separator}after=${lastMessageId()}`;

        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then((response) => (response.ok ? response.json() : null))
            .then((data) => {
                (data?.data || []).forEach((message) => appendMessage(message));
            })
            .catch(() => { /* lỗi mạng: lần sau hỏi lại */ })
            .finally(() => { polling = false; });
    };

    window.setInterval(pollNewMessages, POLL_INTERVAL_MS);

    // Vừa quay lại tab -> lấy tin mới ngay, không phải chờ hết chu kỳ
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) pollNewMessages();
    });
    window.addEventListener('focus', pollNewMessages);
}
}

// Giữ lại logic xử lý gửi tin nhắn AJAX của view Blade (multipart + CSRF token).
