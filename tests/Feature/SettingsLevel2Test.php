<?php

/*
|--------------------------------------------------------------------------
| L09 (Mức 2) — Thiết lập tài khoản chuẩn SaaS
|--------------------------------------------------------------------------
| 2.1 Bảo mật   : lịch sử đăng nhập (+ cảnh báo IP mới), đăng xuất phiên khác,
|                  thu hồi "ghi nhớ đăng nhập".
| 2.2 Hồ sơ     : avatar (upload/xoá/fallback), ngôn ngữ vi/en, múi giờ.
| 2.3 Riêng tư  : danh sách chặn + bỏ chặn, ẩn trạng thái online,
|                  ai được mời mình vào nhóm (everyone | classmates | none).
*/

use App\Models\BlockedUser;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\PresenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

/* ------------------------------- 2.2 HỒ SƠ ------------------------------- */

it('upload ảnh đại diện, hiển thị avatar_url và xoá được ảnh', function () {
    Storage::fake('public');

    $user = make_user('student', 'Sinh viên avatar');

    $this->actingAs($user)->post(route('users.settings.profile.update'), [
        'locale' => 'vi',
        'avatar' => \Illuminate\Http\UploadedFile::fake()->image('me.jpg', 120, 120),
    ])->assertSessionHas('success');

    $fresh = $user->fresh();
    expect($fresh->avatar_path)->not->toBeNull()
        ->and(Storage::disk('public')->exists($fresh->avatar_path))->toBeTrue()
        ->and($fresh->avatar_url)->toContain($fresh->avatar_path);

    $this->actingAs($fresh)->post(route('users.settings.avatar.destroy'))
        ->assertSessionHas('success');

    expect($user->fresh()->avatar_path)->toBeNull()
        ->and($user->fresh()->avatar_url)->toBeNull();
});

it('hồ sơ: lưu ngôn ngữ vi/en + múi giờ và áp dụng cho request sau', function () {
    $user = make_user('student', 'Sinh viên locale');
    $user->update(['locale' => 'en', 'timezone' => 'Asia/Bangkok']);

    $this->actingAs($user)->get(route('users.settings.profile'))->assertOk();

    // Middleware SetLocale áp dụng cho MỌI request.
    expect(app()->getLocale())->toBe('en');
});

it('Bó-2: múi giờ người dùng KHÔNG làm lệch mốc thời gian ghi vào DB (chỉ dùng để hiển thị)', function () {
    $user = make_user('student', 'Sinh viên tz');
    $user->update(['timezone' => 'Asia/Bangkok']); // UTC+7

    // 1) Đăng nhập (middleware chạy khi CHƯA có user) → ghi lịch sử đăng nhập.
    $this->post('/login', ['email' => $user->email, 'password' => 'password'], ['REMOTE_ADDR' => '9.9.9.9'])
        ->assertRedirect();

    $history = LoginHistory::where('user_id', $user->user_id)->firstOrFail();

    // Nếu timezone bị áp TOÀN CỤC (lỗi cũ) thì created_at lệch ~7 giờ so với now() (UTC).
    expect($history->created_at->diffInMinutes(now()))->toBeLessThan(2)
        ->and(date_default_timezone_get())->toBe(config('app.timezone'));

    // 2) Request CÓ người dùng có timezone: mặc định PHP vẫn là múi giờ ứng dụng,
    //    còn múi giờ người dùng chỉ được ghi nhận cho phần HIỂN THỊ.
    $this->actingAs($user)->get(route('users.settings.security'))->assertOk();

    expect(date_default_timezone_get())->toBe(config('app.timezone'))
        ->and(config('app.display_timezone'))->toBe('Asia/Bangkok');

    // 3) Bản ghi sinh ra TRONG request của user đó (last_seen_at qua UpdateLastSeen)
    //    cũng phải theo UTC, không bị +7 giờ.
    $lastSeen = $user->fresh()->last_seen_at;

    expect($lastSeen)->not->toBeNull()
        ->and($lastSeen->diffInMinutes(now()))->toBeLessThan(2);
});

it('Bó-2: trang Bảo mật hiển thị lịch sử theo múi giờ người dùng và không lỗi', function () {
    $user = make_user('student', 'Sinh viên tz2');
    $user->update(['timezone' => 'Asia/Bangkok']);

    LoginHistory::create([
        'user_id' => $user->user_id,
        'ip_address' => '8.8.8.8',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120',
    ]);

    $this->actingAs($user)->get(route('users.settings.security'))
        ->assertOk()
        ->assertSee('Asia/Bangkok')      // nhãn múi giờ đang dùng
        ->assertSee('8.8.8.8')
        ->assertSee('Chrome');
});

