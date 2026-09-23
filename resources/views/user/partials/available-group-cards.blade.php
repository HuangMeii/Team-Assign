{{--
    Thẻ nhóm "còn chỗ" — dùng cho modal TÌM NHÓM CỦA TỪNG LỚP ở trang "Nhóm của tôi".

    Biến cần có:
      - $classAvailableGroups: collection nhóm còn chỗ THUỘC 1 lớp học phần
      - $maxMembersByGroup: map [group_id => max_members] (truyền từ UserDashboardController::myGroups)
--}}
@forelse($classAvailableGroups as $classGroup)
    @php
        $totalMembers = $classGroup->members->count() + 1;
        $groupMax = $maxMembersByGroup[$classGroup->group_id] ?? 5;
        $hasPendingRequest = $classGroup->joinRequests()
            ->where('member_id', Auth::id())
            ->where('status', 'Pending')
            ->exists();
    @endphp

    <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 border">
            <div class="card-body p-3">
                <h6 class="mb-3 fw-bold" title="{{ $classGroup->group_name }}">
                    {{ $classGroup->group_name }}
                </h6>
                <div class="mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-user-tie text-muted me-2"></i>
                        <small class="text-muted">{{ $classGroup->leader->name }}</small>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-users text-muted me-2"></i>
                        <small class="text-muted">{{ $totalMembers }}/{{ $groupMax }} thành viên</small>
                    </div>
                    @if($classGroup->topic)
                        <div class="d-flex align-items-center">
                            <i class="fas fa-lightbulb text-warning me-2"></i>
                            <small class="text-muted">Đã có đề tài</small>
                        </div>
                    @endif
                </div>

                <div class="mt-auto">
                    @if($hasPendingRequest)
                        <div class="d-grid">
                            <span class="badge bg-warning py-2">
                                <i class="fas fa-clock me-1"></i>Đang chờ duyệt
                            </span>
                        </div>
                    @else
                        <form action="{{ route('user.send-join-request') }}" method="POST">
                            @csrf
                            <input type="hidden" name="group_id" value="{{ $classGroup->group_id }}">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i>Xin tham gia
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="col-12">
        <div class="text-center py-5">
            <i class="fas fa-users fa-3x text-muted opacity-50 mb-3"></i>
            <p class="text-muted mb-0">Lớp này chưa có nhóm nào còn chỗ trống</p>
        </div>
    </div>
@endforelse
