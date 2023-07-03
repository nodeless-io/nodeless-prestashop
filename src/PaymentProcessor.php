<?php

namespace NodelessIO\Prestashop;

class PaymentProcessor
{
    public NodelessApi $api;
    // Can't type hint this because PS does only pass the class without namespace for some odd reason.
    public $module;

    public $configuration;

    public function __construct($module)
    {
        $this->api = new NodelessApi();
        $this->module = $module;
        $this->configuration = new \Configuration();
    }

    // todo constructor logger etc
    public function checkInvoiceStatus(\PaymentModel $pm): bool
    {
        // Get the invoice id and fetch latest data from nodeless.io
        if (!$invoiceId = $pm->getInvoiceId()) {
            $errInvoice = 'Could not find the invoice associated with that payment model id: ' . $pm->getId();
            \PrestaShopLogger::addLog($errInvoice, 3);
            throw new \Exception($errInvoice);
        }

        return $this->updateOrderStatus($pm, $this->api->getInvoiceStatus($invoiceId));
    }

    public function updateOrderStatus(\PaymentModel $pm, string $status): bool
    {
        // Log the received status.
        $updateOrderStatus = false;
        $message = null;
        $orderStatus = null;

        switch ($status) {
            case Constants::INVOICE_STATUS_NEW:
                break;

            case Constants::INVOICE_STATUS_PENDING:
                $message = 'Invoice payment received fully, waiting for settlement.';
                $updateOrderStatus = true;
                $orderStatus = $this->configuration->get(Constants::ORDER_STATE_PENDING_CONFIRMATION);
                break;

            case Constants::INVOICE_STATUS_PAID:
                $message = 'Invoice fully paid and settled.';
                $updateOrderStatus = true;
                $orderStatus = $this->configuration->get(Constants::ORDER_STATE_PAID);
                break;

            case Constants::INVOICE_STATUS_UNDERPAID:
                $message = 'Invoice is underpaid. Needs manual checking.';
                $updateOrderStatus = true;
                $orderStatus = $this->configuration->get(Constants::ORDER_STATE_UNDERPAID);
                break;

            case Constants::INVOICE_STATUS_OVERPAID:
                $message = 'Invoice is overpaid. Needs manual checking.';
                $updateOrderStatus = true;
                $orderStatus = $this->configuration->get(Constants::ORDER_STATE_OVERPAID);
                break;

            case Constants::INVOICE_STATUS_IN_FLIGHT:
                $message = 'Invoice is in flight. Eventually needs manual checking if no paid status follows.';
                $updateOrderStatus = true;
                $orderStatus = $this->configuration->get(Constants::ORDER_STATE_IN_FLIGHT);
                break;

            case Constants::INVOICE_STATUS_EXPIRED:
                // Not updating order status so the customer can retry with order still in cart.
                // This will keep the order as cart only and not create an actual order; this is how PS handles orders.
                $message = 'Invoice expired.';
                \PrestaShopLogger::addLog('Invoice expired, cart id: ' . $pm->getCartId(), 1);
                $updateOrderStatus = false;
                $orderStatus = $this->configuration->get(Constants::ORDER_STATE_EXPIRED);
                break;

            case Constants::INVOICE_STATUS_CANCELLED:
                $message = 'Invoice was cancelled.';
                $updateOrderStatus = true;
                $orderStatus = $this->configuration->get(Constants::ORDER_STATE_CANCELLED);
                break;
        }

        // Load cart and customer.
        $cart = new \Cart($pm->getCartId());
        $customer = new \Customer($cart->id_customer);

        // Create the order if not available, yet.
        $order = \Order::getByCartId($pm->getCartId());
        if (!$order && $updateOrderStatus) {
            // Create an order from cart.
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

            // Load the order as it exists now, update payment model.
            $newOrder = \Order::getByCartId($pm->getCartId());
            $pm->setOrderId($newOrder->id);
            $pm->save();

            // Add order history.
            $orderHistory = new \OrderHistory();
            $orderHistory->id_order = $newOrder->id;
            $orderHistory->changeIdOrderState($orderStatus, $newOrder);
            $orderHistory->message = $message;
            $orderHistory->add();

        } else { // Update existing order status.
            if ($updateOrderStatus) {
                // Add only order history and update status.
                $orderHistory = new \OrderHistory();
                $orderHistory->id_order = $order->id;
                $orderHistory->changeIdOrderState($orderStatus, $order);
                $orderHistory->message = $message;
                $orderHistory->add();
            }
        }

        // Always update payment model status.
        $pm->setStatus($status);
        $pm->save();

        return $updateOrderStatus;
    }
}
