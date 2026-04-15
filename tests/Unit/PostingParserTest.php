<?php

declare(strict_types=1);

namespace Beancount\Parser\Tests\Unit;

use Beancount\Parser\Parser;
use PHPUnit\Framework\TestCase;

final class PostingParserTest extends TestCase
{
    public function testParseBasicPosting(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:Checking  100 USD
  Expenses:Food  -100 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][0];
        self::assertSame('Assets:Checking', $posting['account']);
        self::assertSame('100', $posting['amount']);
        self::assertSame('USD', $posting['currency']);
    }

    public function testParsePostingWithCostBasis(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Buy"
  Assets:Investments  10 IVV {183.07 USD}
  Assets:Cash  -1830.70 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][0];
        self::assertSame('183.07', $posting['cost']);
        self::assertSame('USD', $posting['cost_currency']);
    }

    public function testParsePostingWithCostBasisAmount(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Buy"
  Assets:Investments  10 IVV {183.07 USD}
  Assets:Cash  -1830.70 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][0];
        self::assertSame('10', $posting['amount']);
        self::assertSame('IVV', $posting['currency']);
    }

    public function testParsePostingWithPricePerUnit(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Exchange"
  Assets:US  -100 USD @ 0.73 EUR
  Assets:EU  73 EUR
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][0];
        self::assertSame('0.73', $posting['price']);
        self::assertSame('EUR', $posting['price_currency']);
        self::assertSame('per-unit', $posting['price_type']);
    }

    public function testParsePostingWithTotalPrice(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Exchange"
  Assets:US  -100 USD @@ 73 EUR
  Assets:EU  73 EUR
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][0];
        self::assertSame('73', $posting['price']);
        self::assertSame('EUR', $posting['price_currency']);
        self::assertSame('total', $posting['price_type']);
    }

    public function testParsePostingWithCostAndPrice(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Buy"
  Assets:Investments  10 IVV {183.07 USD} @ 190 USD
  Assets:Cash  -1900 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][0];
        self::assertSame('183.07', $posting['cost']);
        self::assertSame('190', $posting['price']);
    }

    public function testParsePostingWithCostTotalAndTotalPrice(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Sell"
  Assets:I  -10 IVV {183.07 USD} @@ 1979.00 USD
  Assets:C  1979.00 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][0];
        self::assertSame('183.07', $posting['cost']);
        self::assertSame('USD', $posting['cost_currency']);
    }

    public function testParsePostingWithCurrencyOnly(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:Checking  100
  Expenses:Food  -100
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][0];
        self::assertSame('100', $posting['amount']);
        self::assertEmpty($posting['currency']);
    }

    public function testParsePostingAccountOnly(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Transfer"
  Assets:A  -100 USD
  Assets:B
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][1];
        self::assertSame('Assets:B', $posting['account']);
    }

    public function testParsePostingWithNegativeAmount(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:Checking  -100 USD
  Expenses:Food  100 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][0];
        self::assertSame('-100', $posting['amount']);
    }

    public function testParsePostingWithDecimalAmount(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:Checking  100.50 USD
  Expenses:Food  -100.50 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][0];
        self::assertSame('100.50', $posting['amount']);
    }

    public function testParsePostingWithZeroAmount(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:Checking  0 USD
  Expenses:Food  0 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][0];
        self::assertSame('0', $posting['amount']);
    }

    public function testParsePostingWithLargeAmount(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:Checking  1000000 USD
  Expenses:Food  -1000000 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][0];
        self::assertSame('1000000', $posting['amount']);
    }

    public function testParsePostingWithNestedAccount(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:US:BofA:Checking  100 USD
  Expenses:Food:Groceries  -100 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][0];
        self::assertSame('Assets:US:BofA:Checking', $posting['account']);
    }

    public function testParsePostingWithStockTickerCurrency(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Buy"
  Assets:Investments  10 MSFT {43.40 USD}
  Assets:Cash  -434 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][0];
        self::assertSame('MSFT', $posting['currency']);
    }

    public function testParsePostingMetadata(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:A  100 USD
  date: "2024-01-01"
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][0];
        self::assertIsArray($posting['metadata']);
    }

    public function testParseMultiplePostings(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:A  50 USD
  Assets:B  30 USD
  Expenses:C  -80 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertCount(3, $result[0]['postings']);
    }

    public function testParsePostingWithDecimalCost(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Buy"
  Assets:I  5 AAPL {123.456789 USD}
  Assets:C  -617.283945 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][0];
        self::assertSame('123.456789', $posting['cost']);
    }

    public function testParsePostingWithOnlyCostBasis(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Buy"
  Assets:Investments  10 IVV {183.07 USD}
  Assets:Cash
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][0];
        self::assertSame('183.07', $posting['cost']);
        self::assertNull($posting['price']);
    }

    public function testParsePostingWithOnlyPrice(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Exchange"
  Assets:US  -100 USD @ 0.73 EUR
  Assets:EU
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $posting = $result[0]['postings'][0];
        self::assertNull($posting['cost']);
        self::assertSame('0.73', $posting['price']);
    }

    public function testParsePostingWithMixedMetadataTypes(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Metadata"
  Assets:A  100 USD
    key1: "string value"
    key2: 123.45
    key3: some_identifier
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $metadata = $result[0]['postings'][0]['metadata'];
        self::assertSame('string value', $metadata['key1']);
        self::assertSame('123.45', $metadata['key2']);
        self::assertSame('some_identifier', $metadata['key3']);
    }
}