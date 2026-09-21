const currentUser = document.querySelector('[data-current-user-id]');
const userId = currentUser?.dataset.currentUserId;

function getBadgeCount(badge) {
    const match = String(badge.textContent || '').match(/\d+/);
    return match ? parseInt(match[0], 10) : 0;
}

function showBadge(badge, count) {
    if (!badge) return;
    const value = typeof count === 'number' ? count : getBadgeCount(badge);
    if (!Number.isFinite(value) || value <= 0) {
        badge.textContent = '0';
        badge.style.display = 'none';
        return;
    }
    badge.textContent = value > 99 ? '99+' : String(value);
    badge.style.display = '';
}

function incrementBadge(selector) {
    const badge = document.querySelector(selector);
    if (!badge) return;
    showBadge(badge, getBadgeCount(badge) + 1);
}

function decrementBadge(selector) {
    const badge = document.querySelector(selector);
    if (!badge) return;
    showBadge(badge, Math.max(getBadgeCount(badge) - 1, 0));
}

/**
 * Yêu cầu tham gia nhóm đã được xử lý / hết hiệu lực:
 * - giảm badge "Yêu cầu" (data-request-badge);
 * - tắt nút Chấp nhận / Từ chối của đúng yêu cầu đó (nếu đang hiển thị).
 */
function markJoinRequestResolved(joinRequestId, reason) {
    if (!joinRequestId) return;

    decrementBadge('[data-request-badge]');

    const wrapper = document.querySelector(`[data-join-request-id="${joinRequestId}"]`);
    if (!wrapper) return;

    // Bỏ form Chấp nhận / Từ chối (yêu cầu không còn hiệu lực để xử lý)
    wrapper.querySelectorAll('form.js-join-request-form').forEach((form) => form.remove());

    const actions = wrapper.classList.contains('js-join-request-actions')
        ? wrapper
        : wrapper.querySelector('.js-join-request-actions');

    if (actions && !actions.querySelector('[data-join-request-resolved]')) {
        const note = document.createElement('button');
        note.type = 'button';
        note.className = 'btn btn-outline-secondary w-100';
        note.disabled = true;
        note.setAttribute('data-join-request-resolved', '1');
        note.textContent = reason || 'Yêu cầu đã được xử lý';
        actions.prepend(note);
    }

    // Nhãn lý do cạnh tên sinh viên (nếu có)
    wrapper.querySelectorAll('[data-join-request-reason]').forEach((badgeEl) => {
        badgeEl.textContent = reason || 'Yêu cầu đã được xử lý';
    });
}

// Chống bấm 2 lần: tắt nút ngay khi submit form xử lý yêu cầu.
document.addEventListener('submit', (event) => {
    const form = event.target?.closest?.('form.js-join-request-form');
    if (!form) return;

    const actions = form.closest('.js-join-request-actions') || form.parentElement;
    actions?.querySelectorAll('button').forEach((button) => {
        button.disabled = true;
    });
});

// Badge của ĐÚNG người gửi trong danh sách hội thoại (chat/index.blade.php).
// Không có phần tử (đang ở trang khác) thì bỏ qua.
function incrementUserBadge(peerId) {
    const badge = document.querySelector(`[data-chat-badge-user-id="${peerId}"]`);
    if (!badge) return;
    showBadge(badge, getBadgeCount(badge) + 1);
}

// Badge của ĐÚNG nhóm trong danh sách nhóm.
function incrementGroupBadge(groupId) {
    const badge = document.querySelector(`[data-chat-badge-group-id="${groupId}"]`);
    if (!badge) return;
    showBadge(badge, getBadgeCount(badge) + 1);
}

// --- Toast "Tin nhắn mới" (thuần DOM + inline style, không phụ thuộc Bootstrap JS) ---
// URL mặc định khớp route: chat.show (/chat/{user}) và groups.chat.show (/groups/{groupId}/chat)
const CHAT_URL_TEMPLATE = document.body?.dataset.chatUrlTemplate || '/chat/__ID__';
const GROUP_CHAT_URL_TEMPLATE = document.body?.dataset.groupChatUrlTemplate || '/groups/__ID__/chat';

function chatUrl(id) {
    return CHAT_URL_TEMPLATE.replace('__ID__', id);
}

function groupChatUrl(id) {
    return GROUP_CHAT_URL_TEMPLATE.replace('__ID__', id);
}

// --- Đánh dấu đã đọc (AJAX) ---
// Khớp route: chat.read (/chat/{user}/read) và groups.chat.read (/groups/{groupId}/chat/read)
const CHAT_READ_URL_TEMPLATE = document.body?.dataset.chatReadUrlTemplate || '/chat/__ID__/read';
const GROUP_CHAT_READ_URL_TEMPLATE = document.body?.dataset.groupChatReadUrlTemplate || '/groups/__ID__/chat/read';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '';
}

// Chống gọi lặp liên tục cho cùng một hội thoại (mỗi tin nhắn đến lại ping 1 lần).
const lastReadPingAt = {};

/**
 * Gọi API đánh dấu đã đọc: server trả về badge TỔNG mới, ta đồng thời xoá badge
 * RIÊNG của hội thoại vừa đọc -> badge cá nhân và badge tổng luôn khớp server.
 */
