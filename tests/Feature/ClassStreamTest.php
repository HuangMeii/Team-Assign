<?php

use App\Models\ClassPost;
use App\Models\ClassPostComment;
use App\Models\Groups;
use App\Models\Topic_requests;
use App\Services\ClassStreamService;
use App\Services\GroupService;
use App\Services\TopicRegistrationService;
use Illuminate\Support\Facades\DB;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_topic;
use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| Bảng tin lớp học (kiểu Google Classroom) — Feature test (cần MySQL test)
|--------------------------------------------------------------------------
| Bao gồm: ACL, đăng thông báo + notify sinh viên, hoạt động nhóm tự động,
| bình luận/reply, ghim/xoá, backfill idempotent, fail-open.
*/

beforeEach(function () {
    $this->lecturer = make_user('lecturer', 'Giảng viên A');
    $this->otherLecturer = make_user('lecturer', 'Giảng viên B');
    $this->subject = make_subject($this->lecturer);

    $this->class = make_class($this->subject, $this->lecturer);
    $this->otherClass = make_class($this->subject, $this->otherLecturer);

    $this->student = make_user('student', 'Sinh viên A');
    $this->student2 = make_user('student', 'Sinh viên B');
    $this->outsider = make_user('student', 'Sinh viên ngoài lớp');

    $this->class->students()->attach($this->student->user_id);
    $this->class->students()->attach($this->student2->user_id);
    $this->otherClass->students()->attach($this->outsider->user_id);

    $this->stream = app(ClassStreamService::class);
});

it('giảng viên phụ trách đăng thông báo: 1 bài + sinh viên trong lớp nhận thông báo', function () {
    $this->actingAs($this->lecturer)
        ->post(route('class.stream.store', $this->class->class_id), [
            'title' => 'Nộp báo cáo tuần này',
            'content' => 'Các nhóm nộp báo cáo tiến độ trước Chủ nhật nhé.',
        ])
        ->assertRedirect();

    expect(ClassPost::where('class_id', $this->class->class_id)->count())->toBe(1);

    $announcement = ClassPost::first();
    expect($announcement->type)->toBe('announcement')
        ->and($announcement->user_id)->toBe($this->lecturer->user_id);

    // 2 sinh viên của lớp nhận thông báo, sinh viên lớp khác KHÔNG nhận
    expect(DB::table('notifications')->where('type', 'class_announcement')->count())->toBe(2)
        ->and(DB::table('notifications')->where('user_id', $this->student->user_id)->count())->toBe(1)
        ->and(DB::table('notifications')->where('user_id', $this->outsider->user_id)->count())->toBe(0);
});

it('sinh viên và giảng viên không phụ trách lớp KHÔNG được đăng thông báo', function () {
    $this->actingAs($this->student)
        ->post(route('class.stream.store', $this->class->class_id), ['content' => 'Em xin đăng bài'])
        ->assertStatus(403);

    $this->actingAs($this->otherLecturer)
        ->post(route('class.stream.store', $this->class->class_id), ['content' => 'GV lớp khác đăng bài'])
        ->assertStatus(403);

    expect(ClassPost::count())->toBe(0);
});

it('sinh viên trong lớp xem được bảng tin, người ngoài lớp bị chặn', function () {
    $this->stream->postAnnouncement($this->class, $this->lecturer, 'Thông báo cho lớp A');

    $this->actingAs($this->student)
        ->get(route('class.stream', $this->class->class_id))
        ->assertOk()
        ->assertSee('Thông báo cho lớp A');

    $this->actingAs($this->outsider)
        ->get(route('class.stream', $this->class->class_id))
        ->assertStatus(403);
});

it('tạo nhóm mới ⇒ tự sinh bài group_created trong bảng tin lớp', function () {
    $result = app(GroupService::class)->createGroupByStudent($this->student, 'Nhóm Một', $this->class->class_id);

    expect($result->succeeded())->toBeTrue();

    $post = ClassPost::where('class_id', $this->class->class_id)->where('type', 'group_created')->first();

    expect($post)->not->toBeNull()
        ->and($post->user_id)->toBeNull()                       // bài hệ thống
        ->and($post->group_id)->toBe($result->data()->group_id)
        ->and($post->content)->toContain('Nhóm Một');
});

it('thêm thành viên vào nhóm ⇒ bài group_member_joined', function () {
    $group = make_group($this->student, $this->class);

    app(GroupService::class)->addMember($group, $this->student2);

    $posts = ClassPost::where('type', 'group_member_joined')->where('group_id', $group->group_id)->get();

    expect($posts)->toHaveCount(1)
        ->and($posts->first()->content)->toContain('Sinh viên B');
});