it('hồ sơ: từ chối ngôn ngữ và múi giờ không hợp lệ', function () {
    $user = make_user('student', 'Sinh viên locale 2');

    $this->actingAs($user)->post(route('users.settings.profile.update'), [
        'locale' => 'fr',
        'timezone' => 'Khong/Ton_Tai',
    ])->assertSessionHasErrors(['locale', 'timezone']);

    expect($user->fresh()->locale)->not->toBe('fr');
});

/* ------------------------------ 2.3 RIÊNG TƯ ----------------------------- */

it('riêng tư: ẩn trạng thái online được PresenceService tôn trọng', function () {
    $user = make_user('student', 'Sinh viên ẩn');
    $user->update(['last_seen_at' => now()]);

    $presence = app(PresenceService::class);
    expect($presence->isOnline($user->fresh()))->toBeTrue();

    $this->actingAs($user)->post(route('users.settings.privacy.update'), [
        'invite_policy' => 'everyone',
        'hide_online' => '1',
    ])->assertSessionHas('success');

    $fresh = $user->fresh();
    expect($fresh->hide_online)->toBeTrue()
        ->and($presence->isOnline($fresh))->toBeFalse()
        ->and($presence->label($fresh))->toBe('Ẩn');
});

it('riêng tư: lưu danh sách chặn và bỏ chặn ngay trong Cài đặt', function () {
    $user = make_user('student', 'Sinh viên chặn');
    $other = make_user('student', 'Sinh viên bị chặn');

    BlockedUser::create(['blocker_id' => $user->user_id, 'blocked_id' => $other->user_id]);

    $this->actingAs($user)->get(route('users.settings.privacy'))
        ->assertOk()
        ->assertSee($other->name)
        ->assertSee('Bỏ chặn');

    $this->actingAs($user)->post(route('users.settings.unblock'), ['user_id' => $other->user_id])
        ->assertSessionHas('success');

    expect(BlockedUser::where('blocker_id', $user->user_id)->count())->toBe(0);
});

it('riêng tư: invite_policy=none chặn mọi lời mời vào nhóm', function () {
    $lecturer = make_user('lecturer', 'Giảng viên policy');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm policy');
    $group = make_group($leader, $class);
    $invitee = make_user('student', 'Sinh viên từ chối lời mời');
    $invitee->update(['invite_policy' => User::INVITE_NONE]);

    $result = app(InvitationService::class)->sendInvite($group, $leader, $invitee->user_id);

    expect($result->succeeded())->toBeFalse()
        ->and($result->message())->toContain('tắt nhận lời mời');
});

it('riêng tư: invite_policy=classmates chỉ cho bạn cùng lớp được mời', function () {
    $lecturer = make_user('lecturer', 'Giảng viên policy 2');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);
    $leader = make_user('student', 'Trưởng nhóm policy 2');
    $group = make_group($leader, $class);

    // Sinh viên CÙNG lớp (đang học)
    $classmate = make_user('student', 'Bạn cùng lớp');
    $class->users()->attach($classmate->user_id);
    $classmate->update(['invite_policy' => User::INVITE_CLASSMATES]);

    // Sinh viên KHÁC lớp
    $outsider = make_user('student', 'Người ngoài lớp');
    $outsider->update(['invite_policy' => User::INVITE_CLASSMATES]);

    $service = app(InvitationService::class);

    expect($service->sendInvite($group, $leader, $classmate->user_id)->succeeded())->toBeTrue();

    $blocked = $service->sendInvite($group, $leader, $outsider->user_id);
    expect($blocked->succeeded())->toBeFalse()
        ->and($blocked->message())->toContain('bạn cùng lớp');
});

/* ------------------------------ 2.1 BẢO MẬT ------------------------------ */

it('bảo mật: đăng nhập được ghi vào lịch sử và cảnh báo khi IP mới', function () {
    $user = make_user('student', 'Sinh viên lịch sử');

    // Lần đầu (IP lạ) => ghi lịch sử + cảnh báo.
    $this->post('/login', ['email' => $user->email, 'password' => 'password'], ['REMOTE_ADDR' => '1.1.1.1'])
        ->assertRedirect()
        ->assertSessionHas('warning');

    expect(LoginHistory::where('user_id', $user->user_id)->count())->toBe(1)
        ->and(LoginHistory::first()->ip_address)->toBe('1.1.1.1');

    $this->post(route('logout'));

    // Cùng IP => KHÔNG còn cảnh báo.
    $this->post('/login', ['email' => $user->email, 'password' => 'password'], ['REMOTE_ADDR' => '1.1.1.1'])
        ->assertRedirect()
        ->assertSessionMissing('warning');

    $this->post(route('logout'));

    // IP mới => cảnh báo lại.
    $this->post('/login', ['email' => $user->email, 'password' => 'password'], ['REMOTE_ADDR' => '2.2.2.2'])
        ->assertRedirect()
        ->assertSessionHas('warning');

    // Trang Bảo mật hiển thị lịch sử (IP + tổng số lần).
    $this->actingAs($user)->get(route('users.settings.security'))
        ->assertOk()
        ->assertSee('1.1.1.1')
        ->assertSee('Lịch sử đăng nhập');
});