function markConversationRead(options) {
    if (!options || !options.url) return;

    const now = Date.now();
    if (lastReadPingAt[options.url] && now - lastReadPingAt[options.url] < 2000) return;
    lastReadPingAt[options.url] = now;

    fetch(options.url, {
        method: 'POST',
        keepalive: true,
        credentials: 'same-origin',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    })
        .then((response) => (response.ok ? response.json() : null))
        .then((data) => {
            if (!data) return;
            showBadge(document.querySelector(options.badgeSelector), 0);
            showBadge(document.querySelector('[data-chat-badge]'), Number(data.total) || 0);
        })
        .catch(() => {});
}

// Bấm vào một hội thoại (người dùng hoặc nhóm) -> cập nhật badge riêng + badge tổng.
// Không preventDefault để link vẫn điều hướng bình thường.
document.addEventListener('click', (event) => {
    const link = event.target.closest('[data-chat-peer-id],[data-chat-group-id]');
    if (!link) return;

    const peerId = link.dataset.chatPeerId;
    if (peerId) {
        decreaseTotalBadge(resetBadge(`[data-chat-badge-user-id="${peerId}"]`));

        markConversationRead({
            url: CHAT_READ_URL_TEMPLATE.replace('__ID__', peerId),
            badgeSelector: `[data-chat-badge-user-id="${peerId}"]`,
        });
        return;
    }

    const groupId = link.dataset.chatGroupId;
    if (groupId) {
        decreaseTotalBadge(resetBadge(`[data-chat-badge-group-id="${groupId}"]`));

        markConversationRead({
            url: GROUP_CHAT_READ_URL_TEMPLATE.replace('__ID__', groupId),
            badgeSelector: `[data-chat-badge-group-id="${groupId}"]`,
        });
    }
});

function getToastContainer() {
    let box = document.getElementById('chat-toast-container');
    if (!box) {
        box = document.createElement('div');
        box.id = 'chat-toast-container';
        box.style.cssText = 'position:fixed;top:12px;right:12px;z-index:1090;display:flex;flex-direction:column;gap:8px;max-width:320px;';
        document.body.appendChild(box);
    }
    return box;
}

function showChatToast(options) {
    if (!options || !options.url) return;

    const toast = document.createElement('a');
    toast.href = options.url;
    toast.className = 'text-decoration-none';
    toast.style.cssText = 'display:block;background:#fff;border-left:4px solid #0d6efd;border-radius:8px;'
        + 'box-shadow:0 6px 18px rgba(0,0,0,.18);padding:10px 12px;color:#212529;cursor:pointer;';

    const header = document.createElement('div');
    header.style.cssText = 'font-weight:600;font-size:.9rem;margin-bottom:4px;';
    header.textContent = options.title || 'Tin nhắn mới';
    toast.appendChild(header);

    const content = document.createElement('div');
    content.style.cssText = 'font-size:.85rem;color:#495057;word-break:break-word;';
    content.textContent = options.body || '';
    toast.appendChild(content);

    if (options.imageUrl) {
        const image = document.createElement('img');
        image.src = options.imageUrl;
        image.alt = 'Ảnh đính kèm';
        image.style.cssText = 'display:block;margin-top:6px;max-width:100%;max-height:120px;border-radius:6px;';
        toast.appendChild(image);
    }

    const hint = document.createElement('div');
    hint.style.cssText = 'font-size:.75rem;color:#6c757d;margin-top:6px;';
    hint.textContent = 'Bấm để mở cuộc trò chuyện';
    toast.appendChild(hint);

    getToastContainer().appendChild(toast);

    // Tự ẩn sau 8 giây
    window.setTimeout(function () {
        toast.remove();
    }, 8000);
}

// Đồng bộ trạng thái badge khi tải trang (ẩn badge khi = 0, rút gọn khi > 99)
document.querySelectorAll('[data-chat-badge],[data-chat-badge-user-id],[data-chat-badge-group-id],[data-notification-badge],[data-request-badge],[data-invite-badge],[data-moderation-badge]').forEach((badge) => {
    showBadge(badge, badge.textContent);
});

