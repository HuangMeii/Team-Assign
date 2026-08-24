<?php

namespace App\Services;

/**
 * Kết quả trả về thống nhất cho các Service nghiệp vụ.
 *
 * Controller nhận ServiceResult và chuyển thành flash message:
 *   return back()->with($result->status(), $result->message());
 */
class ServiceResult
{
    public function __construct(
        private readonly string $status,
        private readonly string $message,
        private readonly mixed $data = null,
    ) {}

    public static function ok(string $message, mixed $data = null): self
    {
        return new self('success', $message, $data);
    }

    public static function error(string $message, mixed $data = null): self
    {
        return new self('error', $message, $data);
    }

    public static function warning(string $message, mixed $data = null): self
    {
        return new self('warning', $message, $data);
    }

    public function succeeded(): bool
    {
        return $this->status === 'success';
    }

    public function status(): string
    {
        return $this->status;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function data(): mixed
    {
        return $this->data;
    }
}
