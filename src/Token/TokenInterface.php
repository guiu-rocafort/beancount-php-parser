<?php

declare(strict_types=1);

namespace Beancount\Parser\Token;

interface TokenInterface
{
    public const DATE = 'DATE';
    public const FLAG = 'FLAG';
    public const STRING = 'STRING';
    public const NUMBER = 'NUMBER';
    public const IDENTIFIER = 'IDENTIFIER';
    public const ACCOUNT = 'ACCOUNT';
    public const CURRENCY = 'CURRENCY';
    public const SYMBOL = 'SYMBOL';
    public const OPEN = 'OPEN';
    public const CLOSE = 'CLOSE';
    public const BALANCE = 'BALANCE';
    public const PAD = 'PAD';
    public const NOTE = 'NOTE';
    public const DOCUMENT = 'DOCUMENT';
    public const COMMODITY = 'COMMODITY';
    public const PRICE = 'PRICE';
    public const EVENT = 'EVENT';
    public const QUERY = 'QUERY';
    public const CUSTOM = 'CUSTOM';
    public const TXN = 'TXN';
    public const EOF = 'EOF';
    public const EOL = 'EOL';
    public const COST = 'COST';
    public const PRICE_AT = 'PRICE_AT';
    public const TOTAL_PRICE = 'TOTAL_PRICE';
    public const TAG = 'TAG';
    public const LINK = 'LINK';
    public const KEY = 'KEY';
    public const WHITESPACE = 'WHITESPACE';
    public const COMMENT = 'COMMENT';

    public function getType(): string;

    public function getValue(): string;

    public function getLine(): int;

    public function getColumn(): int;
}