<?php

declare(strict_types=1);

namespace Beancount\Parser\Tests\Unit;

use Beancount\Parser\Parser;
use Beancount\Parser\Directive\Close;
use Beancount\Parser\Directive\Open;
use Beancount\Parser\Directive\Balance;
use Beancount\Parser\Directive\Pad;
use Beancount\Parser\Directive\Note;
use Beancount\Parser\Directive\Document;
use Beancount\Parser\Directive\Price;
use Beancount\Parser\Directive\Event;
use Beancount\Parser\Directive\Query;
use Beancount\Parser\Directive\Custom;
use Beancount\Parser\Directive\Commodity;
use Beancount\Parser\Directive\Posting;
use Beancount\Parser\Directive\Transaction;
use PHPUnit\Framework\TestCase;

final class DirectiveClassesTest extends TestCase
{
    public function testCloseClass(): void
    {
        $close = new Close('2014-01-01', 'Assets:Testing');
        self::assertSame('2014-01-01', $close->getDate());
        self::assertSame('Assets:Testing', $close->getAccount());
        self::assertSame('close', $close->getDirectiveType());
        
        $arr = $close->toArray();
        self::assertSame('close', $arr['directive']);
        self::assertSame('2014-01-01', $arr['date']);
        self::assertSame('Assets:Testing', $arr['account']);
    }

    public function testOpenClass(): void
    {
        $open = new Open('2014-01-01', 'Assets:Testing', ['USD'], 'STRICT');
        self::assertSame('2014-01-01', $open->getDate());
        self::assertSame('Assets:Testing', $open->getAccount());
        self::assertSame(['USD'], $open->getCurrencies());
        self::assertSame('STRICT', $open->getBookingMethod());
        self::assertSame('open', $open->getDirectiveType());
    }

    public function testBalanceClass(): void
    {
        $balance = new Balance('2014-01-01', 'Assets:Testing', '1000.00', 'USD');
        self::assertSame('2014-01-01', $balance->getDate());
        self::assertSame('Assets:Testing', $balance->getAccount());
        self::assertSame('1000.00', $balance->getAmount());
        self::assertSame('USD', $balance->getCurrency());
        self::assertSame('balance', $balance->getDirectiveType());
    }

    public function testPadClass(): void
    {
        $pad = new Pad('2014-01-01', 'Assets:Savings', 'Assets:Checking', '-100', 'USD');
        self::assertSame('2014-01-01', $pad->getDate());
        self::assertSame('Assets:Savings', $pad->getAccount());
        self::assertSame('Assets:Checking', $pad->getSourceAccount());
        self::assertSame('-100', $pad->getAmount());
        self::assertSame('USD', $pad->getCurrency());
        self::assertSame('pad', $pad->getDirectiveType());
    }

    public function testNoteClass(): void
    {
        $note = new Note('2014-01-01', 'Assets:Testing', 'Important note');
        self::assertSame('2014-01-01', $note->getDate());
        self::assertSame('Assets:Testing', $note->getAccount());
        self::assertSame('Important note', $note->getMessage());
        self::assertSame('note', $note->getDirectiveType());
    }

    public function testDocumentClass(): void
    {
        $doc = new Document('2014-01-01', 'Assets:Testing', '/path/to/file.pdf');
        self::assertSame('2014-01-01', $doc->getDate());
        self::assertSame('Assets:Testing', $doc->getAccount());
        self::assertSame('/path/to/file.pdf', $doc->getPath());
        self::assertSame('document', $doc->getDirectiveType());
    }

    public function testPriceClass(): void
    {
        $price = new Price('2014-01-01', 'USD', '1.09', 'CAD');
        self::assertSame('2014-01-01', $price->getDate());
        self::assertSame('USD', $price->getCurrency());
        self::assertSame('1.09', $price->getAmount());
        self::assertSame('CAD', $price->getPriceCurrency());
        self::assertSame('price', $price->getDirectiveType());
    }

    public function testEventClass(): void
    {
        $event = new Event('2014-01-01', 'location', 'Paris');
        self::assertSame('2014-01-01', $event->getDate());
        self::assertSame('location', $event->getType());
        self::assertSame('Paris', $event->getDescription());
        self::assertSame('event', $event->getDirectiveType());
    }

    public function testQueryClass(): void
    {
        $query = new Query('2014-01-01', 'balances', 'balances of Assets');
        self::assertSame('2014-01-01', $query->getDate());
        self::assertSame('balances', $query->getName());
        self::assertSame('balances of Assets', $query->getQueryString());
        self::assertSame('query', $query->getDirectiveType());
    }

    public function testCustomClass(): void
    {
        $custom = new Custom('2014-01-01', 'mytype', ['value1', 'value2']);
        self::assertSame('2014-01-01', $custom->getDate());
        self::assertSame('mytype', $custom->getName());
        self::assertSame(['value1', 'value2'], $custom->getValues());
        self::assertSame('custom', $custom->getDirectiveType());
    }

    public function testCommodityClass(): void
    {
        $commodity = new Commodity('2014-01-01', 'AAPL', ['name' => 'Apple Inc']);
        self::assertSame('2014-01-01', $commodity->getDate());
        self::assertSame('AAPL', $commodity->getCurrency());
        self::assertSame(['name' => 'Apple Inc'], $commodity->getMetadata());
        self::assertSame('commodity', $commodity->getDirectiveType());
    }

