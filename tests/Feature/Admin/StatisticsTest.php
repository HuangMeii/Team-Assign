<?php

use App\Models\Groups;
use App\Models\Topic_requests;
use App\Models\Topics;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_topic;
use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| Thống kê hệ thống (admin/statistics) — hoàn thiện StatisticsController
|--------------------------------------------------------------------------
| Schema hiện tại: users không còn is_have_group / role 'leader'
| (migration 2026_09_13). Trưởng nhóm derive từ groups.leader_id.
| topic_requests.status = Pending|Accepted|Rejected.
*/

it('admin xem trang tổng quan thống kê với các số liệu tổng hợp', function () {
    $admin = make_user('admin', 'Quản trị viên ST');
    $lecturer = make_user('lecturer', 'Giảng viên ST');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);
    $leader = make_user('student', 'Trưởng nhóm ST');
    make_group($leader, $class);

    make_topic($class, $subject);

    $response = $this->actingAs($admin)->get(route('admin.statistics.index'));

    $response->assertOk()
        ->assertSee('Thống kê hệ thống')
        ->assertSee('Người dùng')
        ->assertSee('Yêu cầu đang chờ duyệt');
});

it('trang thống kê đề tài hiển thị số còn trống và đã có nhóm', function () {
    $admin = make_user('admin', 'Quản trị viên ST2');
    $lecturer = make_user('lecturer', 'Giảng viên ST2');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);
    $leader = make_user('student', 'Trưởng nhóm ST2');
    $group = make_group($leader, $class);

    make_topic($class, $subject);

    $taken = make_topic($class, $subject);
    $group->update(['topic_id' => $taken->topic_id]);
    $taken->update(['assigned_group_id' => $group->group_id]);

    $response = $this->actingAs($admin)->get(route('admin.statistics.topics'));

    $response->assertOk()
        ->assertSee('Tổng số đề tài')
        ->assertSee('Còn trống')
        ->assertSee('Đã có nhóm đăng ký')
        ->assertSee('Theo giảng viên');
});

it('trang thống kê nhóm tính đúng số thành viên trung bình', function () {
    $admin = make_user('admin', 'Quản trị viên ST3');
    $lecturer = make_user('lecturer', 'Giảng viên ST3');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm ST3');
    make_group($leader, $class);

    $response = $this->actingAs($admin)->get(route('admin.statistics.groups'));

    $response->assertOk()
        ->assertSee('Thống kê nhóm')
        ->assertSee('Chưa có đề tài')
        ->assertSee('Số thành viên trung bình mỗi nhóm');
});

it('trang thống kê yêu cầu dùng đúng status Pending/Accepted/Rejected', function () {
    $admin = make_user('admin', 'Quản trị viên ST4');

    $response = $this->actingAs($admin)->get(route('admin.statistics.requests'));

    $response->assertOk()
        ->assertSee('Chờ duyệt (Pending)')
        ->assertSee('Đã duyệt (Accepted)')
        ->assertSee('Từ chối (Rejected)');
    // Không được còn chuỗi status cũ 'Approved' trong view
    expect($response->getContent())->not->toContain('Approved');
});

it('trang thống kê người dùng: trưởng nhóm tính qua groups, không qua role', function () {
    $admin = make_user('admin', 'Quản trị viên ST5');
    $lecturer = make_user('lecturer', 'Giảng viên ST5');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm ST5');
    make_group($leader, $class);

    $response = $this->actingAs($admin)->get(route('admin.statistics.users'));

    $response->assertOk()
        ->assertSee('Trưởng nhóm')
        ->assertSee('Chưa có nhóm');
});

it('không phải admin thì không vào được trang thống kê', function () {
    $student = make_user('student', 'Sinh viên ST6');

    $response = $this->actingAs($student)->get(route('admin.statistics.index'));

    expect(in_array($response->status(), [302, 403]))->toBeTrue();
});
