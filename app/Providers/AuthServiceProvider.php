<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\PostPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Post::class => PostPolicy::class,
        Category::class => CategoryPolicy::class,
    ];

    public function boot(): void
    {
        Gate::before(function (User $user): ?bool {
            return $user->hasRole('Super Admin') || $user->hasRole('Super-Admin') ? true : null;
        });

        Gate::define('access-overview-dashboard', function (User $user): bool {
            return $user->hasAnyRole(['Admin', 'Manager', 'Super Admin', 'Super-Admin']);
        });

        $this->registerPolicies();
    }
}
