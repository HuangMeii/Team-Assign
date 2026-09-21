<?php

namespace App\Http\Controllers;

use App\Models\BlockedUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockUserController extends Controller
{
    /**
     * Chặn một người dùng.
     */
    public function block(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,user_id'],
        ]);

        $blockedId = (int) $request->user_id;
        $blockerId = Auth::id();

        // Không cho phép chặn chính mình
        if ($blockedId === $blockerId) {
            return response()->json(['message' => 'Không thể tự chặn chính mình.'], 400);
        }

        BlockedUser::firstOrCreate([
            'blocker_id'  => $blockerId,
            'blocked_id'  => $blockedId,
        ]);

        return response()->json([
            'status'  => 'blocked',
            'message' => 'Đã chặn người dùng này.',
        ]);
    }

    /**
     * Bỏ chặn một người dùng.
     */
    public function unblock(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,user_id'],
        ]);

        $blockedId = (int) $request->user_id;
        $blockerId = Auth::id();

        BlockedUser::where('blocker_id', $blockerId)
            ->where('blocked_id', $blockedId)
            ->delete();

        return response()->json([
            'status'  => 'unblocked',
            'message' => 'Đã bỏ chặn người dùng này.',
        ]);
    }

    /**
     * Kiểm tra trạng thái chặn giữa hai người dùng.
     */
    public function check(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,user_id'],
        ]);

        $blockedId = (int) $request->user_id;
        $blockerId = Auth::id();

        $isBlocked = BlockedUser::isBlocked($blockerId, $blockedId);

        return response()->json([
            'blocked' => $isBlocked,
        ]);
    }
}
