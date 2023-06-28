<?php

namespace NodelessIO\Prestashop;

class Constants
{
    // Configuration.
    public const API_KEY = 'NODELESS_API_KEY';
    public const STORE_ID = 'NODELESS_STORE_ID';
    public const WEBHOOK_SECRET = 'NODELESS_WEBHOOK_SECRET';
    public const WEBHOOK_ID = 'NODELESS_WEBHOOK_ID';
    public const LIVE_MODE = 'NODELESS_LIVE_MODE';
    public const HOST_PRODUCTION = 'https://nodeless.io';
    public const HOST_TESTNET = 'https://testnet.nodeless.io';


    // Order states.
    public const ORDER_STATE_NEW = 'NODELESS_OS_NEW';
    public const ORDER_STATE_PENDING_CONFIRMATION = 'NODELESS_OS_PENDING_CONFIRMATION';
    public const ORDER_STATE_PAID = 'NODELESS_OS_PAID';
    public const ORDER_STATE_OVERPAID = 'NODELESS_OS_OVERPAID';
    public const ORDER_STATE_UNDERPAID = 'NODELESS_OS_UNDERPAID';
    public const ORDER_STATE_IN_FLIGHT = 'NODELESS_OS_IN_FLIGHT';
    public const ORDER_STATE_EXPIRED = 'NODELESS_OS_EXPIRED';
    public const ORDER_STATE_CANCELLED = 'NODELESS_OS_CANCELLED';

    // Webhook events.
    public const WEBHOOK_EVENTS = [
        'pending_confirmation',
        'paid',
        'expired',
        'cancelled',
        'underpaid',
        'overpaid',
        'in_flight'
    ];

}
