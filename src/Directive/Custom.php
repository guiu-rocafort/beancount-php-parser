<?php

declare(strict_types=1);

namespace Beancount\Parser\Directive;

final class Custom implements DirectiveInterface
{
    private string $date;
    private string $name;
    /** @var array<int, string> */
    private array $values = [];

    /** @param array<int, string> $values */
    public function __construct(string $date, string $name, array $values = [])
    {
        $this->date = $date;
        $this->name = $name;
        $this->values = $values;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return array<int, string> */
    public function getValues(): array
    {
        return $this->values;
    }

    public function getDirectiveType(): string
    {
        return 'custom';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'directive' => 'custom',
            'date' => $this->date,
            'name' => $this->name,
            'values' => $this->values,
        ];
    }
}