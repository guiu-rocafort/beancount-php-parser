<?php

declare(strict_types=1);

namespace Beancount\Parser\Tests\Unit;

use Beancount\Parser\Tokenizer;
use Beancount\Parser\Token\TokenInterface;
use PHPUnit\Framework\TestCase;

final class TokenizerTest extends TestCase
{
    public function testTokenizeDate(): void
    {
        $tokenizer = new Tokenizer('2014-05-01');
        $tokens = $tokenizer->tokenize();

        self::assertSame(TokenInterface::DATE, $tokens[0]->getType());
        self::assertSame('2014-05-01', $tokens[0]->getValue());
    }

    public function testTokenizeDateWithSlashes(): void
    {
        $tokenizer = new Tokenizer('2014/05/01');
        $tokens = $tokenizer->tokenize();

        self::assertSame(TokenInterface::DATE, $tokens[0]->getType());
    }

    public function testTokenizeFlag(): void
    {
        $tokenizer = new Tokenizer('*');
        $tokens = $tokenizer->tokenize();

        self::assertSame(TokenInterface::FLAG, $tokens[0]->getType());
        self::assertSame('*', $tokens[0]->getValue());
    }

    public function testTokenizeExclamationFlag(): void
    {
        $tokenizer = new Tokenizer('!');
        $tokens = $tokenizer->tokenize();

        self::assertSame(TokenInterface::FLAG, $tokens[0]->getType());
        self::assertSame('!', $tokens[0]->getValue());
    }

    public function testTokenizeString(): void
    {
        $tokenizer = new Tokenizer('"Hello World"');
        $tokens = $tokenizer->tokenize();

        self::assertSame(TokenInterface::STRING, $tokens[0]->getType());
        self::assertSame('Hello World', $tokens[0]->getValue());
    }

    public function testTokenizeNumber(): void
    {
        $tokenizer = new Tokenizer('100');
        $tokens = $tokenizer->tokenize();

        self::assertSame(TokenInterface::NUMBER, $tokens[0]->getType());
        self::assertSame('100', $tokens[0]->getValue());
    }

    public function testTokenizeDecimalNumber(): void
    {
        $tokenizer = new Tokenizer('100.50');
        $tokens = $tokenizer->tokenize();

        self::assertSame(TokenInterface::NUMBER, $tokens[0]->getType());
        self::assertSame('100.50', $tokens[0]->getValue());
    }

    public function testTokenizeNegativeNumber(): void
    {
        $tokenizer = new Tokenizer('-100');
        $tokens = $tokenizer->tokenize();

        self::assertSame(TokenInterface::NUMBER, $tokens[0]->getType());
        self::assertSame('-100', $tokens[0]->getValue());
    }

    public function testTokenizeAccountName(): void
    {
        $tokenizer = new Tokenizer('Assets:US:BofA:Checking');
        $tokens = $tokenizer->tokenize();

        self::assertSame(TokenInterface::ACCOUNT, $tokens[0]->getType());
        self::assertStringStartsWith('Assets', $tokens[0]->getValue());
    }

    public function testTokenizeCurrency(): void
    {
        $tokenizer = new Tokenizer('USD');
        $tokens = $tokenizer->tokenize();

        self::assertSame(TokenInterface::CURRENCY, $tokens[0]->getType());
        self::assertSame('USD', $tokens[0]->getValue());
    }

    public function testTokenizeMultiCharCurrency(): void
    {
        $tokenizer = new Tokenizer('EUR');
        $tokens = $tokenizer->tokenize();

        self::assertSame(TokenInterface::CURRENCY, $tokens[0]->getType());
    }

    public function testTokenizeComment(): void
    {
        $tokenizer = new Tokenizer('; This is a comment');
        $tokens = $tokenizer->tokenize();

        self::assertNotEmpty($tokens);
    }

    public function testTokenizeTransactionLine(): void
    {
        $input = <<<'BEAN'
2014-05-05 * "Payee" "Narration"
  Assets:US:Checking  -100 USD
  Expenses:Food  100
BEAN;

        $tokenizer = new Tokenizer($input);
        $tokens = $tokenizer->tokenize();

        self::assertNotEmpty($tokens);
        self::assertSame(TokenInterface::DATE, $tokens[0]->getType());
        self::assertSame(TokenInterface::FLAG, $tokens[1]->getType());
    }

