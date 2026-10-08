<?php

namespace App\Http\Controllers;

use App\Models\Invites;
use Illuminate\Support\Facades\Auth;

/**
 * Trang legacy: danh sách LỜI MỜI đã nhận (đang chờ) — CHỈ ĐỌC.
 *
 * Bó-1 (fix): các method `accept()/reject()/destroy()` cũ đã bị XÓA vì
 *  - route `invites.approve` trỏ tới method KHÔNG tồn tại ⇒ bấm "Duyệt" là HTTP 500;
 *  - `reject` là route GET đổi trạng thái (CSRF) và nhận `Auth::user()` có thể null.
 * Thao tác duyệt/từ chối nay đi qua luồng chuẩn `POST user.accept-invite` /
 * `POST user.reject-invite` → `UserDashboardController` + `InvitationService`
 * (đã kiểm tra đúng người nhận lời mời).
 */
class InviteController extends Controller
{
    /**
     * Danh sách lời mời nhận được (đang chờ) của chính người đang đăng nhập.
     */
    public function index()
    {
        $invites = Invites::where('member_id', Auth::id())
            ->with(['group.leader', 'invitedBy', 'member'])
            ->where('status', 'Pending')
            ->paginate(10);

        return view('invites.index', compact('invites'));
    }
}
