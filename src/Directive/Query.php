<?php

declare(strict_types=1);

namespace Beancount\Parser\Directive;

final class Query implements DirectiveInterface
{
    private string $date;
    private string $name;
    private string $queryString;

    public function __construct(string $date, string $name, string $queryString)
    {
        $this->date = $date;
        $this->name = $name;
        $this->queryString = $queryString;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQueryString(): string
    {
        return $this->queryString;
    }

    public function getDirectiveType(): string
    {
        return 'query';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'directive' => 'query',
            'date' => $this->date,
            'name' => $this->name,
            'query' => $this->queryString,
        ];
    }
}