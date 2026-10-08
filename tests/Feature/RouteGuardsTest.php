<?php

/*
|--------------------------------------------------------------------------
| Bó-1 — Route thiếu middleware `auth` (khách ⇒ HTTP 500) + route trỏ tới method không tồn tại
|--------------------------------------------------------------------------
|  - `GET /groups/{groupId}/chat`  : trước đây KHÔNG có middleware ⇒ khách gây 500
|    (`GroupsChatController::isAdmin()` đọc `Auth::user()->role`).
|  - `GET /invites`                : trước đây KHÔNG có middleware ⇒ không có phiên.
|  - `GET /invites/{id}/approve`   : trỏ tới `InviteController::approve()` KHÔNG tồn tại ⇒ 500.
|  - `GET /invites/{id}/reject`    : GET đổi trạng thái (CSRF) + `Auth::user()` null ⇒ 500.
|  - `GET /requests`               : render `view('requests')` KHÔNG tồn tại ⇒ 500.
*/

use App\Models\Invites;
use Illuminate\Support\Facades\Route;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

it('khách gọi chat nhóm: chuyển hướng đăng nhập thay vì HTTP 500', function () {
    $lecturer = make_user('lecturer', 'GV bó1');
    $class = make_class(make_subject($lecturer), $lecturer);
    $group = make_group(make_user('student', 'Leader bó1'), $class);

    $this->get(route('groups.chat.show', $group->group_id))
        ->assertRedirect(route('login'));
});

it('khách gọi /invites: chuyển hướng đăng nhập (trước đây thiếu middleware)', function () {
    $this->get(route('invites.index'))->assertRedirect(route('login'));
});

it('route /requests (view không tồn tại) đã được gỡ', function () {
    $this->get('/requests')->assertNotFound();
});

it('route duyệt/từ chối lời mời cũ (method không tồn tại / GET đổi trạng thái) đã được gỡ', function () {
    expect(Route::has('invites.approve'))->toBeFalse()
        ->and(Route::has('invites.reject'))->toBeFalse();

    $this->get('/invites/1/approve')->assertNotFound();
});

it('trang lời mời dùng form POST đúng luồng và duyệt lời mời hoạt động', function () {
    $lecturer = make_user('lecturer', 'GV bó1b');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Leader bó1b');
    $member = make_user('student', 'Thành viên bó1b');
    $class->users()->attach($member->user_id);
    $group = make_group($leader, $class);

    $invite = Invites::create([
        'group_id' => $group->group_id,
        'member_id' => $member->user_id,
        'invitedBy' => $leader->user_id,
        'status' => 'Pending',
    ]);

    $this->actingAs($member)->get(route('invites.index'))
        ->assertOk()
        ->assertSee(route('user.accept-invite', $invite->id), false)
        ->assertSee(route('user.reject-invite', $invite->id), false);

    $this->actingAs($member)
        ->post(route('user.accept-invite', $invite->id))
        ->assertRedirect();

    expect($invite->fresh()->status)->toBe('Accepted')
        ->and($group->members()->where('users.user_id', $member->user_id)->exists())->toBeTrue();
});

it('Bó-4: route dashboard không còn bị nhân đôi URL và admin/classes chỉ khai báo 1 lần', function () {
    // Trước đây có 2 route cùng tên `dashboard` ⇒ route('dashboard') = /dashboard/dashboard
    expect(route('dashboard', [], false))->toBe('/dashboard');

    $duplicates = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($route) => $route->getName() === 'admin.classes.index');

    expect($duplicates)->toHaveCount(1);
});
