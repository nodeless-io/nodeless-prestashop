<?php
/**
 * 2023 Nodeless.io
 */

use NodelessIO\Prestashop\PaymentModel;
use NodelessIO\Prestashop\PaymentProcessor;

class NodelessValidationModuleFrontController extends ModuleFrontController
{
    /** @var \PrestaShopBundle\Translation\Translator */
    public $translator;

    /** @var bool enforce ssl */
    public $ssl = true;

    public PaymentProcessor $processor;

    public function __construct()
    {
        parent::__construct();

        $this->translator = $this->module->getTranslator();
        $this->processor = new PaymentProcessor($this->module);
    }

    public function postProcess()
    {
        // Get the translator so we can translate our errors
        if ($this->translator === null) {
            throw new \RuntimeException('Expected the translator to be available');
        }

        // Check module is active.
        if (!$this->module->active) {
            $this->warning[] = $this->translator->trans('Payment method is not available anymore, please contact support..', [], 'Modules.Nodeless.Shop');
            $this->redirectWithNotifications($this->context->link->getPageLink('cart', $this->ssl));
        }

        $cart_id = \Tools::getValue('cart_id');
        $secure_key = \Tools::getValue('secure_key');

        $cart = new \Cart((int)$cart_id);
        $customer = new \Customer((int)$cart->id_customer);

        // Make sure cart and customer is available.
        if (!$cart || !$customer) {
            $this->warning[] = $this->translator->trans('Could not find cart or customer, something went wrong when processing your payment.', [], 'Modules.Nodeless.Shop');
            $this->redirectWithNotifications($this->context->link->getPageLink('cart', $this->ssl));
        }

        $payment_status = \Configuration::get('PS_OS_PAYMENT'); // Default value for a payment that succeed.
        $message = null; // You can add a comment directly into the order so the merchant will see it in the BO.

        // Find the existing invoice and check the status if it has been paid.
        // todo: support multiple invoices; check all and if one is "paid" show the order is paid.
        $collection = (new \PrestaShopCollection(PaymentModel::class))
            ->where('cart_id', '=', (int)$cart_id)
            ->orderBy('created_at', 'desc');

        $pm = $collection->getFirst();

        // If there is no order yet, we did not process any webhook, so do a manual check here
        if (!\Order::getByCartId($cart_id)) {
            $this->processor->checkInvoiceStatus($pm);
        }

        // Check again if the order exists, if not there was a problem with the payment or it expired, send the user
        // back to checkout.
        $order = \Order::getByCartId($cart_id);

        // Status is not paid yet, lookup the invoice to be sure and proceed.
        if ($order) {
            // Redirect to confirmation page.
            // If it's a guest, sent them to guest tracking
            if (Cart::isGuestCartByCartId($cart_id)) {
                Tools::redirect($this->context->link->getPageLink('guest-tracking', $this->ssl, null, ['order_reference' => $order->reference, 'email' => $customer->email]));
            }

            // If it's an actual customer, sent them to the order confirmation page
            Tools::redirect($this->context->link->getPageLink('order-confirmation', $this->ssl, null, [
                'id_cart'   => $order->id_cart,
                'id_module' => $this->module->id,
                'id_order'  => $order->id,
                'key'       => $customer->secure_key,
            ]));
        } else {
            // Redirect to checkout.
            $this->warning[] = $this->translator->trans('There was a problem processing your payment, the returned status of the payment was: %status%', ['%status%' => $pm->getStatus()], 'Modules.Nodeless.Shop');
            $this->redirectWithNotifications($this->context->link->getPageLink('cart', $this->ssl));
        }

    }
}
