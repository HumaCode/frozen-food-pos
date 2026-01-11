<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\WholesalePrice;
use Illuminate\Auth\Access\HandlesAuthorization;

class WholesalePricePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:WholesalePrice');
    }

    public function view(AuthUser $authUser, WholesalePrice $wholesalePrice): bool
    {
        return $authUser->can('View:WholesalePrice');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:WholesalePrice');
    }

    public function update(AuthUser $authUser, WholesalePrice $wholesalePrice): bool
    {
        return $authUser->can('Update:WholesalePrice');
    }

    public function delete(AuthUser $authUser, WholesalePrice $wholesalePrice): bool
    {
        return $authUser->can('Delete:WholesalePrice');
    }

    public function restore(AuthUser $authUser, WholesalePrice $wholesalePrice): bool
    {
        return $authUser->can('Restore:WholesalePrice');
    }

    public function forceDelete(AuthUser $authUser, WholesalePrice $wholesalePrice): bool
    {
        return $authUser->can('ForceDelete:WholesalePrice');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:WholesalePrice');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:WholesalePrice');
    }

    public function replicate(AuthUser $authUser, WholesalePrice $wholesalePrice): bool
    {
        return $authUser->can('Replicate:WholesalePrice');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:WholesalePrice');
    }

}