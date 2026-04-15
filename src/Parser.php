<?php

declare(strict_types=1);

namespace Beancount\Parser;

use Beancount\Parser\Directive\Balance;
use Beancount\Parser\Directive\Close;
use Beancount\Parser\Directive\Commodity;
use Beancount\Parser\Directive\Custom;
use Beancount\Parser\Directive\Document;
use Beancount\Parser\Directive\Event;
use Beancount\Parser\Directive\Note;
use Beancount\Parser\Directive\Open;
use Beancount\Parser\Directive\Pad;
use Beancount\Parser\Directive\Posting;
use Beancount\Parser\Directive\Price;
use Beancount\Parser\Directive\Query;
use Beancount\Parser\Directive\Transaction;
use Beancount\Parser\Exception\ParseException;
use Beancount\Parser\Token\TokenInterface;
use Beancount\Parser\Directive\DirectiveInterface;

/**
 * Main parser for Beancount double-entry bookkeeping files.
 *
 * This class parses Beancount-format files and returns an array of directive arrays.
 * Each directive contains the parsed data including dates, accounts, amounts, and transactions.
 *
 * @example
 * ```php
 * $parser = new Parser($beancountContent);
 * $entries = $parser->parse();
 * foreach ($entries as $entry) {
 *     echo $entry['directive'] . PHP_EOL;
 * }
 * ```
 */
class Parser
{
    /** @var array<int, TokenInterface> */
    private array $tokens = [];
    private int $position = 0;

    /**
     * Creates a new Parser instance.
     *
     * @param string $input The Beancount file content to parse
     */
    public function __construct(string $input)
    {
        $tokenizer = new Tokenizer($input);
        $this->tokens = $tokenizer->tokenize();
    }

    /**
     * Parses the Beancount content and returns an array of directives.
     *
     * @return array<int, array<string, mixed>> Array of directive arrays with 'directive', 'date', and other fields
     */
    public function parse(): array
    {
        /** @var array<int, array<string, mixed>> */
        $directives = [];
        $this->position = 0;

        while (!$this->isAtEnd()) {
            $directive = $this->parseDirective();
            if ($directive !== null) {
                $directives[] = $directive->toArray();
            }
        }

        return $directives;
    }

    /**
     * Parses a single directive from the current position.
     *
     * @return DirectiveInterface|null The parsed directive or null if no directive found
     * @throws ParseException If parsing fails
     */
    private function parseDirective(): ?DirectiveInterface
    {
        $this->skipEOL();

        if ($this->isAtEnd()) {
            return null;
        }

        $token = $this->peek();

        if ($token->getType() === TokenInterface::DATE) {
            return $this->parseDatedDirective();
        }

        $this->advance();
        return null;
    }

    private function parseDatedDirective(): ?DirectiveInterface
    {
        $dateToken = $this->advance();
        $date = $dateToken->getValue();

        $this->skipEOL();

        if ($this->isAtEnd()) {
            throw new ParseException('Expected directive after date', $dateToken->getLine(), $dateToken->getColumn());
        }

        $typeToken = $this->peek();
        $type = $typeToken->getValue();
        $typeTokenType = $typeToken->getType();

        if ($typeTokenType === TokenInterface::FLAG) {
            $this->advance();
            return $this->parseTransaction($date, $type);
        }

        // Handle directive keyword tokens
        if (in_array($typeTokenType, [
            TokenInterface::OPEN,
            TokenInterface::CLOSE,
            TokenInterface::BALANCE,
            TokenInterface::COMMODITY,
            TokenInterface::PAD,
            TokenInterface::NOTE,
            TokenInterface::DOCUMENT,
            TokenInterface::PRICE,
            TokenInterface::EVENT,
            TokenInterface::QUERY,
            TokenInterface::CUSTOM,
            TokenInterface::TXN,
        ]) || $typeTokenType === TokenInterface::IDENTIFIER) {
            $this->advance();

            return match ($typeTokenType) {
                TokenInterface::OPEN => $this->parseOpen($date),
                TokenInterface::CLOSE => $this->parseClose($date),
                TokenInterface::BALANCE => $this->parseBalance($date),
                TokenInterface::COMMODITY => $this->parseCommodity($date),
                TokenInterface::PAD => $this->parsePad($date),
                TokenInterface::NOTE => $this->parseNote($date),
                TokenInterface::DOCUMENT => $this->parseDocument($date),
                TokenInterface::PRICE => $this->parsePrice($date),
                TokenInterface::EVENT => $this->parseEvent($date),
                TokenInterface::QUERY => $this->parseQuery($date),
                TokenInterface::CUSTOM => $this->parseCustom($date),
                TokenInterface::TXN => $this->parseTransaction($date, '*'),
                default => match ($type) {
                    'txn' => $this->parseTransaction($date, '*'),
                    default => null,
                },
            };
        }

        $this->advance();
        return null;
    }

