<?php

namespace NodelessIO\Prestashop;

use NodelessIO\Client\StoreInvoiceClient;
use NodelessIO\Client\StoreWebhookClient;
use NodelessIO\Response\StoreInvoiceResponse;
use NodelessIO\Response\StoreWebhookResponse;

class NodelessApi
{
    public \Configuration $configuration;
    public string $host;
    public string $apiKey;
    public string $storeId;
    public string $webhookSecret;
    public string $webhookId;

    public function __construct()
    {
        $this->configuration = new \Configuration();
        $this->apiKey = $this->configuration->get(Constants::API_KEY);
        $this->storeId = $this->configuration->get(Constants::STORE_ID);
        $this->webhookSecret = $this->configuration->get(Constants::WEBHOOK_SECRET);
        $this->webhookId = $this->configuration->get(Constants::WEBHOOK_ID);

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
    ): StoreInvoiceResponse {
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

        return $client->getInvoice($this->storeId, $invoiceId);
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

    public function ensureWebhook(): void
    {
        if (!empty($this->apiKey) &&
            !empty($this->storeId) &&
            !empty($this->webhookId) &&
            $this->currentWebhookValid()) {
            // do nothing.
        } else {
            if ($this->createWebhook()) {
                // todo: add notice?
            }
        }
    }

    public function createWebhook(): ?StoreWebhookResponse
    {
        $webhookSecret = \bin2hex(\random_bytes(24));

        $client = new StoreWebhookClient($this->host, $this->apiKey);

        try {
            $webhook = $client->createWebhook(
                $this->storeId,
                'store',
                $this->storeWebhookEndpointUrl(),
                Constants::WEBHOOK_EVENTS,
                $webhookSecret,
                'active'
            );

            // Save to PS config.
            $this->configuration->updateValue(Constants::WEBHOOK_SECRET, $webhookSecret);
            $this->configuration->updateValue(Constants::WEBHOOK_ID, $webhook->getId());

            return $webhook;
        } catch (\Throwable $e) {
            // todo: log
        }

        return null;
    }

    /**
     * Make sure the current webhook is available on BTCPay and also the URL did
     * not change in the meantime.
     */
    public function currentWebhookValid(): bool
    {
        $client = new StoreWebhookClient($this->host, $this->apiKey);

        try {
            $webhook = $client->getWebhook($this->storeId, $this->webhookId);

            if ($this->storeWebhookEndpointUrl() === $webhook->getUrl()) {
                return true;
            } else {
                // todo: log url mismatch
            }

        } catch (\Throwable $e) {
            // todo: log exception on fetching webhook
        }

        return false;
    }

    public function storeWebhookEndpointUrl(): string
    {
        $link = new \Link();
        return $link->getModuleLink('nodeless', 'webhook', [], true);
    }

}
