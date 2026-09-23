<?php

use App\Models\Topic_requests;
use App\Models\Topics;
use App\Services\GroupService;
use App\Services\TopicRegistrationService;
use Illuminate\Http\UploadedFile;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_topic;
use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| MỘT MÔN - HAI BÀI BÁO CÁO (giữa kì + cuối kì)
|--------------------------------------------------------------------------
| - subjects.report_count: 1 = chỉ cuối kì (mặc định) · 2 = giữa kì + cuối kì
| - topics.report_type:    final (cuối kì, mặc định) · midterm (giữa kì)
| - Nhóm của lớp làm CẢ 2 bài: tối đa 1 đề tài cho MỖI LOẠI, không bắt buộc thứ tự.
| - groups.topic_id giữ đề tài CUỐI KÌ; topic_requests.status = Accepted là nguồn sự thật.
| Cần MySQL test theo phpunit.xml.
*/

it('môn 2 bài: tạo được đề tài giữa kì và cuối kì', function () {
    $admin = make_user('admin', 'Admin RT1');
    $lecturer = make_user('lecturer', 'GV RT1');
    $subject = make_subject($lecturer);
    $subject->update(['report_count' => 2]);
    $class = make_class($subject, $lecturer);

    foreach (['midterm', 'final'] as $type) {
        $this->actingAs($admin)->post(route('topics.store'), [
            'name' => 'Đề tài RT ' . $type,
            'description' => 'Mô tả đủ dài cho đề tài ' . $type,
            'class_id' => $class->class_id,
            'min_members' => 2,
            'max_members' => 4,
            'report_type' => $type,
        ])->assertSessionHas('success');
    }

    expect(Topics::where('name', 'Đề tài RT midterm')->first()->report_type)->toBe('midterm')
        ->and(Topics::where('name', 'Đề tài RT final')->first()->report_type)->toBe('final');
});

it('môn 1 bài: report_type = midterm bị ép về cuối kì', function () {
    $admin = make_user('admin', 'Admin RT2');
    $lecturer = make_user('lecturer', 'GV RT2');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);

    $this->actingAs($admin)->post(route('topics.store'), [
        'name' => 'Đề tài ép final',
        'description' => 'Mô tả đủ dài cho đề tài ép final',
        'class_id' => $class->class_id,
        'report_type' => 'midterm',
    ])->assertSessionHas('success');

    expect(Topics::where('name', 'Đề tài ép final')->first()->report_type)->toBe('final');
});

it('nhóm đăng ký được 2 đề tài KHÁC LOẠI; groups.topic_id = đề tài cuối kì', function () {
    $lecturer = make_user('lecturer', 'GV RT3');
    $subject = make_subject($lecturer);
    $subject->update(['report_count' => 2]);
    $class = make_class($subject, $lecturer);

    $midterm = make_topic($class, $subject, 1, 4, null, 'midterm');
    $final = make_topic($class, $subject, 1, 4, null, 'final');

    $leader = make_user('student', 'Trưởng nhóm RT');
    $group = make_group($leader, $class);
    app(GroupService::class)->addMember($group, make_user('student', 'Thành viên RT'));

    $service = app(TopicRegistrationService::class);

    // 1) Đăng ký + duyệt đề tài GIỮA KÌ
    expect($service->register($group, $midterm, $leader)->succeeded())->toBeTrue();
    $reqMid = Topic_requests::where('group_id', $group->group_id)->where('topic_id', $midterm->topic_id)->first();
    expect($service->approve($reqMid, $lecturer)->succeeded())->toBeTrue();

    // 2) Vẫn đăng ký được đề tài CUỐI KÌ (khác loại)
    expect($service->register($group, $final, $leader)->succeeded())->toBeTrue();
    $reqFinal = Topic_requests::where('group_id', $group->group_id)->where('topic_id', $final->topic_id)->first();
    expect($service->approve($reqFinal, $lecturer)->succeeded())->toBeTrue();

    expect((int) $group->fresh()->topic_id)->toBe((int) $final->topic_id)
        ->and(Topic_requests::where('group_id', $group->group_id)->where('status', 'Accepted')->count())->toBe(2)
        ->and((int) $midterm->fresh()->assigned_group_id)->toBe((int) $group->group_id)
        ->and((int) $final->fresh()->assigned_group_id)->toBe((int) $group->group_id);
});

