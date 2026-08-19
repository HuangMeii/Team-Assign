<?php
namespace App\Providers;

use App\Models\Groups;
use App\Models\Topics;
use App\Models\Topic_requests;
use App\Models\Join_Requests;
use App\Policies\GroupsPolicy;
use App\Policies\TopicPolicy;
use App\Policies\TopicRequestPolicy;
use App\Policies\JoinRequestPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Groups::class => GroupsPolicy::class,
        Topics::class => TopicPolicy::class,
        Topic_requests::class => TopicRequestPolicy::class,
        Join_Requests::class => JoinRequestPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}