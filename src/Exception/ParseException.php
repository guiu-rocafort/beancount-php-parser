<?php

declare(strict_types=1);

namespace Beancount\Parser\Exception;

use RuntimeException;

final class ParseException extends RuntimeException
{
    private int $errorLine;
    private int $errorColumn;

    public function __construct(string $message, int $line = 0, int $column = 0, ?\Throwable $previous = null)
    {
        $this->errorLine = $line;
        $this->errorColumn = $column;
        $fullMessage = $message;
        if ($line > 0) {
            $fullMessage .= sprintf(' at line %d, column %d', $line, $column);
        }
        parent::__construct($fullMessage, 0, $previous);
    }

    public function getErrorLine(): int
    {
        return $this->errorLine;
    }

    public function getErrorColumn(): int
    {
        return $this->errorColumn;
    }
}