<?php

namespace App\Enums;

enum DocumentRequestStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case ReadyForPickup = 'ready_for_pickup';
    case Released = 'released';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::ReadyForPickup => 'Ready for Pickup',
            self::Released => 'Released',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Statuses an admin/staff is allowed to move a request to, from the current status.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Processing, self::Rejected, self::Cancelled],
            self::Processing => [self::ReadyForPickup, self::Rejected],
            self::ReadyForPickup => [self::Released],
            self::Released, self::Rejected, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}