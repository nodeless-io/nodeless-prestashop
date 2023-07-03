<?php

use NodelessIO\Prestashop\NodelessApi;
use NodelessIO\Prestashop\PaymentModel;

class NodelessRedirectModuleFrontController extends ModuleFrontController
{
    public $api;
    public function __construct()
    {
        parent::__construct();
        $this->api = new NodelessApi();
    }

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;

        if (!$this->module->checkCurrency($cart)) {
            \Tools::redirect('index.php?controller=order');
        }

        $amount = number_format($cart->getOrderTotal(true, 3), 2, '.', '');
        $currency = Context::getContext()->currency;

        $description = [];
        foreach ($cart->getProducts() as $product) {
            $description[] = $product['cart_quantity'] . ' × ' . $product['name'];
        }

        $customer = new Customer($cart->id_customer);

        $link = new Link();

        $redirectUrl = $link->getModuleLink("nodeless", "validation", [
            'cart_id' => $cart->id,
            'id_module' => $this->module->id,
            'key' => $customer->secure_key
        ]);

        try {
            $invoice = $this->api->createInvoice(
                $amount,
                $currency->iso_code,
                $customer->email,
                $redirectUrl
            );

            if (!$invoice->getCheckoutLink()) {
                \Tools::redirect('index.php?controller=order');
            }

            // Store data.
            $pm = new PaymentModel();
            $pm->setCartId($cart->id);
            $pm->setInvoiceId($invoice->getId());
            $pm->setAmountSats($invoice->getSatsAmount());
            $pm->setAmountFiat($amount);
            $pm->setCurrency($currency->iso_code);
            $pm->setStatus($invoice->getStatus());
            $pm->setCheckoutUrl($invoice->getCheckoutLink());
            $pm->setCreatedAt(date('Y-m-d H:i:s'));
            $pm->save();

            \Tools::redirect($invoice->getCheckoutLink());
        } catch (\Throwable $e) {
            // todo: log + redirect
            die($e->getMessage());
            \Tools::redirect('index.php?controller=order');
        }
    }
}
