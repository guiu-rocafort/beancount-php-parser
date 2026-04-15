<?php

declare(strict_types=1);

namespace Beancount\Parser\Directive;

final class Event implements DirectiveInterface
{
    private string $date;
    private string $type;
    private string $description;

    public function __construct(string $date, string $type, string $description)
    {
        $this->date = $date;
        $this->type = $type;
        $this->description = $description;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDirectiveType(): string
    {
        return 'event';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'directive' => 'event',
            'date' => $this->date,
            'type' => $this->type,
            'description' => $this->description,
        ];
    }
}