    public function testTokenizeTag(): void
    {
        $tokenizer = new Tokenizer('#my-tag');
        $tokens = $tokenizer->tokenize();

        self::assertSame(TokenInterface::TAG, $tokens[0]->getType());
        self::assertSame('my-tag', $tokens[0]->getValue());
    }

    public function testTokenizeLink(): void
    {
        $tokenizer = new Tokenizer('^invoice-001');
        $tokens = $tokenizer->tokenize();

        self::assertSame(TokenInterface::LINK, $tokens[0]->getType());
        self::assertSame('invoice-001', $tokens[0]->getValue());
    }

    public function testTokenizeCost(): void
    {
        $tokenizer = new Tokenizer('{183.07 USD}');
        $tokens = $tokenizer->tokenize();

        self::assertSame(TokenInterface::COST, $tokens[0]->getType());
    }

    public function testTokenizePriceAt(): void
    {
        $tokenizer = new Tokenizer('@ 1.09');
        $tokens = $tokenizer->tokenize();

        self::assertSame(TokenInterface::PRICE_AT, $tokens[0]->getType());
    }

    public function testTokenizeTotalPrice(): void
    {
        $tokenizer = new Tokenizer('@@');
        $tokens = $tokenizer->tokenize();

        self::assertSame(TokenInterface::TOTAL_PRICE, $tokens[0]->getType());
    }

    public function testTokenizeKeywords(): void
    {
        $keywords = ['open', 'close', 'balance', 'pad', 'note', 'document', 'commodity', 'price', 'event', 'query', 'custom', 'txn'];

        foreach ($keywords as $keyword) {
            $tokenizer = new Tokenizer($keyword);
            $tokens = $tokenizer->tokenize();

            self::assertNotEmpty($tokens, "Expected token for keyword: {$keyword}");
        }
    }

    public function testSkipsWhitespace(): void
    {
        $tokenizer = new Tokenizer('  100  ');
        $tokens = $tokenizer->tokenize();

        self::assertSame('100', $tokens[0]->getValue());
    }

    public function testEOLTokenGenerated(): void
    {
        $tokenizer = new Tokenizer("2014-01-01\n2014-01-02");
        $tokens = $tokenizer->tokenize();

        $eolCount = 0;
        foreach ($tokens as $token) {
            if ($token->getType() === TokenInterface::EOL) {
                $eolCount++;
            }
        }

        self::assertGreaterThan(0, $eolCount);
    }

    public function testEOFTokenAtEnd(): void
    {
        $tokenizer = new Tokenizer('test');
        $tokens = $tokenizer->tokenize();

        $lastToken = end($tokens);
        self::assertSame(TokenInterface::EOF, $lastToken->getType());
    }

    public function testTokenizeNumberWithPositiveSign(): void
    {
        $tokenizer = new Tokenizer('+100.50');
        $tokens = $tokenizer->tokenize();

        self::assertSame(TokenInterface::NUMBER, $tokens[0]->getType());
        self::assertSame('+100.50', $tokens[0]->getValue());
    }

    public function testTokenizeCurrencyTooLong(): void
    {
        // Max 24 chars for currency
        $longCurrency = str_repeat('A', 25);
        $tokenizer = new Tokenizer($longCurrency);
        $tokens = $tokenizer->tokenize();

        // Should be categorized as IDENTIFIER or something else, but not CURRENCY
        self::assertNotSame(TokenInterface::CURRENCY, $tokens[0]->getType());
    }

    public function testTokenizeInvalidNumberFormat(): void
    {
        $tokenizer = new Tokenizer('100.50.25');
        $tokens = $tokenizer->tokenize();

        // Should tokenize as 100.50 and then .25 or throw/IDENTIFIER
        self::assertSame(TokenInterface::NUMBER, $tokens[0]->getType());
        self::assertSame('100.50', $tokens[0]->getValue());
    }

    public function testTokenizeCommentAtStartOfInput(): void
    {
        $tokenizer = new Tokenizer('; First line comment');
        $tokens = $tokenizer->tokenize();
        
        // Comments are skipped in tokenize() loop, so should only have EOF if nothing else
        self::assertCount(1, $tokens);
        self::assertSame(TokenInterface::EOF, $tokens[0]->getType());
    }
}