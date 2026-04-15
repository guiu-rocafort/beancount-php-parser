<?php

declare(strict_types=1);

namespace Beancount\Parser\Tests\Unit;

use Beancount\Parser\Parser;
use Beancount\Parser\Exception\ParseException;
use PHPUnit\Framework\TestCase;

final class ErrorHandlingTest extends TestCase
{
    /**
     * @dataProvider invalidSyntaxProvider
     */
    public function testParserThrowsExceptionOnInvalidSyntax(string $input, string $expectedMessagePart): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessageMatches("/$expectedMessagePart/i");

        $parser = new Parser($input);
        $parser->parse();
    }

    public static function invalidSyntaxProvider(): array
    {
        return [
            'malformed balance' => ['2024-01-01 balance Assets:Checking', 'Expected token type NUMBER'],
            'incomplete balance' => ['2024-01-01 balance Assets:Checking 100', 'Expected token type CURRENCY'],
        ];
    }

    public function testParseExceptionProvidesCoordinates(): void
    {
        $input = "2024-01-01 balance Assets:Checking"; // Missing amount
        
        try {
            $parser = new Parser($input);
            $parser->parse();
            $this->fail('Expected ParseException was not thrown');
        } catch (ParseException $e) {
            self::assertGreaterThan(0, $e->getErrorLine());
            self::assertGreaterThan(0, $e->getErrorColumn());
        }
    }
}
