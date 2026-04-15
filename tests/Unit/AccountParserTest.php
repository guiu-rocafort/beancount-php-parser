<?php

declare(strict_types=1);

namespace Beancount\Parser\Tests\Unit;

use Beancount\Parser\Parser;
use PHPUnit\Framework\TestCase;

final class AccountParserTest extends TestCase
{
    public function testParseSimpleAccount(): void
    {
        $content = '2014-01-01 open Assets:Checking USD';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertNotEmpty($result);
        self::assertSame('Assets:Checking', $result[0]['account']);
    }

    public function testParseNestedAccount(): void
    {
        $content = '2014-01-01 open Assets:US:BofA:Checking USD';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('Assets:US:BofA:Checking', $result[0]['account']);
    }

    public function testParseAccountWithNumbers(): void
    {
        $content = '2014-01-01 open Assets:Test123:Account456 USD';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertStringStartsWith('Assets', $result[0]['account']);
    }

    public function testParseAccountInTransactionPosting(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:US:Checking  100 USD
  Expenses:Food  -100 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertNotEmpty($result);
        self::assertSame('transaction', $result[0]['directive']);
        self::assertSame('Assets:US:Checking', $result[0]['postings'][0]['account']);
    }

    public function testParseMultipleAccountTypes(): void
    {
        $content = <<<'BEAN'
2014-01-01 open Assets:Checking USD
2014-01-01 open Liabilities:CreditCard USD
2014-01-01 open Income:Salary USD
2014-01-01 open Expenses:Food USD
2014-01-01 open Equity:OpeningBalances USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertCount(5, $result);
    }
}