it('nhóm KHÔNG đăng ký được 2 đề tài CÙNG LOẠI', function () {
    $lecturer = make_user('lecturer', 'GV RT4');
    $subject = make_subject($lecturer);
    $subject->update(['report_count' => 2]);
    $class = make_class($subject, $lecturer);

    $finalA = make_topic($class, $subject, 1, 4, null, 'final');
    $finalB = make_topic($class, $subject, 1, 4, null, 'final');

    $leader = make_user('student', 'Trưởng nhóm RT4');
    $group = make_group($leader, $class);
    app(GroupService::class)->addMember($group, make_user('student', 'Thành viên RT4'));

    $service = app(TopicRegistrationService::class);

    expect($service->register($group, $finalA, $leader)->succeeded())->toBeTrue();
    $reqA = Topic_requests::where('group_id', $group->group_id)->where('topic_id', $finalA->topic_id)->first();
    expect($service->approve($reqA, $lecturer)->succeeded())->toBeTrue();

    expect($service->register($group, $finalB, $leader)->succeeded())->toBeFalse();
});

it('duyệt đề tài giữa kì KHÔNG làm rớt yêu cầu cuối kì đang chờ', function () {
    $lecturer = make_user('lecturer', 'GV RT5');
    $subject = make_subject($lecturer);
    $subject->update(['report_count' => 2]);
    $class = make_class($subject, $lecturer);

    $midterm = make_topic($class, $subject, 1, 4, null, 'midterm');
    $final = make_topic($class, $subject, 1, 4, null, 'final');

    $leader = make_user('student', 'Trưởng nhóm RT5');
    $group = make_group($leader, $class);
    app(GroupService::class)->addMember($group, make_user('student', 'Thành viên RT5'));

    $service = app(TopicRegistrationService::class);

    expect($service->register($group, $midterm, $leader)->succeeded())->toBeTrue();
    expect($service->register($group, $final, $leader)->succeeded())->toBeTrue();

    $reqMid = Topic_requests::where('group_id', $group->group_id)->where('topic_id', $midterm->topic_id)->first();
    expect($service->approve($reqMid, $lecturer)->succeeded())->toBeTrue();

    $reqFinal = Topic_requests::where('group_id', $group->group_id)->where('topic_id', $final->topic_id)->first();

    expect($reqFinal->fresh()->status)->toBe('Pending')
        ->and($reqMid->fresh()->status)->toBe('Accepted');
});

it('gán trực tiếp: giữa kì không đổi groups.topic_id, cuối kì thì đổi', function () {
    $lecturer = make_user('lecturer', 'GV RT6');
    $subject = make_subject($lecturer);
    $subject->update(['report_count' => 2]);
    $class = make_class($subject, $lecturer);

    $midterm = make_topic($class, $subject, 1, 4, null, 'midterm');
    $final = make_topic($class, $subject, 1, 4, null, 'final');

    $leader = make_user('student', 'Trưởng nhóm RT6');
    $group = make_group($leader, $class);

    $service = app(TopicRegistrationService::class);

    expect($service->assignDirectly($group, $midterm, $lecturer)->succeeded())->toBeTrue()
        ->and($group->fresh()->topic_id)->toBeNull();

    expect($service->assignDirectly($group, $final, $lecturer)->succeeded())->toBeTrue()
        ->and((int) $group->fresh()->topic_id)->toBe((int) $final->topic_id);
});

it('import đề tài: cột loai_bao_cao cho môn 2 bài ⇒ giữa kì; môn 1 bài ⇒ cuối kì', function () {
    $admin = make_user('admin', 'Admin RT7');
    $lecturer = make_user('lecturer', 'GV RT7');

    $subject2 = make_subject($lecturer);
    $subject2->update(['report_count' => 2]);
    $class2 = make_class($subject2, $lecturer);

    $subject1 = make_subject($lecturer);
    $class1 = make_class($subject1, $lecturer);

    $csv = "ten_de_tai,mo_ta,muc_tieu,yeu_cau,ma_lop,so_tv_min,so_tv_max,han_dang_ky,loai_bao_cao\n"
        . 'Đề tài GK import,Mô tả đủ dài cho đề tài GK,,,' . $class2->class_code . ",2,4,,giua_ki\n"
        . 'Đề tài GK mon 1 bai,Mô tả đủ dài cho đề tài này,,,' . $class1->class_code . ",2,4,,giua_ki\n";

    $this->actingAs($admin)->post(route('topics.import'), [
        'file' => UploadedFile::fake()->createWithContent('de_tai.csv', $csv),
    ])->assertSessionHas('success');

    expect(Topics::where('name', 'Đề tài GK import')->first()->report_type)->toBe('midterm')
        ->and(Topics::where('name', 'Đề tài GK mon 1 bai')->first()->report_type)->toBe('final');
});