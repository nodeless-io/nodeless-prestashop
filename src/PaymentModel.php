<?php

namespace NodelessIO\Prestashop;

/**
 * phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
 */
class PaymentModel extends \ObjectModel
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $cart_id;

    /**
     * @var int|null
     */
    public $order_id;

    /**
     * @var string
     */
    public $status;

    /**
     * @var string|null
     */
    public $invoice_id;

    /**
     * @var string|null
     */
    public $amount_sats;

    /**
     * @var string|null
     */
    public $amount_fiat;

    /**
     * @var string|null
     */
    public $currency;

    /**
     * @var string|null
     */
    public $checkout_url;

    /**
     * @var string|null
     */
    public $created_at;

    /**
     * @var string|null
     */
    public $updated_at;

    public static $definition = [
        'table' => 'nodeless_payment',
        'primary' => 'id',
        'multilang' => false,
        'fields' => [
            'cart_id' => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedInt'],
            'order_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'status' => ['type' => self::TYPE_STRING, 'required' => true, 'validate' => 'isString'],
            'invoice_id' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'amount_sats' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'amount_fiat' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'currency' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'checkout_url' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl'],
            'created_at' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'updated_at' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
        ],
    ];

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCartId(): int
    {
        return $this->cart_id;
    }

    public function setCartId(int $cart_id): void
    {
        $this->cart_id = $cart_id;
    }

    public function getOrderId(): int
    {
        return $this->order_id;
    }

    public function setOrderId(?int $order_id): void
    {
        $this->order_id = $order_id;
    }

    public function hasOrder(): bool
    {
        return false === empty($this->order_id);
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at;
    }

    public function setCreatedAt(string $date): void
    {
        $this->created_at = $date;
    }

    public function getUpdatedAt(): string
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(string $date): void
    {
        $this->updated_at = $date;
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function getStatusName(): string
    {
        $name = $this->getStatus();

        if (null !== ($orderState = new \OrderState((int)$name))) {
            if (\is_string($orderState->name)) {
                return $orderState->name;
            }

            if (\is_array($orderState->name) && !empty($orderState->name)) {
                return \array_pop($orderState->name);
            }
        }

        return $name;
    }

    public function getInvoiceId(): ?string
    {
        return $this->invoice_id;
    }

    public function setInvoiceId(?string $invoice_id): void
    {
        $this->invoice_id = $invoice_id;
    }

    public function getAmountSats(): ?string
    {
        return $this->amount_sats;
    }

    public function setAmountSats(?string $amount): void
    {
        $this->amount_sats = $amount;
    }

    public function getAmountFiat(): ?string
    {
        return $this->amount_fiat;
    }

    public function setAmountFiat(?string $amount): void
    {
        $this->amount_fiat = $amount;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
    }

    public function getCheckoutUrl(): ?string
    {
        return $this->checkout_url;
    }

    public function setCheckoutUrl(?string $url): void
    {
        $this->checkout_url = $url;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'cart_id' => $this->getCartId(),
            'id_order' => $this->getOrderId(),
            'status' => $this->getStatus(),
            'invoice_id' => $this->getInvoiceId(),
            'amount_sats' => $this->getAmountSats(),
            'amount_fiat' => $this->getAmountFiat(),
            'currency' => $this->getCurrency(),
            'checkout_url' => $this->getCheckoutUrl(),
        ];
    }


}
