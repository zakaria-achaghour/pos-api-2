<?php

namespace App\Observers;

use App\Models\OrderItem;

class OrderItemObserver
{
    /**
     * Handle the OrderItem "created" event.
     */
    public function created(OrderItem $orderItem): void
    {
        $this->updateOrderStatus($orderItem);
    }

    /**
     * Handle the OrderItem "updated" event.
     */
    public function updated(OrderItem $orderItem): void
    {
        // Only update if state changed
        if ($orderItem->wasChanged('state')) {
            $this->updateOrderStatus($orderItem);
        }
    }

    /**
     * Handle the OrderItem "deleted" event.
     */
    public function deleted(OrderItem $orderItem): void
    {
        $this->updateOrderStatus($orderItem);
    }

    /**
     * Update the parent order status based on item states
     */
    private function updateOrderStatus(OrderItem $orderItem): void
    {
        $order = $orderItem->order;
        
        if ($order) {
            $order->updateStatusFromItems();
        }
    }
}