it('bảo mật: đăng xuất các phiên khác chỉ xoá phiên của chính mình', function () {
    $user = make_user('student', 'Sinh viên phiên');
    $other = make_user('student', 'Người khác phiên');

    DB::table('sessions')->insert([
        ['id' => 'sess-a', 'user_id' => $user->user_id, 'ip_address' => '1.1.1.1', 'user_agent' => 'UA', 'payload' => 'x', 'last_activity' => time()],
        ['id' => 'sess-b', 'user_id' => $user->user_id, 'ip_address' => '1.1.1.2', 'user_agent' => 'UA', 'payload' => 'y', 'last_activity' => time()],
        ['id' => 'sess-other', 'user_id' => $other->user_id, 'ip_address' => '3.3.3.3', 'user_agent' => 'UA', 'payload' => 'z', 'last_activity' => time()],
    ]);

    $this->actingAs($user)->post(route('users.settings.revoke-sessions'))
        ->assertSessionHas('success');

    expect(DB::table('sessions')->where('user_id', $user->user_id)->count())->toBe(0)
        // Phiên của người khác KHÔNG bị đụng tới.
        ->and(DB::table('sessions')->where('user_id', $other->user_id)->count())->toBe(1);
});

it('bảo mật: thu hồi "ghi nhớ đăng nhập" đổi remember_token', function () {
    $user = make_user('student', 'Sinh viên remember');
    $before = $user->fresh()->remember_token;

    $this->actingAs($user)->post(route('users.settings.revoke-remember'))
        ->assertSessionHas('success');

    $after = $user->fresh()->remember_token;

    expect($after)->not->toBe($before)
        ->and($after)->toHaveLength(60);
});

/* ---------------------------- SMOKE: 3 TRANG ----------------------------- */

it('3 tab mới render được cho cả sinh viên, giảng viên và admin', function () {
    foreach (['student', 'lecturer', 'admin'] as $role) {
        $user = make_user($role, 'Người dùng ' . $role);

        foreach ([
            'users.settings.security',
            'users.settings.profile',
            'users.settings.privacy',
        ] as $routeName) {
            $this->actingAs($user)->get(route($routeName))->assertOk();
        }
    }
});

/* ---------------- TRANG TỔNG QUAN "CÀI ĐẶT" (/settings) ------------------ */

it('trang tổng quan /settings hiển thị 5 thẻ và render cho cả 3 vai trò', function () {
    foreach (['student', 'lecturer', 'admin'] as $role) {
        $user = make_user($role, 'Người dùng ' . $role);

        $this->actingAs($user)->get(route('users.settings.index'))
            ->assertOk()
            ->assertSee('Thiết lập tài khoản')
            ->assertSee('Thông tin chung')
            ->assertSee('Đổi mật khẩu')
            ->assertSee('Bảo mật')
            ->assertSee('Hồ sơ')
            ->assertSee('Riêng tư');
    }
});

it('menu "Cài đặt" đã trỏ tới /settings (không còn link chết href="#")', function () {
    $admin = make_user('admin', 'Admin menu');
    $student = make_user('student', 'Sinh viên menu');

    // Layout admin/giảng viên (layouts.app): sidebar "Cài đặt" trỏ /settings.
    $this->actingAs($admin)->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee(route('users.settings.index'), false);

    // Layout sinh viên (layouts.user): sidebar mới thêm mục "Cài đặt".
    $this->actingAs($student)->get(route('users.settings.profile'))
        ->assertOk()
        ->assertSee(route('users.settings.index'), false);
});

it('từ /settings mở được cả 5 tab con', function () {
    $user = make_user('student', 'Sinh viên điều hướng');

    $this->actingAs($user)->get(route('users.settings.index'))
        ->assertOk()
        ->assertSee(route('users.profile.info'), false)
        ->assertSee(route('users.profile.password'), false)
        ->assertSee(route('users.settings.security'), false)
        ->assertSee(route('users.settings.profile'), false)
        ->assertSee(route('users.settings.privacy'), false);
});

