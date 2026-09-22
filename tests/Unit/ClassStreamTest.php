<?php

use App\Models\ClassPost;
use App\Models\Groups;
use App\Services\ClassStreamService;

/*
|--------------------------------------------------------------------------
| Bảng tin lớp học — logic thuần (KHÔNG cần DB)
|--------------------------------------------------------------------------
| Test cần DB (ACL, đăng bài, bình luận, observer, backfill):
| tests/Feature/ClassStreamTest.php
*/

uses(Tests\TestCase::class);

it('mọi loại bài của bảng tin đều có nhãn + icon + màu', function () {
    foreach (array_keys(ClassPost::LABELS) as $type) {
        $post = new ClassPost(['type' => $type]);

        expect($post->label)->not->toBe('')
            ->and($post->icon)->toStartWith('fa-')
            ->and($post->color)->not->toBe('');
    }
});

it('loại bài lạ vẫn có nhãn/icon/màu mặc định (không lỗi)', function () {
    $post = new ClassPost(['type' => 'khong-ton-tai']);

    expect($post->label)->toBe('Hoạt động')
        ->and($post->icon)->toBe('fa-circle-info')
        ->and($post->color)->toBe('secondary');
});

it('phân biệt bài HỆ THỐNG (hoạt động nhóm) và bài do người đăng', function () {
    expect((new ClassPost(['user_id' => null]))->is_system)->toBeTrue()
        ->and((new ClassPost(['user_id' => 5]))->is_system)->toBeFalse();
});

it('mô tả hoạt động nhóm đúng theo từng loại', function () {
    $service = app(ClassStreamService::class);
    $group = new Groups(['group_name' => 'Nhóm Test']);

    expect($service->describeGroupActivity($group, 'group_created', ['actor_name' => 'An']))
        ->toContain('Nhóm Test')->toContain('An')
        ->and($service->describeGroupActivity($group, 'group_member_joined', ['member_name' => 'Bình']))
        ->toContain('Bình')->toContain('tham gia')
        ->and($service->describeGroupActivity($group, 'group_member_left', ['member_name' => 'Cường']))
        ->toContain('Cường')->toContain('rời nhóm')
        ->and($service->describeGroupActivity($group, 'group_topic', ['topic_name' => 'Web thư viện']))
        ->toContain('Web thư viện')
        ->and($service->describeGroupActivity($group, 'group_deleted'))
        ->toContain('giải tán');
});

it('hằng số giới hạn nội dung dùng chung với validate của controller', function () {
    expect(ClassStreamService::MAX_POST_LENGTH)->toBe(1000)
        ->and(ClassStreamService::MAX_COMMENT_LENGTH)->toBe(500)
        ->and(ClassStreamService::PER_PAGE)->toBe(10)
        ->and(ClassStreamService::PREVIEW_LIMIT)->toBe(3);
});
