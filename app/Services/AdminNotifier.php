<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Message;
use App\Models\Order;
use App\Models\ProductComment;
use App\Models\ProductVariant;
use App\Notifications\Admin\AdminNotification;
use App\Notifications\Admin\LowStockAlert;
use App\Notifications\Admin\NewChatMessage;
use App\Notifications\Admin\NewOrderPlaced;
use App\Notifications\Admin\NewReviewSubmitted;
use App\Support\AdminPermissions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Sends bell notifications to Super Admins and to admins allowed to act on them.
 * Failures are logged and never interrupt the customer-facing action that triggered them.
 */
class AdminNotifier
{
    public function newOrder(Order $order): void
    {
        $this->send('orders.view', fn () => new NewOrderPlaced($order));
    }

    public function riskyOrder(Order $order, \App\Services\Fraud\RiskResult $result, bool $held): void
    {
        $this->send('orders.view', fn () => new \App\Notifications\Admin\RiskyOrderFlagged($order, $result, $held));
    }

    public function riskCheckFailed(Order $order, string $reason): void
    {
        $this->send('orders.view', fn () => new \App\Notifications\Admin\RiskCheckFailed($order, $reason));
    }

    public function lowStock(ProductVariant $variant, int $stock, int $threshold): void
    {
        $this->send('products.view', fn () => new LowStockAlert($variant, $stock, $threshold));
    }

    public function newChatMessage(Message $message): void
    {
        $this->send('messages.view', fn () => new NewChatMessage($message));
    }

    public function newReview(ProductComment $review): void
    {
        $this->send('reviews.view', fn () => new NewReviewSubmitted($review));
    }

    /**
     * @return Collection<int, Admin>
     */
    public function recipients(string $permission): Collection
    {
        $admins = Admin::role(AdminPermissions::SUPER_ADMIN_ROLE, AdminPermissions::GUARD)->get();

        try {
            $admins = $admins->merge(Admin::permission($permission)->get());
        } catch (Throwable) {
            // Permission not seeded yet: only Super Admins are notified.
        }

        return $admins->unique('id')->values();
    }

    /**
     * @param  callable(): AdminNotification  $make
     */
    private function send(string $permission, callable $make): void
    {
        try {
            $recipients = $this->recipients($permission);

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, $make());
            }
        } catch (Throwable $e) {
            Log::warning('Admin notification failed: ' . $e->getMessage(), ['permission' => $permission]);
        }
    }
}
