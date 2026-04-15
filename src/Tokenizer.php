<?php

declare(strict_types=1);

namespace Beancount\Parser;

use Beancount\Parser\Token\Token;
use Beancount\Parser\Token\TokenInterface;

/**
 * Tokenizer (Lexer) for Beancount files.
 *
 * This class converts Beancount text content into a sequence of tokens
 * that can be processed by the Parser.
 *
 * @example
 * ```php
 * $tokenizer = new Tokenizer($content);
 * $tokens = $tokenizer->tokenize();
 * foreach ($tokens as $token) {
 *     echo $token->getType() . ': ' . $token->getValue() . PHP_EOL;
 * }
 * ```
 */
class Tokenizer
{
    private const DIRECTIVE_KEYWORDS = [
        'open', 'close', 'balance', 'pad', 'note', 'document',
        'commodity', 'price', 'event', 'query', 'custom', 'txn'
    ];

    private const VALID_ACCOUNT_TYPES = [
        'Assets', 'Liabilities', 'Equity', 'Income', 'Expenses'
    ];

    protected string $input;
    protected int $position = 0;
    protected int $line = 1;
    protected int $column = 1;

    /**
     * Creates a new Tokenizer instance.
     *
     * @param string $input The Beancount content to tokenize
     */
    public function __construct(string $input)
    {
        $this->input = $input;
    }

    /**
     * Tokenizes the input and returns an array of Tokens.
     *
     * @return array Array of Token objects
     */
    /** @return array<int, TokenInterface> */
    public function tokenize(): array
    {
        $tokens = [];
        $this->reset();

        while (!$this->isAtEnd()) {
            $token = $this->scanToken();
            if ($token === null) {
                continue;
            }

            if ($token->getType() === TokenInterface::COMMENT) {
                continue;
            }

            if ($token->getType() === TokenInterface::EOL) {
                $tokens[] = $token;
                continue;
            }

            $tokens[] = $token;
        }

        $tokens[] = new Token(TokenInterface::EOF, '', $this->line, $this->column);

        return $tokens;
    }

    protected function reset(): void
    {
        $this->position = 0;
        $this->line = 1;
        $this->column = 1;
    }

    protected function scanToken(): ?TokenInterface
    {
        $char = $this->peek();
        $startColumn = $this->column;

        if ($char === "\n") {
            $this->advance();
            return new Token(TokenInterface::EOL, "\n", $this->line, $this->column);
        }

        if ($char === ';' && $this->isAtCommentStart()) {
            return $this->scanComment();
        }

        if ($char === '"') {
            return $this->scanString();
        }

        if ($char === '{') {
            return $this->scanCost();
        }

        if ($char === '@') {
            return $this->scanPrice();
        }

        if ($char === '#') {
            return $this->scanTag();
        }

        if ($char === '^') {
            return $this->scanLink();
        }

        if (ctype_digit($char)) {
            return $this->scanDateOrNumber();
        }

        if ($char === '-' || $char === '+') {
            return $this->scanNumber();
        }

        if ($char === '*' || $char === '!') {
            $this->advance();
            return new Token(TokenInterface::FLAG, $char, $this->line, $this->column - 1);
        }

        if (ctype_alpha($char)) {
            return $this->scanIdentifier();
        }

        if (ctype_space($char)) {
            $this->advance();
            return null;
        }

        if ($char === '.') {
            $this->advance();
            return new Token(TokenInterface::NUMBER, '.', $this->line, $startColumn);
        }

        $this->advance();
        return null;
    }

    private function isAtCommentStart(): bool
    {
        $prev = $this->peek(-1);
        return $prev === "\n" || $prev === '' || $prev === ';';
    }

    private function scanComment(): Token
    {
        $this->advance();
        $value = '';

        while (!$this->isAtEnd() && $this->peek() !== "\n") {
            $value .= $this->advance();
        }

        return new Token(TokenInterface::COMMENT, $value, $this->line, $this->column);
    }

    private function scanString(): Token
    {
        $this->advance();
        $value = '';
        $startLine = $this->line;
        $startColumn = $this->column - 1;

        while (!$this->isAtEnd() && $this->peek() !== '"') {
            if ($this->peek() === "\n") {
                $this->line++;
                $this->column = 1;
            }
            $value .= $this->advance();
        }

        $this->advance();

        return new Token(TokenInterface::STRING, $value, $startLine, $startColumn);
    }

