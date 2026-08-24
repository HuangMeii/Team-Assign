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
                <a href="{{ route('invites.approve', $invite->id) }}" class="btn btn-success btn-sm">Duyệt</a>
                <a href="{{ route('invites.reject', $invite->id) }}" class="btn btn-danger btn-sm">Từ chối</a>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
