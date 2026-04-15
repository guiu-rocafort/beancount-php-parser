<?php

declare(strict_types=1);

namespace Beancount\Parser\Directive;

interface DirectiveInterface
{
    public function getDirectiveType(): string;

    /** @return array<string, mixed> */
    public function toArray(): array;
}