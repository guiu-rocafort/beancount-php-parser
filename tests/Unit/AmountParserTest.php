<?php

declare(strict_types=1);

namespace Beancount\Parser\Tests\Unit;

use Beancount\Parser\Parser;
use PHPUnit\Framework\TestCase;

final class AmountParserTest extends TestCase
{
    public function testParseIntegerAmount(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:Checking  100 USD
  Expenses:Test  -100 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('100', $result[0]['postings'][0]['amount']);
    }

    public function testParseDecimalAmount(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:Checking  100.50 USD
  Expenses:Test  -100.50 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('100.50', $result[0]['postings'][0]['amount']);
    }

    public function testParseNegativeAmount(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:Checking  -100 USD
  Expenses:Test  100 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('-100', $result[0]['postings'][0]['amount']);
    }

    public function testParseAmountWithZero(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:Checking  0 USD
  Expenses:Test  0 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('0', $result[0]['postings'][0]['amount']);
    }

    public function testParseLargeAmount(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:Checking  1000000 USD
  Expenses:Test  -1000000 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('1000000', $result[0]['postings'][0]['amount']);
    }

    public function testParseCurrencyCode(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:Checking  100 USD
  Expenses:Test  -100 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('USD', $result[0]['postings'][0]['currency']);
    }

    public function testParseMultiCurrencyAmounts(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:Checking  50 EUR
  Expenses:Test  -50 EUR
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('50', $result[0]['postings'][0]['amount']);
        self::assertSame('EUR', $result[0]['postings'][0]['currency']);
    }
}