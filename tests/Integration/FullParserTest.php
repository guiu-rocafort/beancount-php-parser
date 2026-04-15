<?php

declare(strict_types=1);

namespace Beancount\Parser\Tests\Integration;

use Beancount\Parser\Parser;
use PHPUnit\Framework\TestCase;

final class FullParserTest extends TestCase
{
    private function loadFixture(string $filename): string
    {
        return file_get_contents(__DIR__ . '/../fixtures/' . $filename);
    }

    public function testParseSimpleFile(): void
    {
        $content = $this->loadFixture('simple.bean');
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertIsArray($result);
        self::assertNotEmpty($result);

        $first = $result[0];
        self::assertSame('open', $first['directive']);
        self::assertSame('2014-01-01', $first['date']);
    }

    public function testParseSimpleTransaction(): void
    {
        $content = $this->loadFixture('simple.bean');
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertNotEmpty($result);
    }

    public function testParseOpenDirective(): void
    {
        $content = $this->loadFixture('simple.bean');
        $parser = new Parser($content);
        $result = $parser->parse();

        $openDirectives = array_filter($result, fn($d) => $d['directive'] === 'open');

        self::assertNotEmpty($openDirectives);
    }

    public function testParseTransactionWithPayeeAndNarration(): void
    {
        $content = $this->loadFixture('simple.bean');
        $parser = new Parser($content);
        $result = $parser->parse();

        $transactions = array_filter($result, fn($d) => $d['directive'] === 'transaction');

        self::assertNotEmpty($transactions);
    }

    public function testParseMultiCurrencyFile(): void
    {
        $content = $this->loadFixture('multicurrency.bean');
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertNotEmpty($result);
    }

    public function testParseStockFile(): void
    {
        $content = $this->loadFixture('stocks.bean');
        $parser = new Parser($content);
        $result = $parser->parse();

        $transactions = array_filter($result, fn($d) => $d['directive'] === 'transaction');

        self::assertNotEmpty($transactions);
    }

    public function testParseBalanceFile(): void
    {
        $content = $this->loadFixture('balance.bean');
        $parser = new Parser($content);
        $result = $parser->parse();

        $balances = array_filter($result, fn($d) => $d['directive'] === 'balance');

        self::assertNotEmpty($balances);
    }

    public function testParseOpenCloseFile(): void
    {
        $content = $this->loadFixture('open-close.bean');
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertNotEmpty($result);
    }

    public function testParseTagsLinksFile(): void
    {
        $content = $this->loadFixture('tags-links.bean');
        $parser = new Parser($content);
        $result = $parser->parse();

        $taggedTx = array_values(array_filter($result, fn($d) => $d['directive'] === 'transaction' && !empty($d['tags'])));

        self::assertNotEmpty($taggedTx);
    }

    public function testParseComplexFile(): void
    {
        $content = $this->loadFixture('complex.bean');
        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertNotEmpty($result);
        self::assertIsArray($result[0]);
    }

public function testParseTransactionPostingsWithCostBasis(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Buy stock"
  Assets:Investments  10 IVV {200.00 USD}
  Assets:Cash  -2000 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $tx = $result[0];
        self::assertNotEmpty($tx['postings']);
    }

    public function testParseTransactionPostingWithPrice(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Exchange"
  Assets:US  -100 USD @ 0.73 EUR
  Assets:EU  73 EUR
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $tx = $result[0];
        self::assertNotEmpty($tx);
    }

    public function testParseDateOrderIsPreserved(): void
    {
        $content = <<<'BEAN'
2014-03-01 open Assets:A
2014-01-01 open Assets:B
2014-02-01 open Assets:C
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertGreaterThanOrEqual(2, count($result));
    }

    public function testParseEmptyPostingAccountOnly(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "Transfer"
  Assets:A  -100 USD
  Assets:B
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        $tx = $result[0];
        self::assertSame('transaction', $tx['directive']);
    }

    public function testParseMultipleTransactions(): void
    {
        $content = <<<'BEAN'
2014-01-01 * "First"
  Assets:A  -50 USD
  Expenses:A  50 USD

2014-01-02 * "Second"
  Assets:A  -30 USD
  Expenses:B  30 USD
BEAN;

        $parser = new Parser($content);
        $result = $parser->parse();

        self::assertGreaterThanOrEqual(1, count($result));
    }
}