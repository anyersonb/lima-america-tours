<?php

namespace App\Exceptions;

/**
 * Thrown by App\Services\PaymentGuard when a real (LIVE) charge is about to
 * fire outside of a fully-authorized production context. This is the
 * "guarda anti-cobro-real": it must be raised BEFORE any HTTP call reaches
 * Culqi or PayPal, never after.
 */
class RealChargeBlockedException extends \RuntimeException
{
    //
}
