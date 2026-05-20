<?php
namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine if the given order can be viewed by the user.
     */
    public function view(User $user, Order $order)
    {
        return $user->id === $order->user_id;
    }
    public function update(User $user, Order $order)
    {
        // Only allow the owner of the order to cancel/update it
        return $user->id === $order->user_id;
    }
}