    private function scanCost(): Token
    {
        $startColumn = $this->column;
        $this->advance();
        $value = '';

        while (!$this->isAtEnd() && $this->peek() !== '}') {
            if ($this->peek() === "\n") {
                $this->line++;
                $this->column = 1;
            }
            $value .= $this->advance();
        }

        if (!$this->isAtEnd()) {
            $this->advance();
        }

        return new Token(TokenInterface::COST, $value, $this->line, $startColumn);
    }

    private function scanPrice(): Token
    {
        $startColumn = $this->column;
        $this->advance();

        if ($this->peek() === '@') {
            $this->advance();
            return new Token(TokenInterface::TOTAL_PRICE, '@@', $this->line, $startColumn);
        }

        return new Token(TokenInterface::PRICE_AT, '@', $this->line, $startColumn);
    }

    private function scanTag(): Token
    {
        $startColumn = $this->column;
        $this->advance();
        $value = '';

        while (!$this->isAtEnd() && !$this->isWhitespaceOrEOL($this->peek())) {
            $value .= $this->advance();
        }

        return new Token(TokenInterface::TAG, $value, $this->line, $startColumn);
    }

    private function scanLink(): Token
    {
        $startColumn = $this->column;
        $this->advance();
        $value = '';

        while (!$this->isAtEnd() && !$this->isWhitespaceOrEOL($this->peek())) {
            $value .= $this->advance();
        }

        return new Token(TokenInterface::LINK, $value, $this->line, $startColumn);
    }

    private function scanDateOrNumber(): Token
    {
        $startColumn = $this->column;
        $year = '';
        $month = '';
        $day = '';
        $separator1 = '';
        $separator2 = '';

        while (!$this->isAtEnd() && ctype_digit($this->peek())) {
            $year .= $this->advance();
        }

        if (strlen($year) !== 4) {
            // Check for decimal numbers like 100.50
            if (!$this->isAtEnd() && $this->peek() === '.') {
                $this->advance();
                $value = $year . '.';
                while (!$this->isAtEnd() && ctype_digit($this->peek())) {
                    $value .= $this->advance();
                }
                return new Token(TokenInterface::NUMBER, $value, $this->line, $startColumn);
            }
            return new Token(TokenInterface::NUMBER, $year, $this->line, $startColumn);
        }

        if ($this->isAtEnd()) {
            return new Token(TokenInterface::NUMBER, $year, $this->line, $startColumn);
        }

        $char = $this->peek();
        if ($char !== '-' && $char !== '/') {
            // Check for decimal numbers like 2024.50 (though weird)
            if ($char === '.') {
                $this->advance();
                $value = $year . '.';
                while (!$this->isAtEnd() && ctype_digit($this->peek())) {
                    $value .= $this->advance();
                }
                return new Token(TokenInterface::NUMBER, $value, $this->line, $startColumn);
            }
            return new Token(TokenInterface::NUMBER, $year, $this->line, $startColumn);
        }

        $separator1 = $this->advance();

        while (!$this->isAtEnd() && ctype_digit($this->peek())) {
            $month .= $this->advance();
        }

        if (strlen($month) !== 2) {
            $value = $year . $separator1 . $month;
            return new Token(TokenInterface::NUMBER, $value, $this->line, $startColumn);
        }

        if ($this->isAtEnd()) {
            $value = $year . $separator1 . $month;
            return new Token(TokenInterface::NUMBER, $value, $this->line, $startColumn);
        }

        $char = $this->peek();
        if ($char !== '-' && $char !== '/') {
            $value = $year . $separator1 . $month;
            return new Token(TokenInterface::NUMBER, $value, $this->line, $startColumn);
        }

        $separator2 = $this->advance();

        while (!$this->isAtEnd() && ctype_digit($this->peek())) {
            $day .= $this->advance();
        }

        if (strlen($day) !== 2) {
            $value = $year . $separator1 . $month . $separator2 . $day;
            return new Token(TokenInterface::NUMBER, $value, $this->line, $startColumn);
        }

        return new Token(TokenInterface::DATE, $year . $separator1 . $month . $separator2 . $day, $this->line, $startColumn);
    }

