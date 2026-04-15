<?php

declare(strict_types=1);

namespace Beancount\Parser\Directive;

final class Transaction implements DirectiveInterface
{
    private string $date;
    private string $flag;
    private ?string $payee = null;
    private ?string $narration = null;
    /** @var array<int, string> */
    private array $tags = [];
    /** @var array<int, string> */
    private array $links = [];
    /** @var array<int, Posting> */
    private array $postings = [];
    /** @var array<string, mixed> */
    private array $metadata = [];

    /**
     * @param array<int, string> $tags
     * @param array<int, string> $links
     * @param array<int, Posting> $postings
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $date,
        string $flag = '*',
        ?string $payee = null,
        ?string $narration = null,
        array $tags = [],
        array $links = [],
        array $postings = [],
        array $metadata = []
    ) {
        $this->date = $date;
        $this->flag = $flag;
        $this->payee = $payee;
        $this->narration = $narration;
        $this->tags = $tags;
        $this->links = $links;
        $this->postings = $postings;
        $this->metadata = $metadata;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getFlag(): string
    {
        return $this->flag;
    }

    public function getPayee(): ?string
    {
        return $this->payee;
    }

    public function getNarration(): ?string
    {
        return $this->narration;
    }

    /** @return array<int, string> */
    public function getTags(): array
    {
        return $this->tags;
    }

    /** @return array<int, string> */
    public function getLinks(): array
    {
        return $this->links;
    }

    /** @return array<int, Posting> */
    public function getPostings(): array
    {
        return $this->postings;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getDirectiveType(): string
    {
        return 'transaction';
    }

    /** @return array<string, mixed> */
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'directive' => 'transaction',
            'date' => $this->date,
            'flag' => $this->flag,
            'payee' => $this->payee,
            'narration' => $this->narration,
            'tags' => $this->tags,
            'links' => $this->links,
            'postings' => array_map(fn(Posting $p) => $p->toArray(), $this->postings),
            'metadata' => $this->metadata,
        ];
    }
}