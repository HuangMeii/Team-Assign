<?php

use App\Events\DirectMessageSent;
use App\Models\User;
use Illuminate\Support\Facades\Event;

use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| P1 — Tối ưu hiệu năng: broadcast sau response + redirect trang chủ + mail timeout
|--------------------------------------------------------------------------
| - Sau D4: broadcast chat bọc AfterResponse::broadcast() ⇒ chạy ở phase
|   terminating (SAU khi response đã gửi) nhưng VẪN được dispatch trong cùng
|   request lifecycle (test assert được — đúng cơ chế của afterResponse).
| - HomeController (đóng closure routes): `/` redirect theo vai trò.
| - config/mail: smtp timeout = 10s (không còn null = treo vô hạn).
*/

it('chat 1-1: DirectMessageSent vẫn được dispatch (hoãn sau response)', function () {
    Event::fake([DirectMessageSent::class]);

    $sender = make_user('student', 'Sender P1');
    $receiver = make_user('student', 'Receiver P1');

    $this->actingAs($sender)
        ->postJson(route('chat.send', $receiver->user_id), ['content' => 'Tin P1'])
        ->assertOk();

    // event chưa dispatch trong handle() nhưng CÓ dispatch trong phase terminating
    // của CÙNG request ⇒ gọi tới đây đã thấy (MakesHttpRequests::call chạy terminate).
    Event::assertDispatched(DirectMessageSent::class, function ($event) use ($receiver) {
        return true;
    });
});

it('trang chủ `/` chuyển hướng theo vai trò (HomeController thay closure)', function () {
    // khách (chưa đăng nhập) — test mới bắt đầu luôn là guest
    $this->get('/')->assertRedirect('/login');

    $lecturer = make_user('lecturer', 'GV P1');
    $admin = make_user('admin', 'Admin P1');

    $this->actingAs($lecturer)->get('/')->assertRedirect(route('dashboard'));
    $this->actingAs($admin)->get('/')->assertRedirect(route('admin.users.index'));
});

it('smtp có timeout cấu hình được (mặc định 10s — không còn null)', function () {
    expect(config('mail.mailers.smtp.timeout'))->toBe(10);
});

it('không view nào còn tải asset từ CDN bên ngoài (chống tái phát)', function () {
    $offenders = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $html = file_get_contents($file->getPathname());
        if (str_contains($html, 'cdnjs.cloudflare.com') || str_contains($html, 'cdn.jsdelivr.net')) {
            $offenders[] = str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $file->getPathname());
        }
    }

    expect($offenders)->toBe([], 'Còn view tải CDN: ' . implode(', ', $offenders));
});