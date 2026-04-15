<?php

declare(strict_types=1);

namespace Beancount\Parser\Directive;

final class Balance implements DirectiveInterface
{
    private string $date;
    private string $account;
    private string $amount;
    private string $currency;

    public function __construct(string $date, string $account, string $amount, string $currency)
    {
        $this->date = $date;
        $this->account = $account;
        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function getDate(): string
    {
        return $this->date;
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

    public function getDirectiveType(): string
    {
        return 'balance';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'directive' => 'balance',
            'date' => $this->date,
            'account' => $this->account,
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }
}