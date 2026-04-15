<?php

declare(strict_types=1);

namespace Beancount\Parser\Directive;

final class Close implements DirectiveInterface
{
    private string $date;
    private string $account;

    public function __construct(string $date, string $account)
    {
        $this->date = $date;
        $this->account = $account;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    public function getDirectiveType(): string
    {
        return 'close';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'directive' => 'close',
            'date' => $this->date,
            'account' => $this->account,
        ];
    }
}