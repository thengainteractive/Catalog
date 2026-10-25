<?php

namespace Modules\Catalog\Policies;

use App\Models\User;
use Modules\Catalog\Models\Category;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasPermissionTo('view_catalog');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->hasRole('Super Admin') || $user->hasPermissionTo('view_catalog');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasPermissionTo('create_catalog');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasRole('Super Admin') || $user->hasPermissionTo('edit_catalog');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->hasRole('Super Admin') || $user->hasPermissionTo('delete_catalog');
    }
}
