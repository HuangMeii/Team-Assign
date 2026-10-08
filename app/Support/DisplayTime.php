<?php

namespace App\Support;

/**
 * Bó-2 (fix L09) — Múi giờ dùng để HIỂN THỊ thời gian.
 *
 * Bối cảnh: `users.timezone` là tuỳ chọn của người dùng nhưng KHÔNG được phép đổi
 * timezone mặc định của PHP (`date_default_timezone_set`) vì sẽ làm lệch mốc thời gian
 * ghi vào DB và rò rỉ sang request khác trên cùng worker.
 *
 * Vì vậy middleware `SetLocale` chỉ lưu múi giờ vào `config('app.display_timezone')`,
 * và nơi hiển thị gọi `$carbon->displayTz()` (macro ở AppServiceProvider) hoặc dùng
 * `DisplayTime::timezone()`.
 */
class DisplayTime
{
    /** Múi giờ đang dùng để hiển thị (mặc định = múi giờ ứng dụng). */
    public static function timezone(): string
    {
        $timezone = config('app.display_timezone');

        return is_string($timezone) && $timezone !== '' ? $timezone : (string) config('app.timezone', 'UTC');
    }

    /** Có đang hiển thị theo múi giờ riêng của người dùng không? */
    public static function isCustom(): bool
    {
        return self::timezone() !== (string) config('app.timezone', 'UTC');
    }

    /** Chuyển 1 mốc thời gian sang múi giờ hiển thị rồi format. */
    public static function format(?\DateTimeInterface $at, string $format = 'd/m/Y H:i'): ?string
    {
        if ($at === null) {
            return null;
        }

        return \Illuminate\Support\Carbon::instance($at)->timezone(self::timezone())->format($format);
    }
}