    private function scanNumber(): Token
    {
        $startColumn = $this->column;
        $value = '';
        $hasDecimal = false;
        $hasSign = false;

        $char = $this->peek();

        // Check for standalone * or ! (transaction flags after date)
        if ($char === '*' || $char === '!') {
            $this->advance();
            return new Token(TokenInterface::FLAG, $char, $this->line, $startColumn);
        }

        // Handle sign + digit/dot (for negative/positive numbers)
        if ($char === '-' || $char === '+') {
            if (!$this->isAtEnd()) {
                $nextChar = $this->peek(1);
                if (ctype_digit($nextChar) || $nextChar === '.') {
                    $hasSign = true;
                    $value .= $this->advance();
                }
            }
        }

        // Continue parsing number/digits
        while (!$this->isAtEnd()) {
            $char = $this->peek();

            if ($char === '.') {
                if (!$hasDecimal) {
                    $hasDecimal = true;
                    $value .= $this->advance();
                } else {
                    break;
                }
            } elseif (ctype_digit($char)) {
                $value .= $this->advance();
            } else {
                break;
            }
        }

        if ($hasSign && $value === '-') {
            return new Token(TokenInterface::NUMBER, $value, $this->line, $startColumn);
        }
        if ($hasSign && $value === '+') {
            return new Token(TokenInterface::NUMBER, $value, $this->line, $startColumn);
        }

        return new Token(TokenInterface::NUMBER, $value, $this->line, $startColumn);
    }

    private function scanIdentifier(): TokenInterface
    {
        $startColumn = $this->column;
        $value = '';

        while (!$this->isAtEnd()) {
            $char = $this->peek();

            if ($char === ':') {
                $value .= $this->advance();
                if (!$this->isAtEnd() && ctype_alpha($this->peek())) {
                    continue;
                }
                break;
            }

            if ($this->isWhitespaceOrEOL($char) || $char === ';' || $char === '{' || $char === '@' || $char === '#' || $char === '^') {
                break;
            }

            if (ctype_alnum($char) || $char === '-' || $char === '_' || $char === '.' || $char === "'") {
                $value .= $this->advance();
            } else {
                break;
            }
        }

        if ($value === '*' || $value === '!') {
            return new Token(TokenInterface::FLAG, $value, $this->line, $startColumn);
        }

        if (str_ends_with($value, ':') && !in_array($value, self::DIRECTIVE_KEYWORDS, true) && !$this->isAccountType(rtrim($value, ':'))) {
            return new Token(TokenInterface::KEY, rtrim($value, ':'), $this->line, $startColumn);
        }

        if (in_array($value, self::DIRECTIVE_KEYWORDS, true)) {
            return new Token(strtoupper($value), $value, $this->line, $startColumn);
        }

        if ($this->isAccountType($value)) {
            return new Token(TokenInterface::ACCOUNT, $value, $this->line, $startColumn);
        }

        if ($this->isValidCurrencyCode($value)) {
            return new Token(TokenInterface::CURRENCY, $value, $this->line, $startColumn);
        }

        if ($this->peek() === ':' || $this->peek(-1) === ':') {
            return new Token(TokenInterface::ACCOUNT, $value, $this->line, $startColumn);
        }

        return new Token(TokenInterface::IDENTIFIER, $value, $this->line, $startColumn);
    }

    private function isAccountType(string $value): bool
    {
        foreach (self::VALID_ACCOUNT_TYPES as $type) {
            if ($value === $type || str_starts_with($value, $type . ':')) {
                return true;
            }
        }
        return false;
    }

    private function isValidCurrencyCode(string $value): bool
    {
        if (strlen($value) > 24) {
            return false;
        }

        if (!ctype_upper($value[0])) {
            return false;
        }

        for ($i = 1; $i < strlen($value); $i++) {
            $char = $value[$i];
            if (!ctype_upper($char) && !ctype_digit($char) && strpos("'._-", $char) === false) {
                return false;
            }
        }

        return true;
    }

    private function isWhitespaceOrEOL(string $char): bool
    {
        return $char === "\n" || ctype_space($char);
    }

    private function peek(int $offset = 0): string
    {
        $pos = $this->position + $offset;
        if ($pos < 0 || $pos >= strlen($this->input)) {
            return '';
        }
        return $this->input[$pos];
    }

    private function advance(): string
    {
        if ($this->isAtEnd()) {
            return '';
        }

        $char = $this->input[$this->position++];
        if ($char === "\n") {
            $this->line++;
            $this->column = 1;
        } else {
            $this->column++;
        }
        return $char;
    }

    protected function isAtEnd(): bool
    {
        return $this->position >= strlen($this->input);
    }
}