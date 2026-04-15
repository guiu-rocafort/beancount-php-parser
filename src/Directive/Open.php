<?php

declare(strict_types=1);

namespace Beancount\Parser\Directive;

final class Open implements DirectiveInterface
{
    private string $date;
    private string $account;
    /** @var array<int, string> */
    private array $currencies = [];
    private ?string $bookingMethod = null;

    /** @param array<int, string> $currencies */
    public function __construct(string $date, string $account, array $currencies = [], ?string $bookingMethod = null)
    {
        $this->date = $date;
        $this->account = $account;
        $this->currencies = $currencies;
        $this->bookingMethod = $bookingMethod;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    /** @return array<int, string> */
    public function getCurrencies(): array
    {
        return $this->currencies;
    }

    public function getBookingMethod(): ?string
    {
        return $this->bookingMethod;
    }

    public function getDirectiveType(): string
    {
        return 'open';
    }

    /** @return array<string, mixed> */
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'directive' => 'open',
            'date' => $this->date,
            'account' => $this->account,
            'currencies' => $this->currencies,
            'booking_method' => $this->bookingMethod,
        ];
    }
}