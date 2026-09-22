<?php

use App\Events\DirectMessagesSeen;
use App\Models\DirectMessage;
use Illuminate\Support\Facades\Event;

use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| Trạng thái tin nhắn 1-1 (2 mốc) + lịch sử trò chuyện — Feature test
|--------------------------------------------------------------------------
| - ĐÃ GỬI  = ✓ xám  (`seen_at IS NULL`) — người nhận chưa mở xem
| - ĐÃ XEM  = ✓✓ xanh (`seen_at` có giá trị) — người nhận đã mở hội thoại
| Người nhận KHÔNG cần click vào ô nhập nội dung, chỉ cần đang mở trang.
*/

beforeEach(function () {
    $this->sender = make_user('student', 'Sinh viên Gửi');
    $this->reader = make_user('student', 'Sinh viên Nhận');
});

it('tin vừa gửi mặc định là ĐÃ GỬI (seen_at null) và chưa broadcast trạng thái', function () {
    Event::fake([DirectMessagesSeen::class]);

    $this->actingAs($this->sender)
        ->post(route('chat.send', $this->reader->user_id), ['content' => 'Chào bạn!'])
        ->assertRedirect();

    $message = DirectMessage::first();

    expect($message->seen_at)->toBeNull()
        ->and($message->deliveryStatus())->toBe('sent')
        ->and($message->deliveryStatusLabel())->toBe('Đã gửi');

    Event::assertNotDispatched(DirectMessagesSeen::class);
});

it('người nhận MỞ hội thoại ⇒ tin chuyển ĐÃ XEM và broadcast cho người gửi', function () {
    $this->actingAs($this->sender)
        ->post(route('chat.send', $this->reader->user_id), ['content' => 'Tin cần xem']);

    $message = DirectMessage::first();

    Event::fake([DirectMessagesSeen::class]);

    $this->actingAs($this->reader)
        ->get(route('chat.show', $this->sender->user_id))
        ->assertOk();

    $fresh = $message->fresh();

    expect($fresh->seen_at)->not->toBeNull()
        ->and($fresh->is_read)->toBeTrue()
        ->and($fresh->deliveryStatus())->toBe('seen')
        ->and($fresh->deliveryStatusLabel())->toBe('Đã xem');

    Event::assertDispatched(DirectMessagesSeen::class, function (DirectMessagesSeen $event) {
        return $event->senderId === (int) $this->sender->user_id
            && $event->readerId === (int) $this->reader->user_id
            && $event->count === 1;
    });
});

it('AJAX markRead (cửa sổ chat đang mở) cũng đánh dấu ĐÃ XEM', function () {
    $this->actingAs($this->sender)
        ->post(route('chat.send', $this->reader->user_id), ['content' => 'Tin thứ hai']);

    expect(DirectMessage::first()->seen_at)->toBeNull();

    $this->actingAs($this->reader)
        ->postJson(route('chat.read', $this->sender->user_id))
        ->assertOk();

    expect(DirectMessage::first()->fresh()->deliveryStatus())->toBe('seen');
});

it('trang chat hiển thị tick trạng thái cho tin của mình (sent → seen)', function () {
    $this->actingAs($this->sender)
        ->post(route('chat.send', $this->reader->user_id), ['content' => 'Tin có tick']);

    // Người gửi mở hội thoại: tin của mình đang ở trạng thái ĐÃ GỬI
    $this->actingAs($this->sender)
        ->get(route('chat.show', $this->reader->user_id))
        ->assertOk()
        ->assertSee('data-role="message-status"', false)
        ->assertSee('data-message-status="sent"', false)
        ->assertSee('Đã gửi lúc', false);

    // Người nhận mở hội thoại ⇒ trạng thái trong DB là ĐÃ XEM
    $this->actingAs($this->reader)->get(route('chat.show', $this->sender->user_id))->assertOk();

    $this->actingAs($this->sender)
        ->get(route('chat.show', $this->reader->user_id))
        ->assertOk()
        ->assertSee('data-message-status="seen"', false)
        ->assertSee('Đã xem lúc', false);
});

it('lịch sử trò chuyện: tải thêm tin nhắn cũ trả HTML + has_more đúng', function () {
    foreach (range(1, 5) as $index) {
        $this->actingAs($this->sender)
            ->post(route('chat.send', $this->reader->user_id), ['content' => 'Tin số ' . $index]);
    }

    // Trang 1: lấy 3 tin MỚI NHẤT (before lớn hơn mọi id đang có)
    $first = $this->actingAs($this->sender)
        ->getJson(route('chat.history', $this->reader->user_id) . '?before=999999&limit=3')
        ->assertOk()
        ->assertJsonPath('ok', true);

    expect($first->json('html'))->toContain('Tin số 3')
        ->and($first->json('html'))->toContain('Tin số 5')
        ->and($first->json('has_more'))->toBeTrue();

    // Trang 2: lấy tiếp các tin CŨ HƠN first_id (tin cũ nhất của trang 1) ⇒ hết lịch sử
    $second = $this->actingAs($this->sender)
        ->getJson(route('chat.history', $this->reader->user_id) . '?before=' . $first->json('first_id') . '&limit=3')
        ->assertOk();

    expect($second->json('html'))->toContain('Tin số 1')
        ->and($second->json('has_more'))->toBeFalse();
});

it('khách chưa đăng nhập không gọi được lịch sử/markRead', function () {
    $this->getJson(route('chat.history', $this->sender->user_id))->assertStatus(401);
    $this->postJson(route('chat.read', $this->sender->user_id))->assertStatus(401);
});
