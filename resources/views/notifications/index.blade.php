@extends(Auth::user()->role === 'student' ? 'layouts.user' : 'layouts.app')

@section('title', 'Thông báo')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-bell me-2 text-primary"></i>Thông báo</h4>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="markAllReadHere()">
            <i class="fas fa-check-double me-1"></i>Đánh dấu tất cả đã đọc
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="list-group list-group-flush">
            @forelse($notifications as $n)
                @php $joinRequestId = data_get($n->data, 'join_request_id'); @endphp
                <div class="list-group-item {{ $n->is_read ? '' : 'bg-light' }}">
                    <div class="d-flex align-items-start gap-3">
                        <i class="fas {{ $n->icon }} text-{{ $n->color }} mt-1"></i>
                        <div class="flex-grow-1">
                            <p class="mb-1 fw-semibold">{{ $n->title }}</p>
                            <p class="mb-1 text-muted small" style="white-space: pre-line;">{{ $n->message }}</p>
                            <small class="text-muted">
                                <i class="far fa-clock me-1"></i>{{ $n->created_at?->format('d/m/Y H:i') }}
                            </small>

                            {{-- Yêu cầu tham gia nhóm: chỉ cho xử lý khi yêu cầu còn hiệu lực --}}
                            @if($n->type === 'join_request' && $joinRequestId)
                                @php
                                    $decision = $joinRequestDecisions[$joinRequestId]
                                        ?? ['can' => false, 'reason' => 'Yêu cầu không còn hiệu lực'];
                                @endphp
                                <div class="mt-2 d-flex gap-2 flex-wrap align-items-center js-join-request-actions"
                                     data-join-request-id="{{ $joinRequestId }}">
                                    @if($decision['can'])
                                        <form action="{{ route('user.approve-join-request', $joinRequestId) }}" method="POST"
                                              class="js-join-request-form">
                                            @csrf
                                            <button class="btn btn-sm btn-success js-join-request-btn">
                                                <i class="fas fa-check me-1"></i>Chấp nhận
                                            </button>
                                        </form>
                                        <form action="{{ route('user.reject-join-request', $joinRequestId) }}" method="POST"
                                              class="js-join-request-form">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger js-join-request-btn">
                                                <i class="fas fa-times me-1"></i>Từ chối
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-secondary" data-join-request-reason>
                                            <i class="fas fa-ban me-1"></i>{{ $decision['reason'] }}
                                        </span>
                                    @endif
                                    @if(data_get($n->data, 'group_id'))
                                        <a href="{{ route('user.group-join-requests', data_get($n->data, 'group_id')) }}"
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-list me-1"></i>Xem tất cả yêu cầu của nhóm
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="text-nowrap d-flex gap-1">
                            @if(!$n->is_read)
                                <a href="{{ route('notifications.read', $n->notification_id) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Đánh dấu đã đọc">
                                    <i class="fas fa-envelope-open"></i>
                                </a>
                            @endif
                            @if($n->url)
                                <a href="{{ $n->url }}" class="btn btn-sm btn-outline-primary" title="Mở">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            @endif
                            <form action="{{ route('notifications.destroy', $n->notification_id) }}" method="POST"
                                  onsubmit="return confirm('Xóa thông báo này?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Xóa"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-5">
                    <i class="fas fa-bell-slash fa-2x d-block mb-2"></i>
                    Chưa có thông báo nào.
                </div>
            @endforelse
        </div>
    </div>

    @if($notifications instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="mt-3 d-flex justify-content-center">{{ $notifications->links() }}</div>
    @endif
</div>

<script>
    // Tự chứa (không phụ thuộc hàm trong layout) để trang thông báo luôn hoạt động.
    function markAllReadHere() {
        fetch('{{ route('notifications.mark-all-read') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        })
        .then(function (res) { return res.json(); })
        .then(function () { window.location.reload(); })
        .catch(function () { alert('Không đánh dấu được, vui lòng thử lại.'); });
    }
</script>
@endsection