    private function parseOpen(string $date): Open
    {
        $account = $this->expectAccount();
        $currencies = [];
        $bookingMethod = null;

        $this->skipEOL();

        while (!$this->isAtEnd() && !$this->isNextTokenEOL()) {
            $token = $this->peek();
            if ($token->getType() === TokenInterface::CURRENCY) {
                $currencies[] = $this->advance()->getValue();
            } elseif ($token->getType() === TokenInterface::STRING) {
                $bookingMethod = $this->advance()->getValue();
            } else {
                $this->advance();
            }
        }

        return new Open($date, $account, $currencies, $bookingMethod);
    }

    private function parseClose(string $date): Close
    {
        $account = $this->expectAccount();
        return new Close($date, $account);
    }

    private function parseBalance(string $date): Balance
    {
        $account = $this->expectAccount();
        $amountToken = $this->expect(TokenInterface::NUMBER);
        $currencyToken = $this->expect(TokenInterface::CURRENCY);

        return new Balance($date, $account, $amountToken->getValue(), $currencyToken->getValue());
    }

    private function parseCommodity(string $date): Commodity
    {
        $currency = $this->expect(TokenInterface::CURRENCY);
        $metadata = $this->parseMetadata();
        return new Commodity($date, $currency->getValue(), $metadata);
    }

    private function parsePad(string $date): Pad
    {
        $account = $this->expectAccount();
        $sourceAccount = $this->expectAccount();
        $amountToken = $this->expect(TokenInterface::NUMBER);
        $currencyToken = $this->expect(TokenInterface::CURRENCY);

        return new Pad($date, $account, $sourceAccount, $amountToken->getValue(), $currencyToken->getValue());
    }

    private function parseNote(string $date): Note
    {
        $account = $this->expectAccount();
        $message = $this->expect(TokenInterface::STRING);

        return new Note($date, $account, $message->getValue());
    }

    private function parseDocument(string $date): Document
    {
        $account = $this->expectAccount();
        $path = $this->expect(TokenInterface::STRING);

        return new Document($date, $account, $path->getValue());
    }

    private function parsePrice(string $date): Price
    {
        $currency = $this->expect(TokenInterface::CURRENCY);
        $amountToken = $this->expect(TokenInterface::NUMBER);
        $currencyToken = $this->expect(TokenInterface::CURRENCY);

        return new Price($date, $currency->getValue(), $amountToken->getValue(), $currencyToken->getValue());
    }

    private function parseEvent(string $date): Event
    {
        $type = $this->expect(TokenInterface::STRING);
        $description = $this->expect(TokenInterface::STRING);

        return new Event($date, $type->getValue(), $description->getValue());
    }

    private function parseQuery(string $date): Query
    {
        $name = $this->expect(TokenInterface::STRING);
        $query = $this->expect(TokenInterface::STRING);

        return new Query($date, $name->getValue(), $query->getValue());
    }

    private function parseCustom(string $date): Custom
    {
        $name = $this->expect(TokenInterface::STRING);
        $values = [];

        while (!$this->isAtEnd() && !$this->isNextTokenEOL()) {
            $token = $this->peek();
            $values[] = $this->advance()->getValue();
        }

        return new Custom($date, $name->getValue(), $values);
    }

