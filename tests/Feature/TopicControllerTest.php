<?php

use App\Models\Topics;
use App\Models\Topic_requests;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_topic;
use function Tests\Support\make_user;

/*
 * Kiểm thử quy tắc quyền của TopicController (bug A2 - R6):
 * - Admin được tạo/sửa đề tài cho MỌI lớp (trước đây bị chặn vì $user->classes rỗng)
 * - Lecturer vẫn chỉ được tạo đề tài cho lớp mình phụ trách
 * - Admin tạo đề tài => "lecturer" lấy giảng viên phụ trách lớp (không phải tên admin)
 * - Form sửa đề tài KHÔNG cho phép thay đổi lớp học phần (giữ nguyên lớp & môn gốc)
 * - Đề tài đã có sinh viên đăng ký (Pending/Accepted) thì KHÔNG được chỉnh sửa nữa;
 *   yêu cầu đăng ký đã bị từ chối (Rejected) thì vẫn chỉnh sửa được
 */

beforeEach(function () {
    $this->admin = make_user('admin', 'Quản trị viên');
    $this->lecturer = make_user('lecturer', 'Giảng viên A');
    $this->otherLecturer = make_user('lecturer', 'Giảng viên B');
    $this->subject = make_subject($this->lecturer);
    $this->class = make_class($this->subject, $this->lecturer);
});

it('admin thấy danh sách lớp học phần trong form thêm đề tài', function () {
    $response = $this->actingAs($this->admin)->get(route('topics.create'));

    $response->assertStatus(200)
        ->assertSee($this->class->class_name);
});

it('admin tạo được đề tài cho lớp bất kỳ và ghi đúng giảng viên phụ trách lớp', function () {
    $response = $this->actingAs($this->admin)->post(route('topics.store'), [
        'name' => 'Đề tài do admin tạo ' . uniqid(),
        'description' => 'Mô tả đề tài phục vụ kiểm thử nghiệp vụ.',
        'class_id' => $this->class->class_id,
        'min_members' => 2,
        'max_members' => 4,
    ]);

    $response->assertRedirect(route('topics.index'));

    $topic = Topics::where('class_id', $this->class->class_id)->first();

    expect($topic)->not->toBeNull()
        ->and($topic->lecturer)->toBe($this->lecturer->name)
        ->and($topic->subject_id)->toBe($this->subject->subject_id);
});

it('admin sửa được đề tài nhưng không thể đổi lớp học phần', function () {
    $topic = make_topic($this->class, $this->subject, 2, 4);
    $otherClass = make_class($this->subject, $this->otherLecturer);

    $response = $this->actingAs($this->admin)->put(route('topics.update', $topic), [
        'name' => $topic->name,
        'description' => $topic->description,
        'class_id' => $otherClass->class_id, // cố tình gửi lớp khác
        'min_members' => 2,
        'max_members' => 4,
    ]);

    $response->assertRedirect(route('topics.index'));

    expect($topic->fresh()->class_id)->toBe($this->class->class_id)
        ->and($topic->fresh()->subject_id)->toBe($this->subject->subject_id)
        ->and($topic->fresh()->lecturer)->toBe($topic->lecturer); // giảng viên không đổi
});

it('admin thấy danh sách lớp học phần trong form sửa đề tài', function () {
    $topic = make_topic($this->class, $this->subject, 2, 4);

    $response = $this->actingAs($this->admin)->get(route('topics.edit', $topic));

    $response->assertStatus(200)
        ->assertSee($this->class->class_name);
});

it('giảng viên khác lớp không tạo được đề tài cho lớp không phụ trách', function () {
    $response = $this->actingAs($this->otherLecturer)->post(route('topics.store'), [
        'name' => 'Đề tài trái quyền ' . uniqid(),
        'description' => 'Mô tả đề tài phục vụ kiểm thử nghiệp vụ.',
        'class_id' => $this->class->class_id,
        'min_members' => 2,
        'max_members' => 4,
    ]);

    $response->assertRedirect()
        ->assertSessionHas('error', 'Bạn không có quyền tạo đề tài cho lớp này!');

    expect(Topics::where('class_id', $this->class->class_id)->count())->toBe(0);
});
it('admin thấy tên giảng viên thực sự của đề tài trong form sửa đề tài', function () {
    // Bug A3 [R4]: form sửa đề tài hiển $topic->lecturer, không Auth::user()->name
    $topic = make_topic($this->class, $this->subject, 2, 4);

    $response = $this->actingAs($this->admin)->get(route('topics.edit', $topic));

    $response->assertStatus(200)
        ->assertSee($topic->lecturer)
        ->assertDontSee($this->admin->name);
});

it('không vào được trang chỉnh sửa và không cập nhật được sau khi có sinh viên đăng ký đề tài', function () {
    $topic = make_topic($this->class, $this->subject, 2, 4);
    $leader = make_user('student', 'Trưởng nhóm');
    $group = make_group($leader, $this->class);
    Topic_requests::create([
        'topic_id' => $topic->topic_id,
        'group_id' => $group->group_id,
        'created_by' => $leader->user_id,
        'status' => 'Pending',
    ]);
    $oldName = $topic->name;
    $oldClassId = $topic->class_id;

    $this->actingAs($this->admin)->get(route('topics.edit', $topic))
        ->assertRedirect(route('topics.show', $topic))
        ->assertSessionHas('error', 'Đề tài đã có sinh viên đăng ký nên không thể chỉnh sửa!');

    $this->actingAs($this->admin)->put(route('topics.update', $topic), [
        'name' => 'Tên đề tài mới ' . uniqid(),
        'description' => 'Mô tả đề tài phục vụ kiểm thử nghiệp vụ.',
        'class_id' => $this->class->class_id,
        'min_members' => 2,
        'max_members' => 4,
    ])->assertRedirect(route('topics.show', $topic))
        ->assertSessionHas('error', 'Đề tài đã có sinh viên đăng ký nên không thể chỉnh sửa!');

    expect($topic->fresh()->name)->toBe($oldName)
        ->and($topic->fresh()->class_id)->toBe($oldClassId);
});

it('vẫn chỉnh sửa được khi yêu cầu đăng ký duy nhất đã bị từ chối', function () {
    $topic = make_topic($this->class, $this->subject, 2, 4);
    $leader = make_user('student', 'Trưởng nhóm');
    $group = make_group($leader, $this->class);
    Topic_requests::create([
        'topic_id' => $topic->topic_id,
        'group_id' => $group->group_id,
        'created_by' => $leader->user_id,
        'status' => 'Rejected',
    ]);
    $newName = 'Tên đề tài mới ' . uniqid();

    $response = $this->actingAs($this->admin)->put(route('topics.update', $topic), [
        'name' => $newName,
        'description' => 'Mô tả đề tài phục vụ kiểm thử nghiệp vụ.',
        'class_id' => $this->class->class_id,
        'min_members' => 2,
        'max_members' => 4,
    ]);

    $response->assertRedirect(route('topics.index'));

    expect($topic->fresh()->name)->toBe($newName)
        ->and($topic->fresh()->class_id)->toBe($this->class->class_id);
});
