@props(['item', 'type' => 'invite'])

<div class="col-md-6 col-lg-4 mb-4">
    <div class="card h-100 shadow-sm border-0 transition-transform">
        <div class="card-body">
            <!-- Title and Status -->
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h5 class="card-title text-primary mb-0 fw-bold">
                    {{ $item->group->group_name }}
                </h5>
                <x-status-badge :status="$item->status" />
            </div>

            <!-- Leader Info -->
            @if ($item->group->leader)
                <p class="text-muted small mb-2">
                    <i class="fas fa-crown text-warning"></i> 
                    <strong>Trưởng nhóm:</strong>
                </p>
                <p class="mb-3">{{ $item->group->leader->name }}</p>
            @endif

            <!-- Members Count -->
            <p class="text-muted small mb-2">
                <i class="fas fa-users"></i> 
                <strong>Thành viên:</strong>
            </p>
            <p class="mb-3">
                <span class="badge bg-info">
                    @if ($type === 'join_request')
                        {{ $item->group->members->count() + 1 }} thành viên
                    @else
                        {{ $item->group->members->count() }} thành viên
                    @endif
                </span>
            </p>

            <!-- Topic Info -->
            @if ($item->group->topic_id && $item->group->topic)
                <div class="mb-3 p-2 rounded bg-light border-start border-3" style="border-color: var(--success);">
                    <small class="text-muted"><i class="fas fa-book"></i> Đề tài:</small>
                    <p class="mb-0 fw-bold text-truncate" title="{{ $item->group->topic->name }}">
                        {{ Str::limit($item->group->topic->name, 50) }}
                    </p>
                </div>
            @else
                <div class="alert alert-sm alert-warning mb-3" style="padding: 8px 12px; font-size: 13px;">
                    <i class="fas fa-exclamation-triangle"></i> Nhóm chưa có đề tài
                </div>
            @endif

            <!-- Date Submitted -->
            <p class="text-muted small mb-0">
                <i class="fas fa-calendar"></i> <strong>Ngày gửi:</strong><br>
                {{ $item->created_at?->format('d/m/Y H:i') ?? 'N/A' }}
            </p>
        </div>

        <!-- Actions Slot or Default -->
        @if ($slot->isNotEmpty())
            {{ $slot }}
        @else
            @if ($item->status === 'Pending')
                <div class="card-footer bg-transparent border-top">
                    <form action="{{ route('user.request-cancel', $item) }}" method="POST" 
                        onsubmit="return confirm('Bạn chắc chắn muốn hủy yêu cầu này?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100 btn-sm">
                            <i class="fas fa-times me-1"></i> Hủy yêu cầu
                        </button>
                    </form>
                </div>
            @elseif ($item->status === 'Approved')
                <div class="card-footer bg-transparent border-top">
                    <a href="{{ route('user.group-detail', $item->group->group_id) }}" class="btn btn-success w-100 btn-sm">
                        <i class="fas fa-eye me-1"></i> Xem nhóm
                    </a>
                </div>
            @else
                <div class="card-footer bg-transparent border-top">
                    <span class="badge bg-danger">Yêu cầu bị từ chối</span>
                </div>
            @endif
        @endif
    </div>
</div>

<style>
.transition-transform {
    transition: transform 0.3s, box-shadow 0.3s;
}

.transition-transform:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
}

.alert-sm {
    margin-bottom: 0;
}
</style>