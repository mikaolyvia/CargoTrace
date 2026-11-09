<?php

namespace App\Policies;

use App\Models\ShipmentException;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShipmentExceptionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any exceptions.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'OPERATOR', 'CUSTOMER'], true);
    }

    /**
     * Determine whether the user can view the specific exception.
     * Customers may only view exceptions associated with their own shipments.
     */
    public function view(User $user, ShipmentException $exception): bool
    {
        if ($user->isAdmin() || $user->isOperator()) {
            return true;
        }

        if ($user->isCustomer()) {
            $exception->loadMissing('shipment');

            return $exception->shipment !== null && (int) $user->id === (int) $exception->shipment->customer_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create/report exceptions.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    /**
     * Determine whether the user can update the exception.
     */
    public function update(User $user, ShipmentException $exception): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    /**
     * Determine whether the user can set exception to investigating.
     * RESOLVED exceptions cannot directly be moved back to investigating.
     */
    public function investigate(User $user, ShipmentException $exception): bool
    {
        if ($exception->isResolved()) {
            return false;
        }

        return $user->isAdmin() || $user->isOperator();
    }

    /**
     * Determine whether the user can resolve the exception.
     */
    public function resolve(User $user, ShipmentException $exception): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    /**
     * Determine whether the user can delete the exception.
     */
    public function delete(User $user, ShipmentException $exception): bool
    {
        return $user->isAdmin();
    }
}
