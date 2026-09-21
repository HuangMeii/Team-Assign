@extends('layouts.app')
@section('title', 'Giám sát Chat')
@section('content')
<div class="container-fluid px-4">
<h1 class="mt-4">Giám sát Chat</h1>
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<ul class="nav nav-tabs mb-3">
<li class="nav-item"><a class="nav-link {{ $tab === 'direct' ? 'active' : '' }}" href="{{ route('admin.chat.monitor', ['tab' => 'direct']) }}">Chat cá nhân</a></li>
<li class="nav-item"><a class="nav-link {{ $tab === 'group' ? 'active' : '' }}" href="{{ route('admin.chat.monitor', ['tab' => 'group']) }}">Chat nhóm</a></li>
<li class="nav-item"><a class="nav-link {{ $tab === 'flagged' ? 'active' : '' }}" href="{{ route('admin.chat.monitor', ['tab' => 'flagged']) }}">Bị gắn cờ <span class="badge bg-danger" data-moderation-badge style="display: {{ $flaggedCount > 0 ? '' : 'none' }};">{{ $flaggedCount }}</span></a></li>
</ul>
<div class="card mb-4">
<div class="card-header">Tìm kiếm nội dung</div>
<div class="card-body">
<form method="GET" action="{{ route('admin.chat.monitor') }}" class="row g-2">
<input type="hidden" name="tab" value="{{ $tab }}">
<div class="{{ $tab === 'flagged' ? 'col-md-3' : 'col-md-5' }}"><input name="search" class="form-control" placeholder="Từ khóa nhạy cảm / lý do cờ..." value="{{ request('search') }}"></div>
@if($tab === 'group' || $tab === 'flagged')
<div class="col-md-3"><select name="group_id" class="form-select">
<option value="">Tất cả nhóm</option>
@foreach($groups as $g)
<option value="{{ $g->group_id }}" {{ request('group_id') == $g->group_id ? 'selected' : '' }}>{{ $g->group_name }}</option>
@endforeach
</select></div>
@endif
@if($tab === 'flagged')
<div class="col-md-2"><input type="number" step="0.05" min="0" max="1" name="min_score" class="form-control" placeholder="Điểm ≥" value="{{ request('min_score') }}"></div>
@endif
<div class="col-md-2"><button class="btn btn-primary">Lọc</button></div>
</form>
</div>
</div>
@if($tab === 'direct')
<div class="card mb-4">
<div class="card-header">Tin nhắn cá nhân mới nhất</div>
<div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
<thead class="table-light"><tr><th>ID</th><th>Người gửi</th><th>Người nhận</th><th>Nội dung</th><th>Ảnh</th><th>Thời gian</th><th></th></tr></thead>
<tbody>
@forelse($directMessages as $m)
<tr>
<td>{{ $m->id }}</td>
<td>{{ optional($m->sender)->name ?? '?' }}</td>
<td>{{ optional($m->recipient)->name ?? '?' }}</td>
<td>{{ $m->content }}</td>
<td>@if($m->attachment)<a href="{{ Storage::url($m->attachment) }}" target="_blank">Xem</a>@else - @endif</td>
<td>{{ $m->created_at?->format('d/m H:i') }}</td>
<td><form action="{{ route('admin.chat.direct.destroy', $m->id) }}" method="POST" onsubmit="return confirm('Xóa tin nhắn vi phạm này?');">@csrf @method('DELETE')<button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button></form></td>
</tr>
@empty
<tr><td colspan="7" class="text-center text-muted py-4">Không có tin nhắn.</td></tr>
@endforelse
</tbody>
</table></div></div>
@if($directMessages instanceof \Illuminate\Pagination\LengthAwarePaginator)
<div class="card-footer d-flex justify-content-end">{{ $directMessages->links() }}</div>
@endif
</div>
@elseif($tab === 'group')
<div class="card mb-4">
<div class="card-header">Tin nhắn nhóm mới nhất</div>
<div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
<thead class="table-light"><tr><th>ID</th><th>Nhóm</th><th>Người gửi</th><th>Nội dung</th><th>Ảnh</th><th>Thời gian</th><th></th></tr></thead>
<tbody>
@forelse($groupMessages as $m)
<tr>
<td>{{ $m->id }}</td>
<td>{{ optional($m->group)->group_name ?? $m->group_id }}</td>
<td>{{ optional($m->user)->name ?? '?' }}</td>
<td>{{ $m->content }}</td>
<td>@if($m->attachment)<a href="{{ Storage::url($m->attachment) }}" target="_blank">Xem</a>@else - @endif</td>
<td>{{ $m->created_at?->format('d/m H:i') }}</td>
<td><form action="{{ route('admin.chat.group.destroy', $m->id) }}" method="POST" onsubmit="return confirm('Xóa tin nhắn vi phạm này?');">@csrf @method('DELETE')<button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button></form></td>
</tr>
@empty
<tr><td colspan="7" class="text-center text-muted py-4">Không có tin nhắn.</td></tr>
@endforelse
</tbody>
</table></div></div>
@if($groupMessages instanceof \Illuminate\Pagination\LengthAwarePaginator)
<div class="card-footer d-flex justify-content-end">{{ $groupMessages->links() }}</div>
@endif
</div>
@else
<div class="card mb-4 border-danger">
<div class="card-header bg-danger text-white">Tin nhắn cá nhân bị gắn cờ (vẫn đã gửi tới người nhận)</div>
<div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
<thead class="table-light"><tr><th>ID</th><th>Người gửi → Người nhận</th><th>Nội dung</th><th>Ảnh</th><th>Thời gian</th><th></th></tr></thead>
<tbody>
@forelse($flaggedDirect as $m)
<tr>
<td>{{ $m->id }}</td>
<td>{{ optional($m->sender)->name ?? '?' }} → {{ optional($m->recipient)->name ?? '?' }}</td>
<td class="small">{{ \Illuminate\Support\Str::limit($m->content, 80) }}</td>
<td>@if($m->attachment)<a href="{{ Storage::url($m->attachment) }}" target="_blank">Xem</a>@else - @endif</td>
<td>{{ ($m->flagged_at ?? $m->created_at)?->format('d/m H:i') }}</td>
<td class="text-nowrap">
<form class="d-inline" action="{{ route('admin.chat.direct.unflag', $m->id) }}" method="POST" onsubmit="return confirm('Xác nhận tin nhắn này KHÔNG vi phạm và bỏ cờ (vẫn giữ tin nhắn)?');">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">Bỏ cờ</button></form>
<form class="d-inline" action="{{ route('admin.chat.direct.destroy', $m->id) }}" method="POST" onsubmit="return confirm('Xóa tin nhắn vi phạm này?');">@csrf @method('DELETE')<button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button></form>
</td>
</tr>
@empty
<tr><td colspan="6" class="text-center text-muted py-4">Không có tin nhắn cá nhân nào bị gắn cờ.</td></tr>
@endforelse
</tbody>
</table></div></div>
@if($flaggedDirect instanceof \Illuminate\Pagination\LengthAwarePaginator && $flaggedDirect->hasPages())
<div class="card-footer d-flex justify-content-end">{{ $flaggedDirect->links() }}</div>
@endif
</div>
<div class="card mb-4 border-danger">
<div class="card-header bg-danger text-white">Tin nhắn nhóm bị gắn cờ (vẫn đã gửi tới nhóm)</div>
<div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
<thead class="table-light"><tr><th>ID</th><th>Nhóm</th><th>Người gửi</th><th>Nội dung</th><th>Ảnh</th><th>Thời gian</th><th></th></tr></thead>
<tbody>
@forelse($flaggedGroup as $m)
<tr>
<td>{{ $m->id }}</td>
<td>{{ optional($m->group)->group_name ?? $m->group_id }}</td>
<td>{{ optional($m->user)->name ?? '?' }}</td>
<td class="small">{{ \Illuminate\Support\Str::limit($m->content, 80) }}</td>
<td>@if($m->attachment)<a href="{{ Storage::url($m->attachment) }}" target="_blank">Xem</a>@else - @endif</td>
<td>{{ ($m->flagged_at ?? $m->created_at)?->format('d/m H:i') }}</td>
<td class="text-nowrap">
<form class="d-inline" action="{{ route('admin.chat.group.unflag', $m->id) }}" method="POST" onsubmit="return confirm('Xác nhận tin nhắn này KHÔNG vi phạm và bỏ cờ (vẫn giữ tin nhắn)?');">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">Bỏ cờ</button></form>
<form class="d-inline" action="{{ route('admin.chat.group.destroy', $m->id) }}" method="POST" onsubmit="return confirm('Xóa tin nhắn vi phạm này?');">@csrf @method('DELETE')<button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button></form>
</td>
</tr>
@empty
<tr><td colspan="7" class="text-center text-muted py-4">Không có tin nhắn nhóm nào bị gắn cờ.</td></tr>
@endforelse
</tbody>
</table></div></div>
@if($flaggedGroup instanceof \Illuminate\Pagination\LengthAwarePaginator && $flaggedGroup->hasPages())
<div class="card-footer d-flex justify-content-end">{{ $flaggedGroup->links() }}</div>
@endif
</div>
@endif
<div class="row">
<div class="col-md-6"><div class="card mb-4">
<div class="card-header">Gửi thông báo tới 1 nhóm</div>
<div class="card-body">
<form method="POST" action="{{ route('admin.chat.broadcast.group') }}">
@csrf
<div class="mb-3"><label class="form-label">Nhóm</label><select name="group_id" class="form-select" required><option value="">-- Chọn nhóm --</option>@foreach($groups as $g)<option value="{{ $g->group_id }}">{{ $g->group_name }}</option>@endforeach</select></div>
<div class="mb-3"><label class="form-label">Tiêu đề</label><input name="title" class="form-control" maxlength="255" required></div>
<div class="mb-3"><label class="form-label">Nội dung</label><textarea name="message" class="form-control" rows="3" maxlength="2000" required></textarea></div>
<button class="btn btn-warning">Gửi tới nhóm</button>
</form>
</div>
</div></div>
<div class="col-md-6"><div class="card mb-4 border-danger">
<div class="card-header bg-danger text-white">Gửi toàn hệ thống</div>
<div class="card-body">
<form method="POST" action="{{ route('admin.chat.broadcast.all') }}" onsubmit="return confirm('Gửi tới TOÀN BỘ người dùng?');">
@csrf
<div class="mb-3"><label class="form-label">Tiêu đề</label><input name="title" class="form-control" maxlength="255" required></div>
<div class="mb-3"><label class="form-label">Nội dung</label><textarea name="message" class="form-control" rows="3" maxlength="2000" required></textarea></div>
<button class="btn btn-danger">Gửi toàn hệ thống</button>
</form>
</div>
</div></div>
</div>
</div>
@endsection