if (window.Echo && userId) {
    window.Echo.private(`chat.${userId}`)
        // ---- Chat 1-1: badge của ĐÚNG người gửi + badge tổng + toast "tin nhắn mới" ----
        .listen('.direct-message', (event) => {
            const message = event?.message || {};
            const senderId = Number(message.sender_id);

            // Đang mở đúng hội thoại với người gửi -> đánh dấu đã đọc, không cần badge/toast
            const openWith = document.querySelector('[data-direct-chat-user-id]')?.dataset.directChatUserId;
            if (openWith && Number(openWith) === senderId) {
                markConversationRead({
                    url: CHAT_READ_URL_TEMPLATE.replace('__ID__', senderId),
                    badgeSelector: `[data-chat-badge-user-id="${senderId}"]`,
                });
                return;
            }

            incrementUserBadge(senderId);
            incrementBadge('[data-chat-badge]');
            showChatToast({
                title: `Tin nhắn mới từ ${message.sender?.name || 'người dùng'}`,
                body: message.content || '(Ảnh)',
                imageUrl: message.attachment_url,
                url: chatUrl(message.sender_id),
            });
        })
        // ---- Chat nhóm: NewChatMessage broadcast thêm kênh cá nhân của thành viên ----
        .listen('.new-message', (event) => {
            const message = event?.message || {};
            const groupId = Number(message.group_id);

            // Đang mở đúng nhóm này thì chat_listener.js đã render tin nhắn
            const openGroup = document.getElementById('chat-box')?.dataset.groupId;
            if (openGroup && Number(openGroup) === groupId) {
                markConversationRead({
                    url: GROUP_CHAT_READ_URL_TEMPLATE.replace('__ID__', groupId),
                    badgeSelector: `[data-chat-badge-group-id="${groupId}"]`,
                });
                return;
            }

            incrementGroupBadge(groupId);
            incrementBadge('[data-chat-badge]');
            showChatToast({
                title: message.user?.name
                    ? `Tin nhắn nhóm mới từ ${message.user.name}`
                    : 'Tin nhắn nhóm mới',
                body: message.content || '(Ảnh)',
                imageUrl: message.attachment_url,
                url: groupChatUrl(message.group_id),
            });
        })
        // ---- Badge "Yêu cầu": DUY NHẤT qua event JoinRequestCreated ----
        .listen('.join-request', () => incrementBadge('[data-request-badge]'))
        // ---- Yêu cầu đã xử lý / hết hiệu lực: giảm badge + tắt nút Chấp nhận/Từ chối ----
        .listen('.join-request-resolved', (event) => {
            markJoinRequestResolved(
                Number(event?.join_request_id),
                event?.reason
            );
        })
        // ---- Badge chuông thông báo ----
        .listen('.notification-created', () => incrementBadge('[data-notification-badge]'));
}

// --- Badge "Giám sát Chat" (chỉ render cho admin) ---
// Số liệu = số tin nhắn/ảnh bị gắn cờ CHƯA XEM (flagged_at > users.flagged_seen_at).
// Mở trang/tab "Bị gắn cờ" thì server set flagged_seen_at = now() nên badge reset về 0.
// Lấy URL đếm từ [data-flagged-count-url] để chạy được ở cả layouts/app lẫn layouts/admin.
const flaggedCountUrl = document.querySelector('[data-flagged-count-url]')?.dataset.flaggedCountUrl;

if (flaggedCountUrl) {
    function refreshModerationBadge() {
        fetch(flaggedCountUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then((response) => (response.ok ? response.json() : null))
            .then((data) => {
                if (!data) return;
                document.querySelectorAll('[data-moderation-badge]').forEach((badge) => {
                    showBadge(badge, Number(data.count) || 0);
                });
            })
            .catch(() => {});
    }

    refreshModerationBadge();
    window.setInterval(refreshModerationBadge, 15000);
}

// --- Bấm vào đối tượng có badge: badge về 0 và TẮT NGAY (không chờ server) ---
const BADGE_SELECTORS = {
    request: '[data-request-badge]',
    invite: '[data-invite-badge]',
    notification: '[data-notification-badge]',
    chat: '[data-chat-badge]',
};

/** Dua badge ve 0 + an ngay (optimistic). Tra ve so cu de tru badge tong. */
function resetBadge(selector) {
    if (!selector) return 0;

    const badge = document.querySelector(selector);
    if (!badge) return 0;

    const old = getBadgeCount(badge);
    showBadge(badge, 0);

    return old;
}

/** Tru badge tong theo so vua doc (khi reset badge cua mot hoi thoai/nhom). */
function decreaseTotalBadge(amount) {
    if (!amount) return;

    const badge = document.querySelector(BADGE_SELECTORS.chat);
    if (!badge) return;

    showBadge(badge, Math.max(getBadgeCount(badge) - amount, 0));
}

/** Bao server da xem (badge Yeu cau / Loi moi / chuong) de badge khong hien lai. */
function markBadgeSeen(url) {
    if (!url) return;

    fetch(url, {
        method: 'POST',
        keepalive: true,
        credentials: 'same-origin',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    }).catch(() => {});
}

// Bấm vào mục "Yêu cầu" / "Lời mời" / chuông thông báo: badge về 0 và TẮT NGAY,
// đồng thời báo server "đã xem" để badge không hiện lại khi tải trang.
document.addEventListener('click', (event) => {
    const seenLink = event.target.closest('[data-badge-clear]');

    if (seenLink) {
        resetBadge(BADGE_SELECTORS[seenLink.dataset.badgeClear] || '');
        markBadgeSeen(seenLink.dataset.badgeSeenUrl);

        return;
    }

    // Bấm 1 thông báo trong dropdown chuông -> badge chuông về 0 + tắt ngay.
    const notificationLink = event.target.closest('a[href*="/notifications/"]');

    if (notificationLink) {
        resetBadge(BADGE_SELECTORS.notification);
        markBadgeSeen(document.querySelector('[data-badge-clear="notification"]')?.dataset.badgeSeenUrl);
    }
});
