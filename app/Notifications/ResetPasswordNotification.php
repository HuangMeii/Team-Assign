<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Email đặt lại mật khẩu (tiếng Việt).
 *
 * Luồng: người dùng nhập email ở trang "Quên mật khẩu" (hoặc bấm nút trong
 * Thiết lập tài khoản) → hệ thống gửi email chứa liên kết kèm token →
 * người dùng click vào liên kết để xác thực và đặt mật khẩu mới.
 */
class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = route('users.profile.password', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $expire = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        return (new MailMessage)
            ->subject('Đặt lại mật khẩu - Hệ thống Quản lý Đề tài Nhóm')
            ->greeting('Xin chào ' . $notifiable->name . ',')
            ->line('Hệ thống nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.')
            ->action('Đặt lại mật khẩu', $url)
            ->line("Liên kết xác thực này sẽ hết hạn sau {$expire} phút.")
            ->line('Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này - mật khẩu hiện tại vẫn giữ nguyên.')
            ->salutation('Trân trọng, Hệ thống Quản lý Đề tài Nhóm');
    }
}