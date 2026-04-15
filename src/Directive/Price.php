<?php

declare(strict_types=1);

namespace Beancount\Parser\Directive;

final class Price implements DirectiveInterface
{
    private string $date;
    private string $currency;
    private string $amount;
    private string $priceCurrency;

    public function __construct(string $date, string $currency, string $amount, string $priceCurrency)
    {
        $this->date = $date;
        $this->currency = $currency;
        $this->amount = $amount;
        $this->priceCurrency = $priceCurrency;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getPriceCurrency(): string
    {
        return $this->priceCurrency;
    }

    public function getDirectiveType(): string
    {
        return 'price';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'directive' => 'price',
            'date' => $this->date,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'price_currency' => $this->priceCurrency,
        ];
    }
}