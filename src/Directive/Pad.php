<?php

declare(strict_types=1);

namespace Beancount\Parser\Directive;

final class Pad implements DirectiveInterface
{
    private string $date;
    private string $account;
    private string $sourceAccount;
    private string $amount;
    private string $currency;

    public function __construct(string $date, string $account, string $sourceAccount, string $amount, string $currency)
    {
        $this->date = $date;
        $this->account = $account;
        $this->sourceAccount = $sourceAccount;
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

    public function getSourceAccount(): string
    {
        return $this->sourceAccount;
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
        return 'pad';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'directive' => 'pad',
            'date' => $this->date,
            'account' => $this->account,
            'source_account' => $this->sourceAccount,
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }
}