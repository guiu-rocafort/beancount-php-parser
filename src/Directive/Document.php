<?php

declare(strict_types=1);

namespace Beancount\Parser\Directive;

final class Document implements DirectiveInterface
{
    private string $date;
    private string $account;
    private string $path;

    public function __construct(string $date, string $account, string $path)
    {
        $this->date = $date;
        $this->account = $account;
        $this->path = $path;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getDirectiveType(): string
    {
        return 'document';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'directive' => 'document',
            'date' => $this->date,
            'account' => $this->account,
            'path' => $this->path,
        ];
    }
}