@extends('layouts.app')

@section('content')
<h2>Lời mời vào nhóm</h2>

<table class="table mt-3">
    <thead>
        <tr>
            <th>Nhóm</th>
            <th>Người mời</th>
            <th>Người nhận</th>
            <th>Trạng thái</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($invites as $invite)
        <tr>
            <td>{{ $invite->group->group_name }}</td>
            <td>{{ $invite->invitedBy->name ?? '—' }}</td>
            <td>{{ $invite->member->name ?? '—' }}</td>
            <td>{{ $invite->status }}</td>
            <td>
                @if ($invite->status == 'Pending')
                    {{-- Bó-1 (fix): dùng ĐÚNG luồng POST đang hoạt động.
                         Trước đây là 2 link GET `invites.approve` (method không tồn tại ⇒ 500)
                         và `invites.reject` (GET đổi trạng thái ⇒ CSRF). --}}
                    <form action="{{ route('user.accept-invite', $invite->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm">Duyệt</button>
                    </form>
                    <form action="{{ route('user.reject-invite', $invite->id) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Từ chối lời mời này?');">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm">Từ chối</button>
                    </form>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
