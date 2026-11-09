<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShipmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any shipments.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'OPERATOR', 'CUSTOMER'], true);
    }

    /**
     * Determine whether the user can view the specific shipment.
     * Anti-IDOR Protection: Customers may only view their own shipments.
     */
    public function view(User $user, Shipment $shipment): bool
    {
        if ($user->isAdmin() || $user->isOperator()) {
            return true;
        }

        if ($user->isCustomer()) {
            return (int) $user->id === (int) $shipment->customer_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create shipments.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    /**
     * Determine whether the user can update the shipment.
     * Note: Status transitions are handled exclusively via transition().
     */
    public function update(User $user, Shipment $shipment): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    /**
     * Determine whether the user can delete (soft-delete) the shipment.
     * Restricted strictly to administrators.
     */
    public function delete(User $user, Shipment $shipment): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can execute a lifecycle status transition.
     */
    public function transition(User $user, Shipment $shipment): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    /**
     * Determine whether the user can append a tracking event to the shipment.
     */
    public function addTrackingEvent(User $user, Shipment $shipment): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    /**
     * Determine whether the user can report an operational exception on the shipment.
     */
    public function reportException(User $user, Shipment $shipment): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }
}
