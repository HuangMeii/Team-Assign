<?php

use App\Models\Group_Members;
use App\Models\Join_Requests;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_topic;
use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| Yêu cầu tham gia nhóm HẾT HIỆU LỰC phải tự bị dọn + ẩn khỏi UI
|--------------------------------------------------------------------------
| Quy tắc: 1 sinh viên chỉ thuộc 1 nhóm trong 1 lớp. Khi sinh viên đã có nhóm
| (hoặc nhóm đã đầy), mọi yêu cầu Pending khác trong cùng lớp phải chuyển
| Expired và:
|   - KHÔNG còn hiện ở tab mặc định ("Đang chờ") của trang Yêu cầu;
|   - KHÔNG còn hiện "Đang chờ duyệt" ở trang Nhóm của tôi;
|   - VẪN xem được ở tab "Hết hiệu lực" (giữ lịch sử trong DB).
| Cần MySQL test theo phpunit.xml.
*/

/**
 * Sinh viên đã tham gia lớp và tự tạo 1 nhóm trong lớp đó.
 *
 * @return array{0: \App\Models\ClassSection, 1: \App\Models\User, 2: \App\Models\Groups}
 */
function jre_student_with_group(string $suffix = ''): array
{
    $lecturer = make_user('lecturer', 'GV JRE' . $suffix);
    $class = make_class(make_subject($lecturer), $lecturer);

    $student = make_user('student', 'SV JRE' . $suffix);
    $class->users()->attach($student->user_id);

    $ownGroup = make_group($student, $class, 'Nhóm của tôi' . $suffix);

    return [$class, $student, $ownGroup];
}

/** Yêu cầu Pending "treo" của sinh viên gửi vào một nhóm khác. */
function jre_pending_request(int $groupId, int $memberId): Join_Requests
{
    return Join_Requests::create([
        'group_id'  => $groupId,
        'member_id' => $memberId,
        'status'    => 'Pending',
    ]);
}

it('sinh viên đã có nhóm: yêu cầu Pending cùng lớp tự chuyển Expired khi mở trang Yêu cầu', function () {
    [$class, $student] = jre_student_with_group(' A');

    $otherLeader = make_user('student', 'Trưởng nhóm khác JRE A');
    $otherGroup = make_group($otherLeader, $class, 'Nhóm khác JRE A');

    $request = jre_pending_request($otherGroup->group_id, $student->user_id);

    $this->actingAs($student)->get(route('user.join-requests'))->assertOk();

    expect($request->fresh()->status)->toBe('Expired');
});

it('tab mặc định (Đang chờ) KHÔNG còn liệt kê yêu cầu đã hết hiệu lực', function () {
    [$class, $student] = jre_student_with_group(' B');

    $otherLeader = make_user('student', 'Trưởng nhóm khác JRE B');
    $otherGroup = make_group($otherLeader, $class, 'Nhóm khác JRE B');

    jre_pending_request($otherGroup->group_id, $student->user_id);

    $this->actingAs($student)
        ->get(route('user.join-requests'))
        ->assertOk()
        ->assertDontSee($otherGroup->group_name);
});

it('tab "Hết hiệu lực" và tab "Tất cả" vẫn xem được lịch sử', function () {
    [$class, $student] = jre_student_with_group(' C');

    $otherLeader = make_user('student', 'Trưởng nhóm khác JRE C');
    $otherGroup = make_group($otherLeader, $class, 'Nhóm khác JRE C');

    jre_pending_request($otherGroup->group_id, $student->user_id);

    $this->actingAs($student)
        ->get(route('user.join-requests', ['status' => 'Expired']))
        ->assertOk()
        ->assertSee($otherGroup->group_name)
        ->assertSee('Hết hiệu lực');

    $this->actingAs($student)
        ->get(route('user.join-requests', ['status' => 'all']))
        ->assertOk()
        ->assertSee($otherGroup->group_name);
});

it('trang Nhóm của tôi không còn hiện "Đang chờ duyệt" khi sinh viên đã có nhóm trong lớp', function () {
    [$class, $student] = jre_student_with_group(' D');

    $otherLeader = make_user('student', 'Trưởng nhóm khác JRE D');
    $otherGroup = make_group($otherLeader, $class, 'Nhóm khác JRE D');

    jre_pending_request($otherGroup->group_id, $student->user_id);

    $this->actingAs($student)
        ->get(route('user.my_groups'))
        ->assertOk()
        ->assertSee('Bạn đã có nhóm lớp này')
        ->assertDontSee('Đang chờ duyệt');
});

it('yêu cầu còn hiệu lực KHÔNG bị dọn và vẫn hiện ở tab mặc định', function () {
    $lecturer = make_user('lecturer', 'GV JRE E');
    $class = make_class(make_subject($lecturer), $lecturer);

    $student = make_user('student', 'SV JRE E');
    $class->users()->attach($student->user_id);

    $leader = make_user('student', 'Trưởng nhóm JRE E');
    $group = make_group($leader, $class, 'Nhóm còn chỗ JRE E');

    $request = jre_pending_request($group->group_id, $student->user_id);

    $this->actingAs($student)
        ->get(route('user.join-requests'))
        ->assertOk()
        ->assertSee($group->group_name);

    expect($request->fresh()->status)->toBe('Pending');
});

it('nhóm đã đầy -> yêu cầu Pending của nhóm đó chuyển Expired', function () {
    $lecturer = make_user('lecturer', 'GV JRE F');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);

    // Đề tài của lớp quy định tối đa 2 thành viên => nhóm có 1 trưởng + 1 thành viên là ĐẦY
    make_topic($class, $subject, 2, 2);

    $student = make_user('student', 'SV JRE F');
    $class->users()->attach($student->user_id);

    $leader = make_user('student', 'Trưởng nhóm JRE F');
    $group = make_group($leader, $class, 'Nhóm đã đầy JRE F');

    $member = make_user('student', 'Thành viên JRE F');
    Group_Members::create([
        'group_id' => $group->group_id,
        'user_id'  => $member->user_id,
        'role'     => 'member',
    ]);

    $request = jre_pending_request($group->group_id, $student->user_id);

    $this->actingAs($student)->get(route('user.join-requests'))->assertOk();

    expect($request->fresh()->status)->toBe('Expired');
});