    private function parseTransaction(string $date, string $flag): Transaction
    {
        $payee = null;
        $narration = null;
        $tags = [];
        $links = [];
        $metadata = [];
        $postings = [];

        $this->skipEOL();

        if (!$this->isAtEnd() && !$this->isNextTokenEOL()) {
            $token = $this->peek();

            if ($token->getType() === TokenInterface::STRING) {
                $payee = $this->advance()->getValue();

                $this->skipEOL();
                if (!$this->isAtEnd() && !$this->isNextTokenEOL()) {
                    $nextToken = $this->peek();
                    if ($nextToken->getType() === TokenInterface::STRING) {
                        $narration = $this->advance()->getValue();
                    }
                }
            }
        }

        while (!$this->isAtEnd()) {
            $this->skipEOL();

            if ($this->isAtEnd()) {
                break;
            }

            $token = $this->peek();

            if ($token->getType() === TokenInterface::TAG) {
                $tags[] = $this->advance()->getValue();
                continue;
            }

            if ($token->getType() === TokenInterface::LINK) {
                $links[] = $this->advance()->getValue();
                continue;
            }

            if ($token->getType() === TokenInterface::KEY) {
                $metadata = array_merge($metadata, $this->parseMetadata());
                continue;
            }

            if ($token->getType() === TokenInterface::ACCOUNT || $token->getType() === TokenInterface::IDENTIFIER) {
                $posting = $this->parsePosting();
                if ($posting !== null) {
                    $postings[] = $posting;
                }
                continue;
            }

            if ($token->getType() === TokenInterface::EOL) {
                $this->advance();
                continue;
            }

            if ($token->getType() === TokenInterface::EOF) {
                break;
            }

            $this->advance();
        }

        return new Transaction($date, $flag, $payee, $narration, $tags, $links, $postings, $metadata);
    }

    private function parsePosting(): ?Posting
    {
        $account = $this->parseAccountName();
        if ($account === null) {
            return null;
        }

        $amount = null;
        $currency = null;
        $cost = null;
        $costCurrency = null;
        $price = null;
        $priceCurrency = null;
        $priceType = null;
        $metadata = [];

        $this->skipEOL();

        if (!$this->isAtEnd()) {
            $token = $this->peek();

            if ($token->getType() === TokenInterface::NUMBER) {
                $this->advance();
                $amount = $token->getValue();

                $this->skipEOL();
                if (!$this->isAtEnd()) {
                    $currencyToken = $this->peek();
                    if ($currencyToken->getType() === TokenInterface::CURRENCY) {
                        $currency = $this->advance()->getValue();
                    } elseif ($currencyToken->getType() === TokenInterface::IDENTIFIER) {
                        $currency = $this->advance()->getValue();
                    }
                }
            }
        }

        $this->skipEOL();

        if (!$this->isAtEnd()) {
            $token = $this->peek();

            if ($token->getType() === TokenInterface::COST) {
                $costStr = $this->advance()->getValue();
                $costParts = $this->parseCostOrPriceParts($costStr);
                $cost = $costParts['amount'];
                $costCurrency = $costParts['currency'];
            }

            if (!$this->isAtEnd()) {
                $priceToken = $this->peek();

                if ($priceToken->getType() === TokenInterface::PRICE_AT) {
                    $this->advance();
                    $priceType = 'per-unit';
                    $priceParts = $this->parsePriceAmount();
                    $price = $priceParts['amount'];
                    $priceCurrency = $priceParts['currency'];
                } elseif ($priceToken->getType() === TokenInterface::TOTAL_PRICE) {
                    $this->advance();
                    $priceType = 'total';
                    $priceParts = $this->parsePriceAmount();
                    $price = $priceParts['amount'];
                    $priceCurrency = $priceParts['currency'];
                }
            }
        }

        $this->skipEOL();

        if (!$this->isAtEnd()) {
            $token = $this->peek();
            if ($token->getType() === TokenInterface::KEY) {
                $metadata = $this->parseMetadata();
            }
        }

        if ($amount !== null && $currency !== null) {
            return new Posting(
                $account,
                $amount,
                $currency,
                $cost,
                $costCurrency,
                $price,
                $priceCurrency,
                $priceType,
                $metadata
            );
        }

        if ($amount !== null) {
            return new Posting($account, $amount, '', null, null, null, null, null, $metadata);
        }

        return new Posting($account, '', '', null, null, null, null, null, $metadata);
    }

    /** @return array{amount: string, currency: string} */
    private function parseCostOrPriceParts(string $value): array
    {
        $parts = preg_split('/\s+/', trim($value));
        return [
            'amount' => $parts[0] ?? '',
            'currency' => $parts[1] ?? '',
        ];
    }