    public function testPostingClassEmpty(): void
    {
        $posting = new Posting('Assets:A', '', '');
        self::assertSame('Assets:A', $posting->getAccount());
        self::assertSame('', $posting->getAmount());
        self::assertSame('', $posting->getCurrency());
        self::assertNull($posting->getCost());
        self::assertNull($posting->getCostCurrency());
        self::assertNull($posting->getPrice());
        self::assertNull($posting->getPriceCurrency());
        self::assertNull($posting->getPriceType());
        self::assertSame([], $posting->getMetadata());
    }

    public function testPostingClassFull(): void
    {
        $posting = new Posting('Assets:A', '100', 'USD', '50.00', 'USD', '60.00', 'EUR', 'per-unit', ['key' => 'value']);
        self::assertSame('Assets:A', $posting->getAccount());
        self::assertSame('100', $posting->getAmount());
        self::assertSame('USD', $posting->getCurrency());
        self::assertSame('50.00', $posting->getCost());
        self::assertSame('USD', $posting->getCostCurrency());
        self::assertSame('60.00', $posting->getPrice());
        self::assertSame('EUR', $posting->getPriceCurrency());
        self::assertSame('per-unit', $posting->getPriceType());
        self::assertSame(['key' => 'value'], $posting->getMetadata());
    }

    public function testTransactionClass(): void
    {
        $txn = new Transaction('2014-01-01', '*', 'Payee', 'Narration', ['tag1'], ['link1'], []);
        self::assertSame('2014-01-01', $txn->getDate());
        self::assertSame('*', $txn->getFlag());
        self::assertSame('Payee', $txn->getPayee());
        self::assertSame('Narration', $txn->getNarration());
        self::assertSame(['tag1'], $txn->getTags());
        self::assertSame(['link1'], $txn->getLinks());
        self::assertSame('transaction', $txn->getDirectiveType());
    }

    public function testParseCloseDirective(): void
    {
        $content = '2014-01-01 close Assets:Testing';
        $parser = new Parser($content);
        $result = $parser->parse();
        
        self::assertNotEmpty($result);
        self::assertSame('close', $result[0]['directive']);
        self::assertSame('2014-01-01', $result[0]['date']);
        self::assertSame('Assets:Testing', $result[0]['account']);
    }

    public function testParsePadDirective(): void
    {
        $content = '2014-01-01 pad Assets:Savings Assets:Checking -100 USD';
        $parser = new Parser($content);
        $result = $parser->parse();
        
        self::assertNotEmpty($result);
        self::assertSame('pad', $result[0]['directive']);
    }

    public function testParseDocumentDirective(): void
    {
        $content = '2014-01-01 document Assets:Testing "/path/to/file.pdf"';
        $parser = new Parser($content);
        $result = $parser->parse();
        
        self::assertNotEmpty($result);
        self::assertSame('document', $result[0]['directive']);
    }

    public function testParsePriceDirective(): void
    {
        $content = '2014-01-01 price USD 1.09 CAD';
        $parser = new Parser($content);
        $result = $parser->parse();
        
        self::assertNotEmpty($result);
        self::assertSame('price', $result[0]['directive']);
        self::assertSame('USD', $result[0]['currency']);
        self::assertSame('1.09', $result[0]['amount']);
        self::assertSame('CAD', $result[0]['price_currency']);
    }

    public function testParseEventDirective(): void
    {
        $content = '2014-01-01 event "location" "Paris"';
        $parser = new Parser($content);
        $result = $parser->parse();
        
        self::assertNotEmpty($result);
        self::assertSame('event', $result[0]['directive']);
        self::assertSame('location', $result[0]['type']);
        self::assertSame('Paris', $result[0]['description']);
    }

    public function testParseQueryDirective(): void
    {
        $content = '2014-01-01 query "balances" "balances of Assets"';
        $parser = new Parser($content);
        $result = $parser->parse();
        
        self::assertNotEmpty($result);
        self::assertSame('query', $result[0]['directive']);
        self::assertSame('balances', $result[0]['name']);
    }

    public function testParseCustomDirective(): void
    {
        $content = '2014-01-01 custom "mytype" value1 value2';
        $parser = new Parser($content);
        $result = $parser->parse();
        
        self::assertNotEmpty($result);
        self::assertSame('custom', $result[0]['directive']);
    }

    public function testParseBalanceWithMultipleCurrencies(): void
    {
        $content = '2014-01-01 balance Assets:Testing 1000 USD, 50 EUR';
        $parser = new Parser($content);
        $result = $parser->parse();
        
        self::assertNotEmpty($result);
    }

    public function testParseNoteDirective(): void
    {
        $content = '2014-01-01 note Assets:Testing "Important message"';
        $parser = new Parser($content);
        $result = $parser->parse();
        
        self::assertNotEmpty($result);
        self::assertSame('note', $result[0]['directive']);
        self::assertSame('Important message', $result[0]['message']);
    }

    public function testParseOpenWithMultipleCurrencies(): void
    {
        $content = '2014-01-01 open Assets:Testing USD, EUR, CAD';
        $parser = new Parser($content);
        $result = $parser->parse();
        
        self::assertNotEmpty($result);
        self::assertContains('USD', $result[0]['currencies']);
        self::assertContains('EUR', $result[0]['currencies']);
        self::assertContains('CAD', $result[0]['currencies']);
    }

    public function testParseOpenWithBookingMethod(): void
    {
        $content = '2014-01-01 open Assets:Testing USD';
        $parser = new Parser($content);
        $result = $parser->parse();
        
        self::assertNotEmpty($result);
    }
}