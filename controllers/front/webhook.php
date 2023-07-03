<?php
/**
 * 2023 Nodeless.io
 */

use NodelessIO\Prestashop\NodelessApi;
use NodelessIO\Prestashop\PaymentModel;
use NodelessIO\Prestashop\PaymentProcessor;

class NodelessWebhookModuleFrontController extends ModuleFrontController
{
    /** @var \PrestaShopBundle\Translation\Translator */
    public $translator;

    /** @var bool enforce ssl */
    public $ssl = true;

    public PaymentProcessor $processor;

    public NodelessApi $api;

    public function __construct()
    {
        parent::__construct();

        $this->translator = $this->module->getTranslator();
        $this->processor = new PaymentProcessor($this->module);
        $this->api = new NodelessApi();
    }

    /**
     * We don't want to show anything, but needed to return http status 200.
     *
     * {@inheritdoc}
     */
    public function display(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        // Get the translator so we can translate our errors
        if ($this->translator === null) {
            throw new \RuntimeException('Expected the translator to be available');
        }

        // Check module is active.
        if (!$this->module->active) {
            $this->error[] = $this->translator->trans('Payment method is not available anymore, aborting.', [], 'Modules.Nodeless.Shop');

            return false;
        }

        $rawPostData = file_get_contents('php://input');
        \PrestaShopLogger::addLog('Webhook data received: input: ' . print_r($rawPostData, true), 1);
        \PrestaShopLogger::addLog('Webhook headers: ' . print_r(getallheaders(), true), 1);

        if ($rawPostData) {
            // Validate webhook request.
            // Note: getallheaders() CamelCases all headers for PHP-FPM/Nginx but for others maybe not, so "NodelessIO-Sig" may becomes "Nodelessio-Sig".
            $headers = getallheaders();
            foreach ($headers as $key => $value) {
                if (strtolower($key) === 'nodeless-signature') {
                    $signature = $value;
                }
            }

            if (!isset($signature) || $this->api->validWebhookRequest($signature, $rawPostData)) {
                $errValidation = 'Failed to validate signature of webhook request.';
                \PrestaShopLogger::addLog($errValidation, 3);
                throw new \RuntimeException($errValidation, $errValidation);
            }
        }

        $postData = json_decode($rawPostData, false, 512, JSON_THROW_ON_ERROR);

        if (!isset($postData->uuid)) {
            $errInvoice = 'No Nodeless.io invoiceId provided, aborting.';
            \PrestaShopLogger::addLog($errInvoice, 3);
            throw new \RuntimeException($errInvoice);
        }

        // Find the existing invoice and check the status if it has been paid.
        // todo: support multiple invoices; check all and if one is "paid" show the order is paid.
        $collection = (new \PrestaShopCollection(PaymentModel::class))
            ->where('invoice_id', '=', (int) $postData->uuid)
            ->orderBy('created_at', 'desc');

        /** @var PaymentModel $pm */
        $pm = $collection->getFirst();
        \PrestaShopLogger::addLog('Loaded pm cart id: ' . $pm->getCartId(), 1);

        // Update order status.
        if (!$this->processor->checkInvoiceStatus($pm)) {
            \PrestaShopLogger::addLog('Error processing the order update on webhook.', 3);
        }

        // The order should exist now. If not log.
        $order = \Order::getByCartId($pm->getCartId());
        if (!$order) {
            $errOrder = __FUNCTION__ . ' Problem creating the order although the payment model exists. Payment model id: ' . $pm->getId();
            PrestaShopLogger::addLog($errOrder, 3);
            throw new \RuntimeException($errOrder);
        }

        echo 'OK';
    }
}
