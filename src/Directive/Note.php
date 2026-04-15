<?php

declare(strict_types=1);

namespace Beancount\Parser\Directive;

final class Note implements DirectiveInterface
{
    private string $date;
    private string $account;
    private string $message;

    public function __construct(string $date, string $account, string $message)
    {
        $this->date = $date;
        $this->account = $account;
        $this->message = $message;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getDirectiveType(): string
    {
        return 'note';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'directive' => 'note',
            'date' => $this->date,
            'account' => $this->account,
            'message' => $this->message,
        ];
    }
}