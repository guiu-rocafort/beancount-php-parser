<?php

declare(strict_types=1);

namespace Beancount\Parser\Tests\Unit;

use Beancount\Parser\Parser;
use PHPUnit\Framework\TestCase;

final class EdgeCaseTest extends TestCase
{
    public function testParseDateWithSlashes(): void
    {
        $content = '2014/01/01 open Assets:Testing USD';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertNotEmpty($result);
    }

    public function testParseLargeIntegerAmount(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:A  9999999999 USD
  Expenses:E  -9999999999 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('9999999999', $result[0]['postings'][0]['amount']);
    }

    public function testParseLargeDecimalAmount(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:A  123456789.123456 USD
  Expenses:E  -123456789.123456 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('123456789.123456', $result[0]['postings'][0]['amount']);
    }

    public function testParseDeeplyNestedAccount(): void
    {
        $content = '2014-01-01 open Assets:US:BofA:Checking:SubAccount USD';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('Assets:US:BofA:Checking:SubAccount', $result[0]['account']);
    }

    public function testParseNestedAccountWithNumbers(): void
    {
        $content = '2014-01-01 open Assets:Test123:Account456:789 USD';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertStringStartsWith('Assets', $result[0]['account']);
    }

    public function testParseAccountWithDashes(): void
    {
        $content = '2014-01-01 open Assets:Test-Account USD';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertNotEmpty($result[0]['account']);
    }

    public function testParseAccountWithUnderscores(): void
    {
        $content = '2014-01-01 open Assets:Test_Account USD';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertNotEmpty($result[0]['account']);
    }

    public function testParseMultipleCurrenciesInOpen(): void
    {
        $content = '2014-01-01 open Assets:Multi USD, EUR, CAD';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertNotEmpty($result[0]['currencies']);
    }

    public function testParseCurrencyWithNumbers(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:A  10 MSFT2 {50.00 USD}
  Assets:C  -500 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('MSFT2', $result[0]['postings'][0]['currency']);
    }

    public function testParseNarrationWithSpecialCharacters(): void
    {
        $content = '2014-01-01 * "Café \"Bonheur\""';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertNotEmpty($result[0]['payee']);
    }

    public function testParseZeroAmountNoCurrency(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:A  0
  Assets:B  0
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('0', $result[0]['postings'][0]['amount']);
    }

    public function testParseCommentOnlyLines(): void
    {
        $content = <<<'BEAN'
; This is a comment
; Another comment line

2014-01-01 open Assets:A USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertNotEmpty($result);
    }

    public function testParseEmptySecondPostingAmount(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:A  -100 USD
  Assets:B
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertNotEmpty($result);
    }
}