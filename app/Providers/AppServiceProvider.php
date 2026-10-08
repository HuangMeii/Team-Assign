<?php

namespace App\Providers;

use App\Models\Group_Members;
use App\Models\Groups;
use App\Observers\GroupMemberObserver;
use App\Observers\GroupObserver;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // BẢNG TIN LỚP HỌC (App\Services\ClassStreamService):
        // tự ghi hoạt động nhóm vào feed của lớp mà nhóm thuộc về.
        Groups::observe(GroupObserver::class);
        Group_Members::observe(GroupMemberObserver::class);

        // Bó-2 (L09): hiển thị thời gian theo MÚI GIỜ của người dùng mà KHÔNG đổi
        // timezone mặc định của PHP (tránh làm lệch mốc thời gian ghi vào DB).
        // Dùng trong view: {{ $model->created_at?->displayTz()->format('d/m/Y H:i') }}
        Carbon::macro('displayTz', function () {
            return $this->copy()->timezone(\App\Support\DisplayTime::timezone());
        });
    }
}
