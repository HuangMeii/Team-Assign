<?php

namespace App\Providers;

use App\Models\Group_Members;
use App\Models\Groups;
use App\Observers\GroupMemberObserver;
use App\Observers\GroupObserver;
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
    }
}
