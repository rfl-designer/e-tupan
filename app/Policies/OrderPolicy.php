<?php

declare(strict_types = 1);

namespace App\Policies;

use App\Domain\Checkout\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Order $order): bool
    {
        // If order belongs to authenticated user
        if ($user !== null && $order->user_id === $user->id) {
            return true;
        }

        // For guest orders, check the access token from the request
        if ($order->isGuest()) {
            $request = request();
            $token   = $request->query('token');

            return $token === $order->access_token;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        return false;
    }
}
