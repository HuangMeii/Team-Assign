<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email xác thực khi người dùng YÊU CẦU ĐỔI EMAIL.
 *
 * Luồng: người dùng nhập email mới trong Thiết lập tài khoản → email hiện tại
 * vẫn dùng để đăng nhập → hệ thống gửi email xác thực tới ĐỊA CHỈ MỚI →
 * người dùng click liên kết (token có chữ ký, hạn 60 phút) → email mới có hiệu lực.
 */
class EmailChangeVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /** URL xác thực (signed) — public để test truy cập được */
    public $verificationUrl;

    public $user;

    public $newEmail;

    public function __construct(User $user, string $newEmail, string $verificationUrl)
    {
        $this->user = $user;
        $this->newEmail = $newEmail;
        $this->verificationUrl = $verificationUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Xác thực email mới - Hệ thống Quản lý Đề tài Nhóm',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-change-verification',
            with: [
                'subject' => 'Xác thực email mới',
            ],
        );
    }
}