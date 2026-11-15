<?php

namespace Modules\Catalog\Policies;

use App\Models\User;
use Modules\Catalog\Models\Category;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->can('view_catalog');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->hasRole('Super Admin') || $user->can('view_catalog');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->can('create_catalog');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasRole('Super Admin') || $user->can('edit_catalog');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->hasRole('Super Admin') || $user->can('delete_catalog');
    }
}
