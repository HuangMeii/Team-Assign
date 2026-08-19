@extends('layouts.user')

@section('title', 'Chat Nhóm')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <h2 class="text-primary"> Chat Nhóm: {{ $group->group_name }}</h2>
            <p class="text-muted">Topic: {{ $group->topic->name ?? 'Chưa chọn Topic' }}</p>

            <div id="chat-box" 
                 class="card shadow-sm" 
                 data-group-id="{{ $group->group_id }}" {{-- Giữ lại data-group-id cho JS module --}}
                 data-user-id="{{ Auth::id() }}"
                 style="height: 50vh; overflow-y: scroll; padding: 15px;">
                @forelse ($messages as $message)
                    <div class="message mb-2 
                        @if($message->user_id === Auth::id()) 
                            text-end 
                        @else 
                            text-start 
                        @endif">
                        
                        <small class="text-muted">{{ $message->user->name }}:</small>
                        <div class="p-2 
                            @if($message->user_id === Auth::id()) 
                                bg-primary text-white rounded-start d-inline-block
                            @else 
                                bg-light text-dark rounded-end d-inline-block border
                            @endif" 
                            style="max-width: 70%; word-wrap: break-word;">
                            {{ $message->content }}
                        </div>
                        <small class="text-muted d-block">{{ $message->created_at->format('H:i') }}</small>
                    </div>
                @empty
                    <p class="text-center text-muted">Chưa có tin nhắn nào. Hãy là người bắt đầu!</p>
                @endforelse
            </div>
            
            <div class="mt-3" style="position: relative; z-index: 1000;">
                <form id="send-message-form" data-group-id="{{ $group->group_id }}">
                    @csrf
                    <div class="input-group">
                        <input type="text" 
                               name="content" 
                               id="message-input" 
                               class="form-control" 
                               placeholder="Nhập tin nhắn..." 
                               autocomplete="off"
                               required>
                        <button type="submit" class="btn btn-primary">Gửi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>
    /* CSS giữ nguyên */
    body > div[style*="position: fixed"][style*="bottom"] {
        top: 80px !important;
        bottom: auto !important;
        right: 20px !important;
        z-index: 9999 !important;
    }
    
    #send-message-form {
        position: relative;
        z-index: 10;
    }
</style>
<script>
    // GIỮ LẠI HÀM renderMessage VÀ LOGIC GỬI TIN NHẮN AJAX/THỦ CÔNG
    
    function renderMessage(message) {
        const chatBox = document.getElementById('chat-box');
        const userId = parseInt(chatBox.dataset.userId);

        const isSelf = message.user_id === userId;
        const alignClass = isSelf ? 'text-end' : 'text-start';
        const bgClass = isSelf ? 'bg-primary text-white rounded-start' : 'bg-light text-dark rounded-end border';
        const userName = isSelf ? 'Bạn' : message.user.name;

        // Định dạng thời gian
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

    const groupId = document.getElementById('send-message-form').getAttribute('data-group-id');
    const chatBox = document.getElementById('chat-box');

    // CUỘN XUỐNG CUỐI KHI LOAD (Vẫn cần)
    chatBox.scrollTop = chatBox.scrollHeight;

    // 2. Xử lý gửi tin nhắn AJAX (Vẫn giữ lại)
    document.getElementById('send-message-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = e.target;
        const contentInput = document.getElementById('message-input');
        const content = contentInput.value.trim();

        if (!content) { alert('Vui lòng nhập tin nhắn!'); return; }

        let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            const tokenInput = form.querySelector('input[name="_token"]');
            if (tokenInput) { csrfToken = tokenInput.value; }
        }

        if (!csrfToken) {
            console.error('CSRF token not found!');
            alert('Lỗi: Không tìm thấy CSRF token. Vui lòng reload trang.');
            return;
        }

        fetch(`/groups/${groupId}/chat/send`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ content: content })
        })
        .then(response => {
            if (response.status === 403) { alert('Bạn không có quyền gửi tin nhắn.'); return null; }
            if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); }
            return response.json();
        })
        .then(data => {
            if (data && data.data) {
                contentInput.value = '';
                
                // Nếu Echo không hoạt động (đã được xử lý ở chat_listener.js), 
                // hiển thị tin nhắn thủ công tại đây
                if (!window.Echo) {
                    const newMessageHtml = renderMessage(data.data);
                    chatBox.insertAdjacentHTML('beforeend', newMessageHtml);
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
            }
        })
        .catch(error => {
            console.error('Lỗi khi gửi tin nhắn:', error);
            alert('Gửi tin nhắn thất bại: ' + error.message);
        });
    });
</script>
@endpush

@endsection