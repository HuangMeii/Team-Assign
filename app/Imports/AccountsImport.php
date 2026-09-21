<?php

namespace App\Imports;

use App\Models\User;
use App\Services\PasswordAuditService;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AccountsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    public function __construct(private readonly User $actor) {}

    public function model(array $row)
    {
        $user = User::firstOrNew(['email' => strtolower(trim($row['email']))]);
        $isNew = !$user->exists;
        $password = $row['password'] ?? null;

        $user->fill([
            'name' => $row['name'],
            'role' => $row['role'] ?? 'student',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        if ($password) {
            $user->password = Hash::make($password);
        } elseif ($isNew) {
            $user->password = Hash::make('123456');
        }

        $user->save();

        if ($password) {
            PasswordAuditService::record($user, $this->actor, 'admin_import');
        }

        return null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['nullable', 'in:student,lecturer,admin'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
        ];
    }
}