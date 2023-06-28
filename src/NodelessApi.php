<?php

namespace NodelessIO\Prestashop;

use NodelessIO\Client\StoreInvoiceClient;
use NodelessIO\Response\StoreInvoiceResponse;

class NodelessApi
{

    public \Configuration $configuration;
    public string $host;
    public string $apiKey;
    public string $storeId;
    public string $webhookSecret;


    public function __construct()
    {
        $this->configuration = new \Configuration();
        $this->apiKey = $this->configuration->get('NODELESS_API_KEY');
        $this->storeId = $this->configuration->get('NODELESS_STORE_ID');
        $this->webhookSecret = $this->configuration->get('NODELESS_WEBHOOK_SECRET');

        // Determine live mode.
        if ($this->configuration->get('NODELESS_LIVE_MODE') === '1') {
            $this->host = 'https://nodeless.io';
        } else {
            $this->host = 'https://testnet.nodeless.io';
        }
    }

    public function createInvoice(
        string $amount,
        string $currency,
        string $customerEmail,
        string $redirectUrl
    ): StoreInvoiceResponse
    {
        $client = new StoreInvoiceClient($this->host, $this->apiKey);

        return $client->createInvoice(
            $this->storeId,
            $amount,
            $currency,
            $customerEmail,
            $redirectUrl
        );
    }

    public function getInvoice(string $invoiceId): StoreInvoiceResponse
    {
        $client = new StoreInvoiceClient($this->host, $this->apiKey);

        return $client->getInvoice($this->storeId);
    }

    public function getInvoiceStatus(string $invoiceId): string
    {
        $client = new StoreInvoiceClient($this->host, $this->apiKey);

        return $client->getInvoiceStatus($this->storeId, $invoiceId);
    }

    public function getInvoiceMetaCartId(string $invoiceId): ?string
    {
        $invoice = $this->getInvoice($invoiceId);

        return $invoice->getMetadata()['cart_id'] ?? null;
    }
}
