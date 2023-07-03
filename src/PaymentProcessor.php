<?php

namespace NodelessIO\Prestashop;

class PaymentProcessor
{
    public const INVOICE_STATUS_NEW = 'new';
    public const INVOICE_STATUS_PAID = 'paid';
    public const INVOICE_STATUS_PENDING = 'pending_confirmation';
    public const INVOICE_STATUS_EXPIRED = 'expired';
    public const INVOICE_STATUS_CANCELLED = 'cancelled';
    public const INVOICE_STATUS_UNDERPAID = 'underpaid';
    public const INVOICE_STATUS_OVERPAID = 'overpaid';
    public const INVOICE_STATUS_IN_FLIGHT = 'in_flight';

    public NodelessApi $api;
    // Can't type hint this because PS does only pass the class without namespace for soem odd reason.
    public $module;

    public function __construct($module)
    {
        $this->api = new NodelessApi();
        $this->module = $module;
    }

    // todo constructor logger etc
    public function checkInvoiceStatus(PaymentModel $pm)
    {
        // Get the invoice id and fetch latest data from nodeless.io
        if (!$invoiceId = $pm->getInvoiceId()) {
            // todo: log
            throw new \Exception('Could not find the invoice associated with that payment model id: ' . $pm->getId());
        }

        #try {
            $this->updateOrderStatus($pm, $this->api->getInvoiceStatus($invoiceId));
        #} catch (\Throwable $e) {
            // todo: log
        #    throw new \Exception('dannggg');
        #}
    }

    public function updateOrderStatus(PaymentModel $pm, string $status): bool
    {
        // Log the received status.
        $updateOrderStatus = false;
        $message = null;
        $orderStatus = null;

        switch ($status) {
            case 'new':
                //Logger::debug( __METHOD__ .  ': Invoice status still "new" doing nothing.' );
                break;

            case 'pending_confirmation': // The invoice is paid in full.
                $message = 'Invoice payment received fully, waiting for settlement.';
                $updateOrderStatus = true;
                $orderStatus = 1; // todo: id for pending
                break;

            case 'paid':
                $message = 'Invoice fully paid and settled.';
                $updateOrderStatus = true;
                $orderStatus = 1; // todo: id for pending
                break;

            case 'underpaid':
                $message = 'Invoice is underpaid. Needs manual checking.';
                $updateOrderStatus = true;
                $orderStatus = 1; // todo: id for underpaid
                break;

            case 'overpaid':
                $message = 'Invoice is overpaid. Needs manual checking.';
                $updateOrderStatus = true;
                $orderStatus = 1; // todo: id for overpaid
                break;

            case 'in_flight':
                //Logger::debug( 'Invoice in flight.' );
                //$this->updateWCOrderStatus( $order, $configuredOrderStates[ OrderStates::IN_FLIGHT ] );
                //$order->add_order_note( __( 'Invoice is in flight. Eventually needs manual checking if no paid status follows.', 'nodeless-for-woocommerce' ) );
                //$this->updateWCOrder( $order, $webhookData );
                $message = 'Invoice is in flight. Eventually needs manual checking if no paid status follows.';
                $updateOrderStatus = true;
                $orderStatus = 1; // todo: id for in flight
                break;

            case 'expired':
                $message = 'Invoice expired.';
                #$updateOrderStatus = true; // not updating order status because customer can retry?
                #$orderStatus = 1;
                break;

            case 'cancelled':
                $message = 'Invoice was cancelled.';
                $updateOrderStatus = true;
                $orderStatus = 1; // todo: id for cancelled
                break;
        }

        // Load cart and customer.
        $cart = new \Cart($pm->getCartId());
        $customer = new \Customer($cart->id_customer);

        // Create the order if not available, yet.
        $order = \Order::getByCartId($pm->getCartId());
        if (!$order && $updateOrderStatus) {

            $this->module->validateOrder(
                $pm->getCartId(),
                $orderStatus,
                $pm->getAmountFiat(),
                $this->module->displayName,
                $message,
                null, // extra vars
                null, // currency id
                false,
                $customer->secure_key
            );

            // Load the order as it exists now.
            $newOrder = \Order::getByCartId($pm->getCartId());
            $pm->setOrderId($newOrder->id);
            $pm->save();

            // Add order history.
            #$orderHistory = new \OrderHistory();
            #$orderHistory->id_order = $newOrder->id;
            #$orderHistory->message = $message;
            #$orderHistory->add();

        } else {
            // Update existing order status.
            if ($updateOrderStatus) {
                // Add order history.
                $orderHistory = new \OrderHistory();
                $orderHistory->id_order = $order->id;
                $orderHistory->changeIdOrderState($orderStatus, $order);
                $orderHistory->add();
            }
        }

        // Always update payment model status.
        $pm->setStatus($status);
        $pm->save();

        return $updateOrderStatus;
    }
}
