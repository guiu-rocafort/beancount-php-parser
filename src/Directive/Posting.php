<?php

declare(strict_types=1);

namespace Beancount\Parser\Directive;

final class Posting implements DirectiveInterface
{
    private string $account;
    private string $amount;
    private string $currency;
    private ?string $cost = null;
    private ?string $costCurrency = null;
    private ?string $price = null;
    private ?string $priceCurrency = null;
    private ?string $priceType = null;
    /** @var array<string, mixed> */
    private array $metadata = [];

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $account,
        string $amount,
        string $currency,
        ?string $cost = null,
        ?string $costCurrency = null,
        ?string $price = null,
        ?string $priceCurrency = null,
        ?string $priceType = null,
        array $metadata = []
    ) {
        $this->account = $account;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->cost = $cost;
        $this->costCurrency = $costCurrency;
        $this->price = $price;
        $this->priceCurrency = $priceCurrency;
        $this->priceType = $priceType;
        $this->metadata = $metadata;
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function getCostCurrency(): ?string
    {
        return $this->costCurrency;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function getPriceCurrency(): ?string
    {
        return $this->priceCurrency;
    }

    public function getPriceType(): ?string
    {
        return $this->priceType;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getDirectiveType(): string
    {
        return 'posting';
    }

    /** @return array<string, mixed> */
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'directive' => 'posting',
            'account' => $this->account,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'cost' => $this->cost,
            'cost_currency' => $this->costCurrency,
            'price' => $this->price,
            'price_currency' => $this->priceCurrency,
            'price_type' => $this->priceType,
            'metadata' => $this->metadata,
        ];
    }
}