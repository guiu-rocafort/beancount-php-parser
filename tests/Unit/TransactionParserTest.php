<?php

declare(strict_types=1);

namespace Beancount\Parser\Tests\Unit;

use Beancount\Parser\Parser;
use PHPUnit\Framework\TestCase;

final class TransactionParserTest extends TestCase
{
    public function testParseMinimalTransaction(): void
    {
        $content = '2014-01-01 * "Test narration"';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertNotEmpty($result);
        self::assertSame('transaction', $result[0]['directive']);
    }

    public function testParseTransactionWithPayeeAndNarration(): void
    {
        $content = '2014-01-01 * "Payee" "Narration"';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('Payee', $result[0]['payee']);
        self::assertSame('Narration', $result[0]['narration']);
    }

    public function testParseTransactionWithPayeeOnly(): void
    {
        $content = '2014-01-01 * "Payee" ""';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('Payee', $result[0]['payee']);
    }

    public function testParseTransactionWithNarrationOnly(): void
    {
        $content = '2014-01-01 * "Just narration"';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('Just narration', $result[0]['payee']);
    }

    public function testParseTransactionWithStarFlag(): void
    {
        $content = '2014-01-01 * "Test"';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('*', $result[0]['flag']);
    }

    public function testParseTransactionWithExclamationFlag(): void
    {
        $content = '2014-01-01 ! "Test"';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('!', $result[0]['flag']);
    }

    public function testParseTransactionWithTags(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test" #tag1 #tag2
  Assets:A  100 USD
  Expenses:E  -100 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertContains('tag1', $result[0]['tags']);
        self::assertContains('tag2', $result[0]['tags']);
    }

    public function testParseTransactionWithSingleTag(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test" #my-tag
  Assets:A  100 USD
  Expenses:E  -100 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame(['my-tag'], $result[0]['tags']);
    }

    public function testParseTransactionWithLinks(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test" ^link1 ^link2
  Assets:A  100 USD
  Expenses:E  -100 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertContains('link1', $result[0]['links']);
        self::assertContains('link2', $result[0]['links']);
    }

    public function testParseTransactionWithSingleLink(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test" ^invoice-001
  Assets:A  100 USD
  Expenses:E  -100 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame(['invoice-001'], $result[0]['links']);
    }

    public function testParseTransactionWithTagAndLink(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test" #tag1 ^link1
  Assets:A  100 USD
  Expenses:E  -100 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertContains('tag1', $result[0]['tags']);
        self::assertContains('link1', $result[0]['links']);
    }

    public function testParseTransactionWithMultiplePostings(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:A  100 USD
  Assets:B  30 USD
  Expenses:C  -130 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertCount(3, $result[0]['postings']);
    }

    public function testParseMultipleTransactions(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "First"
  Assets:A  10 USD
  Expenses:B  -10 USD

2014-01-02 * "Second"
  Assets:C  20 USD
  Expenses:D  -20 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertGreaterThanOrEqual(1, count($result));
    }

    public function testParseTransactionWithTxnKeyword(): void
    {
        $content = '2014-01-01 txn "Test"';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertNotEmpty($result);
        self::assertSame('transaction', $result[0]['directive']);
    }

    public function testParseTransactionWithFlagOnPosting(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Test"
  Assets:A  100 USD
  ! Expenses:E  -100 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertNotEmpty($result);
    }

    public function testParseTransactionEmptyPayeeField(): void
    {
        $content = '2014-01-01 * "" "Narration"';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('', $result[0]['payee']);
        self::assertSame('Narration', $result[0]['narration']);
    }

    public function testParseTransactionDateWithSlashes(): void
    {
        $content = '2014/01/01 * "Test"';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertNotEmpty($result);
    }

    public function testParseTransactionNoPostings(): void
    {
        $content = '2014-01-01 * "Test"';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('transaction', $result[0]['directive']);
        self::assertEmpty($result[0]['postings']);
    }

    public function testParseTransactionwithHeaderMetadata(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Metadata Test"
  key1: "value1"
  key2: "value2"
  Assets:Checking  100 USD
  Expenses:Food  -100 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertSame('value1', $result[0]['metadata']['key1']);
        self::assertSame('value2', $result[0]['metadata']['key2']);
    }

    public function testParseTransactionWithHeaderTagsAndLinks(): void
    {
        $content = '2014-01-01 * "Tags/Links Test" #tag1 ^link1 #tag2 ^link2';
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertContains('tag1', $result[0]['tags']);
        self::assertContains('tag2', $result[0]['tags']);
        self::assertContains('link1', $result[0]['links']);
        self::assertContains('link2', $result[0]['links']);
    }
}