it('duyệt đề tài ⇒ bài group_topic (đúng 1 bài, không lặp)', function () {
    $group = make_group($this->student, $this->class);
    $topic = make_topic($this->class, $this->subject);

    $request = Topic_requests::create([
        'topic_id' => $topic->topic_id,
        'group_id' => $group->group_id,
        'created_by' => $this->student->user_id,
        'status' => 'Pending',
    ]);

    $result = app(TopicRegistrationService::class)->approve($request, $this->lecturer);

    expect($result->succeeded())->toBeTrue();

    $posts = ClassPost::where('type', 'group_topic')->where('group_id', $group->group_id)->get();

    expect($posts)->toHaveCount(1)
        ->and($posts->first()->content)->toContain($topic->name)
        ->and($posts->first()->topic_id)->toBe($topic->topic_id);
});

it('ghim bài ⇒ bài ghim đứng đầu bảng tin; xoá bài ⇒ mất khỏi bảng tin', function () {
    $old = $this->stream->postAnnouncement($this->class, $this->lecturer, 'Bài cũ');
    $new = $this->stream->postAnnouncement($this->class, $this->lecturer, 'Bài mới');

    // Chưa ghim: bài mới nhất đứng đầu
    expect($this->stream->feed($this->class)->first()->post_id)->toBe($new->post_id);

    $this->actingAs($this->lecturer)
        ->patch(route('class.stream.pin', [$this->class->class_id, $old->post_id]))
        ->assertRedirect();

    expect($old->fresh()->is_pinned)->toBeTrue()
        ->and($this->stream->feed($this->class)->first()->post_id)->toBe($old->post_id);

    $this->actingAs($this->lecturer)
        ->delete(route('class.stream.destroy', [$this->class->class_id, $new->post_id]))
        ->assertRedirect();

    expect(ClassPost::find($new->post_id))->toBeNull();
});

it('bình luận của sinh viên ⇒ giảng viên nhận thông báo; hỗ trợ trả lời và xoá đúng quyền', function () {
    $post = $this->stream->postAnnouncement($this->class, $this->lecturer, 'Thông báo A');

    // Sinh viên bình luận
    $this->actingAs($this->student)
        ->post(route('class.stream.comment', [$this->class->class_id, $post->post_id]), ['content' => 'Em đã hiểu ạ'])
        ->assertRedirect();

    expect($post->fresh()->comments_count)->toBe(1)
        ->and(DB::table('notifications')->where('user_id', $this->lecturer->user_id)->where('type', 'class_comment')->count())->toBe(1);

    $comment = ClassPostComment::first();

    // Giảng viên trả lời bình luận đó
    $this->actingAs($this->lecturer)
        ->post(route('class.stream.comment', [$this->class->class_id, $post->post_id]), [
            'content' => 'Tốt lắm, tiếp tục nhé.',
            'parent_id' => $comment->comment_id,
        ])
        ->assertRedirect();

    expect(ClassPostComment::whereNotNull('parent_id')->count())->toBe(1)
        ->and($post->fresh()->comments_count)->toBe(2);

    // Sinh viên khác không được xoá bình luận của người khác
    $this->actingAs($this->student2)
        ->delete(route('class.stream.comment.destroy', [$this->class->class_id, $comment->comment_id]))
        ->assertStatus(403);

    // Chính chủ xoá được
    $this->actingAs($this->student)
        ->delete(route('class.stream.comment.destroy', [$this->class->class_id, $comment->comment_id]))
        ->assertOk();

    expect(ClassPostComment::find($comment->comment_id))->toBeNull()
        ->and($post->fresh()->comments_count)->toBe(1);
});

it('backfill: chạy lần đầu tạo bài, chạy lần hai KHÔNG nhân đôi (idempotent)', function () {
    $group = make_group($this->student, $this->class);
    $group->members()->attach($this->student2->user_id, ['role' => 'member']);

    $this->artisan('class-stream:backfill')->assertExitCode(0);
    $afterFirst = ClassPost::count();

    expect($afterFirst)->toBeGreaterThan(0);

    $this->artisan('class-stream:backfill')->assertExitCode(0);

    expect(ClassPost::count())->toBe($afterFirst);
});

it('fail-open: lỗi khi ghi bảng tin KHÔNG làm hỏng việc tạo nhóm', function () {
    ClassPost::creating(function () {
        throw new RuntimeException('boom');
    });

    $result = app(GroupService::class)->createGroupByStudent($this->student, 'Nhóm fail-open', $this->class->class_id);

    expect($result->succeeded())->toBeTrue()
        ->and(Groups::where('group_name', 'Nhóm fail-open')->exists())->toBeTrue()
        ->and(ClassPost::count())->toBe(0);
});
