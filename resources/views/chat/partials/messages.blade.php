@php
    /**
     * Danh sách tin nhắn 1-1 — dùng CHUNG cho lần tải đầu (chat.index) và khi
     * "Tải thêm tin nhắn cũ" (AJAX) để trạng thái tick + tách ngày luôn nhất quán.
     *
     * @var \Illuminate\Support\Collection<int, \App\Models\DirectMessage> $messages
     * @var string|null $previousDate  Ngày của tin ĐẦU TIÊN đang hiển thị (tránh lặp nhãn ngày khi chèn thêm)
     */
    $authId = Auth::id();
    $previousDate = $previousDate ?? null;
    $lastDate = $previousDate;
@endphp

@forelse($messages as $message)
    @php
        $dateKey = $message->created_at?->toDateString();
        $mine = (int) $message->sender_id === (int) $authId;
        $status = $message->deliveryStatus();
    @endphp

    @if($dateKey && $dateKey !== $lastDate)
        <div class="text-center my-3">
            <span class="badge bg-light text-muted border px-3 py-1">
                {{ $message->created_at->isToday() ? 'Hôm nay' : ($message->created_at->isYesterday() ? 'Hôm qua' : $message->created_at->format('d/m/Y')) }}
            </span>
        </div>
        @php $lastDate = $dateKey; @endphp
    @endif

    <div class="mb-3 {{ $mine ? 'text-end' : '' }}"
         data-message-id="{{ $message->id }}"
         data-message-date="{{ $message->created_at?->toDateString() }}"
         data-message-status="{{ $mine ? $status : '' }}">
        <span class="d-inline-block p-2 rounded {{ $mine ? 'bg-primary text-white' : 'bg-light' }}">{{ $message->content }}@if($message->attachment_url)<img src="{{ $message->attachment_url }}" alt="Ảnh đính kèm" class="d-block mt-2 rounded" style="max-width: 240px; max-height: 180px;">@endif</span>

        <small class="d-block text-muted">
            {{ $message->created_at?->format('H:i') }}

            @if($mine)
                {{-- Trạng thái tin nhắn (2 mốc): ĐÃ GỬI ✓ xám · ĐÃ XEM ✓✓ xanh --}}
                <span class="ms-1 message-status {{ $status === 'seen' ? 'text-success' : 'text-secondary' }}"
                      data-role="message-status"
                      title="{{ $status === 'seen'
                            ? 'Đã xem lúc ' . ($message->seen_at?->format('H:i d/m/Y') ?? $message->created_at?->format('H:i d/m/Y'))
                            : 'Đã gửi lúc ' . $message->created_at?->format('H:i d/m/Y') }}"
                      aria-label="{{ $message->deliveryStatusLabel() }}">
                    <i class="fas {{ $status === 'seen' ? 'fa-check-double' : 'fa-check' }}"></i>
                </span>
            @endif
        </small>
    </div>
@empty
    <p class="text-muted" data-role="no-messages">Chưa có tin nhắn.</p>
@endforelse
