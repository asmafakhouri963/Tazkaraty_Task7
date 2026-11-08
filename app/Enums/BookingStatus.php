<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case REFUNDED = 'refunded';
    case EXPIRED = 'expired';
}