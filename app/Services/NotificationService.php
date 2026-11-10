<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Shipment;
use App\Models\User;

class NotificationService
{
    /**
     * Send an in-app notification to a user.
     *
     * @param User $user
     * @param string $title
     * @param string $message
     * @param string $type ('INFO', 'SUCCESS', 'WARNING', 'DANGER')
     * @param array|null $data
     * @return Notification
     */
    public function send(
        User $user,
        string $title,
        string $message,
        string $type = 'INFO',
        ?array $data = null
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => strtoupper($type),
            'data' => $data,
            'read_at' => null,
        ]);
    }

    /**
     * Dispatch notification to the shipment's customer.
     */
    public function sendToShipmentCustomer(
        Shipment $shipment,
        string $title,
        string $message,
        string $type = 'INFO',
        ?array $data = null
    ): ?Notification {
        $customer = $shipment->customer;
        if (! $customer) {
            return null;
        }

        $payload = array_merge([
            'shipment_id' => $shipment->id,
            'shipment_number' => $shipment->shipment_number,
        ], $data ?? []);

        return $this->send($customer, $title, $message, $type, $payload);
    }
}
