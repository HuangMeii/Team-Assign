# L03 — Reset mật khẩu xong redirect sai kỳ vọng test (mức TRUNG BÌNH)

## Triệu chứng

- Test đỏ: `tests/Feature/Auth/ForgotPasswordTest.php:58`
  Expected `http://127.0.0.1:8000/login`, actual `http://127.0.0.1:8000/user/dashboard`.

## Nguyên nhân gốc

- `app/Http/Controllers/Auth/NewPasswordController.php:67-79`: khi reset thành công,
  code **đăng nhập luôn** (`Auth::login($resetUser)`), regenerate session, rồi
  redirect theo role (`student → user.dashboard`, `lecturer → dashboard`,
  `admin → admin.users.index`) kèm flash `success`.
- Test vẫn giữ kỳ vọng Breeze gốc: `assertRedirect(route('login'))` +
  `assertSessionHas('status')`.
- Đây là **hành vi có chủ ý** của dự án (đăng nhập luôn cho tiện), không phải bug
  runtime. Lệch đặc tả test ↔ code.

## Cách sửa (sửa TEST, giữ controller)

Trong `tests/Feature/Auth/ForgotPasswordTest.php:53-58`, đổi kỳ vọng:

```php
])->assertRedirect(route('login'))->assertSessionHas('status');
```

thành (với `$user` role student):

```php
])->assertRedirect(route('user.dashboard'))->assertSessionHas('success');
```

- Dòng 61 (`Hash::check` mật khẩu mới) và dòng 63-66 (đăng nhập lại bằng mật khẩu
  mới) giữ nguyên — sau L04 thì bước login này cũng hết 500.

## Kiểm chứng

- `php artisan test tests/Feature/Auth/ForgotPasswordTest.php --filter="click link"`

## File liên quan

- `app/Http/Controllers/Auth/NewPasswordController.php:64-86`
- `tests/Feature/Auth/ForgotPasswordTest.php:38-67`
