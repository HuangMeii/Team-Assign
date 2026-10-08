<?php

/*
|--------------------------------------------------------------------------
| L10 — Admin/GV xóa MỀM nhóm + nhả đề tài + SV tạo/tham gia nhóm mới
|--------------------------------------------------------------------------
| Chốt: xóa mềm (`deleted_at`), giữ dữ liệu (khớp chat/bảng tin), đề tài về
| "chưa đăng ký", SV của nhóm đã xóa được tạo/tham gia nhóm mới, có khôi phục
| và xóa vĩnh viễn (chỉ Admin).
*/

use App\Models\Groups;
use App\Models\Invites;
use App\Models\Topic_requests;
use App\Models\Topics;
use App\Services\GroupService;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_topic;
use function Tests\Support\make_user;

beforeEach(function () {
    $this->admin = make_user('admin', 'Admin nhóm');
    $this->lecturer = make_user('lecturer', 'GV phụ trách');
    $this->otherLecturer = make_user('lecturer', 'GV khác');
    $this->subject = make_subject($this->lecturer);
    $this->class = make_class($this->subject, $this->lecturer);

    $this->leader = make_user('student', 'Trưởng nhóm L10');
    $this->member = make_user('student', 'Thành viên L10');
    $this->class->users()->attach([$this->leader->user_id, $this->member->user_id]);

    $this->group = make_group($this->leader, $this->class, 'Nhóm L10');
    $this->group->members()->attach($this->member->user_id);
});

it('Admin xóa mềm nhóm: ẩn khỏi danh sách nhưng DB còn nguyên dữ liệu', function () {
    $this->actingAs($this->admin)
        ->delete(route('groups.destroy', $this->group->group_id))
        ->assertSessionHas('success');

    expect(Groups::where('group_id', $this->group->group_id)->exists())->toBeFalse()
        ->and(Groups::withTrashed()->find($this->group->group_id))->not->toBeNull()
        ->and(Groups::withTrashed()->find($this->group->group_id)->deleted_at)->not->toBeNull();

    expect($this->group->members()->where('users.user_id', $this->member->user_id)->exists())->toBeTrue();

    $this->actingAs($this->admin)->get(route('groups.index'))
        ->assertOk()
        ->assertDontSee('Nhóm L10');

    $this->actingAs($this->admin)->get(route('groups.index', ['trashed' => 1]))
        ->assertOk()
        ->assertSee('Nhóm L10')
        ->assertSee('Khôi phục');
});

it('Giảng viên PHỤ TRÁCH lớp xóa được, giảng viên KHÁC lớp bị chặn', function () {
    $this->actingAs($this->otherLecturer)
        ->delete(route('groups.destroy', $this->group->group_id))
        ->assertSessionHas('error');

    expect(Groups::where('group_id', $this->group->group_id)->exists())->toBeTrue();

    $this->actingAs($this->lecturer)
        ->delete(route('groups.destroy', $this->group->group_id))
        ->assertSessionHas('success');

    expect(Groups::where('group_id', $this->group->group_id)->exists())->toBeFalse();
});

it('Sinh viên không xóa được nhóm', function () {
    $this->actingAs($this->leader)
        ->delete(route('groups.destroy', $this->group->group_id))
        ->assertSessionHas('error');

    expect(Groups::where('group_id', $this->group->group_id)->exists())->toBeTrue();
});

it('Trang danh sách nhóm yêu cầu đăng nhập (khách bị chuyển về login)', function () {
    $this->get(route('groups.index'))->assertRedirect(route('login'));
});

it('Xóa nhóm nhả đề tài về chưa đăng ký và hủy lời mời/yêu cầu đang treo', function () {
    $topic = make_topic($this->class, $this->subject);
    $topic->update(['assigned_group_id' => $this->group->group_id]);
    $this->group->update(['topic_id' => $topic->topic_id]);

    Topic_requests::create([
        'topic_id' => $topic->topic_id,
        'group_id' => $this->group->group_id,
        'status' => 'Accepted',
        'created_by' => $this->leader->user_id,
    ]);

    Invites::create([
        'group_id' => $this->group->group_id,
        'member_id' => $this->member->user_id,
        'invitedBy' => $this->leader->user_id,
        'status' => 'Pending',
    ]);

    $this->actingAs($this->admin)
        ->delete(route('groups.destroy', $this->group->group_id))
        ->assertSessionHas('success');

    // Đề tài trở về "chưa đăng ký" (nhóm khác đăng ký lại được).
    // LƯU Ý: `fresh()` trên model đã xóa mềm trả về null ⇒ dùng withTrashed().
    $deletedGroup = Groups::withTrashed()->find($this->group->group_id);
    expect($deletedGroup)->not->toBeNull()
        ->and($deletedGroup->topic_id)->toBeNull()
        ->and(Topics::find($topic->topic_id)->assigned_group_id)->toBeNull();

    // Lịch sử đăng ký GIỮ LẠI (không xóa cứng) nhưng chuyển Cancelled.
    $topicRequest = Topic_requests::where('group_id', $this->group->group_id)->first();
    expect($topicRequest)->not->toBeNull()
        ->and($topicRequest->status)->toBe('Cancelled')
        ->and($topicRequest->rejection_reason)->toContain('Nhóm đã bị xóa');

    // Lời mời treo chuyển Expired (vẫn còn dòng để đối chiếu).
    expect(Invites::where('group_id', $this->group->group_id)->first()->status)->toBe('Expired');
});

it('Sinh viên của nhóm đã xóa được coi như chưa có nhóm và tạo được nhóm mới', function () {
    $service = app(GroupService::class);
    $service->destroy($this->group, $this->admin);

    expect($service->hasGroupInClass($this->leader->fresh(), $this->class->class_id))->toBeFalse();

    $result = $service->createGroupByStudent($this->leader->fresh(), 'Nhóm mới L10', $this->class->class_id);

    expect($result->succeeded())->toBeTrue()
        ->and(Groups::where('class_id', $this->class->class_id)->count())->toBe(1);
});

it('Khôi phục nhóm: nhóm trở lại nhưng KHÔNG tự lấy lại đề tài cũ', function () {
    $topic = make_topic($this->class, $this->subject);
    $this->group->update(['topic_id' => $topic->topic_id]);

    $this->actingAs($this->admin)->delete(route('groups.destroy', $this->group->group_id));

    $this->actingAs($this->admin)
        ->post(route('groups.restore', $this->group->group_id))
        ->assertSessionHas('success');

    $restored = Groups::find($this->group->group_id);

    expect($restored)->not->toBeNull()
        ->and($restored->deleted_at)->toBeNull()
        // KHÔNG tự lấy lại đề tài cũ (đề tài đã nhả về "chưa đăng ký" khi xóa).
        ->and($restored->topic_id)->toBeNull();

    $this->actingAs($this->admin)->get(route('groups.index'))
        ->assertOk()
        ->assertSee('Nhóm L10');
});

it('Xóa vĩnh viễn: giảng viên bị chặn, chỉ Admin xóa cứng được', function () {
    $this->actingAs($this->admin)->delete(route('groups.destroy', $this->group->group_id));

    $this->actingAs($this->lecturer)
        ->delete(route('groups.force-delete', $this->group->group_id))
        ->assertSessionHas('error');

    expect(Groups::withTrashed()->find($this->group->group_id))->not->toBeNull();

    $this->actingAs($this->admin)
        ->delete(route('groups.force-delete', $this->group->group_id))
        ->assertSessionHas('success');

    expect(Groups::withTrashed()->find($this->group->group_id))->toBeNull();
});