    /** @return array<string, string|null> */
    private function parsePriceAmount(): array
    {
        $this->skipEOL();

        $amount = null;
        $currency = null;

        if (!$this->isAtEnd()) {
            $token = $this->peek();
            if ($token->getType() === TokenInterface::NUMBER) {
                $amount = $this->advance()->getValue();

                $this->skipEOL();
                if (!$this->isAtEnd()) {
                    $currencyToken = $this->peek();
                    if ($currencyToken->getType() === TokenInterface::CURRENCY) {
                        $currency = $this->advance()->getValue();
                    }
                }
            }
        }

        return [
            'amount' => $amount,
            'currency' => $currency,
        ];
    }

    /** @return array<string, mixed> */
    private function parseMetadata(): array
    {
        $metadata = [];

        while (!$this->isAtEnd()) {
            $this->skipEOL();

            if ($this->isAtEnd()) {
                break;
            }

            $token = $this->peek();

            if ($token->getType() !== TokenInterface::KEY) {
                break;
            }

            $key = $this->advance()->getValue();

            $this->skipEOL();

            $value = '';
            if (!$this->isAtEnd()) {
                $valueToken = $this->peek();
                if ($valueToken->getType() === TokenInterface::STRING) {
                    $value = $this->advance()->getValue();
                } elseif ($valueToken->getType() === TokenInterface::NUMBER) {
                    $value = $this->advance()->getValue();
                } elseif ($valueToken->getType() === TokenInterface::IDENTIFIER) {
                    $value = $this->advance()->getValue();
                }
            }

            $metadata[$key] = $value;
        }

        return $metadata;
    }

    private function expectAccount(): string
    {
        $token = $this->expect(TokenInterface::ACCOUNT);

        $account = $token->getValue();

        while (!$this->isAtEnd()) {
            $nextToken = $this->peek();
            if ($nextToken->getType() === TokenInterface::IDENTIFIER && $nextToken->getValue() === ':') {
                $this->advance();
                $account .= ':';

                if (!$this->isAtEnd()) {
                    $partToken = $this->peek();
                    if ($partToken->getType() === TokenInterface::ACCOUNT || $partToken->getType() === TokenInterface::IDENTIFIER) {
                        $part = $this->advance()->getValue();
                        $account .= $part;
                    }
                }
            } else {
                break;
            }
        }

        return $account;
    }

    private function parseAccountName(): ?string
    {
        if ($this->isAtEnd()) {
            return null;
        }

        $token = $this->peek();

        if ($token->getType() !== TokenInterface::ACCOUNT && $token->getType() !== TokenInterface::IDENTIFIER) {
            return null;
        }

        $account = $this->advance()->getValue();

        while (!$this->isAtEnd()) {
            $nextToken = $this->peek();
            if ($nextToken->getType() === TokenInterface::IDENTIFIER && $nextToken->getValue() === ':') {
                $this->advance();
                $account .= ':';

                if (!$this->isAtEnd()) {
                    $partToken = $this->peek();
                    $partType = $partToken->getType();
                    if ($partType === TokenInterface::ACCOUNT || $partType === TokenInterface::IDENTIFIER) {
                        $part = $this->advance()->getValue();
                        $account .= $part;
                    } else {
                        break;
                    }
                }
            } else {
                break;
            }
        }

        return $account;
    }

    /**
     * @param string $type
     * @return TokenInterface
     */
    private function expect(string $type): TokenInterface
    {
        if ($this->isAtEnd()) {
            throw new ParseException("Expected token type {$type} but reached end of input", $this->peekLine(), $this->peekColumn());
        }

        $token = $this->advance();

        if ($token->getType() !== $type) {
            throw new ParseException(
                "Expected token type {$type} but got {$token->getType()}",
                $token->getLine(),
                $token->getColumn()
            );
        }

        return $token;
    }

    private function peek(): TokenInterface
    {
        return $this->tokens[$this->position] ?? new \Beancount\Parser\Token\Token(TokenInterface::EOF, '', 0, 0);
    }

    private function advance(): TokenInterface
    {
        return $this->tokens[$this->position++];
    }

    private function isAtEnd(): bool
    {
        return $this->peek()->getType() === TokenInterface::EOF;
    }

    private function isNextTokenEOL(): bool
    {
        return $this->peek()->getType() === TokenInterface::EOL;
    }

    private function skipEOL(): void
    {
        while (!$this->isAtEnd() && $this->peek()->getType() === TokenInterface::EOL) {
            $this->advance();
        }
    }

    private function peekLine(): int
    {
        return $this->peek()->getLine();
    }

    private function peekColumn(): int
    {
        return $this->peek()->getColumn();
    }
}