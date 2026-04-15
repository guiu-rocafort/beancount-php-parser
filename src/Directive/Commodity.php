<?php

declare(strict_types=1);

namespace Beancount\Parser\Directive;

final class Commodity implements DirectiveInterface
{
    private string $date;
    private string $currency;
    /** @var array<string, mixed> */
    private array $metadata = [];

    /** @param array<string, mixed> $metadata */
    public function __construct(string $date, string $currency, array $metadata = [])
    {
        $this->date = $date;
        $this->currency = $currency;
        $this->metadata = $metadata;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getDirectiveType(): string
    {
        return 'commodity';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'directive' => 'commodity',
            'date' => $this->date,
            'currency' => $this->currency,
            'metadata' => $this->metadata,
        ];
    }
}