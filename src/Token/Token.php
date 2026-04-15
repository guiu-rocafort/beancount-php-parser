<?php

declare(strict_types=1);

namespace Beancount\Parser\Token;

final class Token implements TokenInterface
{
    private string $type;
    private string $value;
    private int $line;
    private int $column;

    public function __construct(string $type, string $value, int $line, int $column)
    {
        $this->type = $type;
        $this->value = $value;
        $this->line = $line;
        $this->column = $column;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getColumn(): int
    {
        return $this->column;
    }

    public function __toString(): string
    {
        return sprintf('<%s: %s at %d:%d>', $this->type, $this->value, $this->line, $this->column);
    }
}