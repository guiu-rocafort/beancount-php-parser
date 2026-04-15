# Beancount PHP Parser

[![Tests](https://github.com/beancount/php-parser/workflows/Tests/badge.svg)](https://github.com/beancount/php-parser/actions)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A lightweight, high-performance PHP library for parsing [Beancount](https://beancount.github.io/) double-entry bookkeeping files.

This parser provides a pure PHP implementation of the Beancount specification, allowing you to easily integrate financial data into your PHP applications without external dependencies on the Python-based Beancount tools.

## Requirements

- PHP 8.1+
- [Composer](https://getcomposer.org/)

## Installation

```bash
composer require beancount/php-parser
```

## Quick Start

```php
use Beancount\Parser\Parser;
use Beancount\Parser\Exception\ParseException;

$content = file_get_contents('ledger.beancount');

try {
    $parser = new Parser($content);
    $entries = $parser->parse();

    foreach ($entries as $entry) {
        printf("%s: %s\n", 
            $entry['directive'], 
            $entry['account'] ?? $entry['narration'] ?? '...'
        );
    }
} catch (ParseException $e) {
    echo "Parsing failed: " . $e->getMessage();
}
```

## Supported Directives

| Directive | Description |
|----------|-------------|
| `open` | Open an account |
| `close` | Close an account |
| `transaction` | Transaction with postings |
| `balance` | Balance assertion |
| `pad` | Pad account balance |
| `note` | Note attached to account |
| `document` | Document attachment |
| `commodity` | Commodity declaration |
| `price` | Price directive |
| `event` | Event directive |
| `query` | Query directive |
| `custom` | Custom directive |

## Core Features

- **Transactions**: Full support for postings, flags (`*` or `!`), tags (`#`), and links (`^`).
- **Financial Details**: Handles cost basis `{...}`, unit prices `@`, and total prices `@@`.
- **Metadata**: Key-value pairs supported on both transactions and individual postings.
- **Accurate Coordinates**: Provides exact line and column numbers for syntax errors.

## Advanced Usage

### Accessing Metadata

The parser extracts metadata as an associative array for both transactions and postings.

```php
foreach ($entries as $entry) {
    if ($entry['directive'] === 'transaction') {
        // Transaction-level metadata
        $invoiceId = $entry['metadata']['invoice'] ?? 'N/A';
        
        foreach ($entry['postings'] as $posting) {
            // Posting-level metadata
            $receiptUrl = $posting['metadata']['receipt'] ?? null;
        }
    }
}
```

### Lexer Access

If you only need to tokenize the input without building the full directive tree:

```php
use Beancount\Parser\Tokenizer;
use Beancount\Parser\Token\TokenInterface;

$tokenizer = new Tokenizer($content);
$tokens = $tokenizer->tokenize();

foreach ($tokens as $token) {
    echo "Type: {$token->getType()}, Value: {$token->getValue()}\n";
}
```

## Docker Development

The project includes a Docker environment for consistent testing and development without needing PHP installed on your host machine.

```bash
# Build the environment
docker compose build

# Run the test suite
docker compose run --rm app ./vendor/bin/phpunit
```

## Testing

```bash
# Run all tests (host machine)
composer test

# Run tests with coverage (requires Xdebug)
XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-text
```

## Project Structure

```
src/
├── Parser.php           # Main recursive descent parser
├── Tokenizer.php       # Lexer/tokenizer state machine
├── Directive/         # DTOs for Beancount directives
├── Token/             # Token definitions and DTOs
└── Exception/         # Custom parse exceptions
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Ensure all tests pass: `docker compose run --rm app ./vendor/bin/phpunit`
4. Submit a pull request

## License

MIT License - see [LICENSE](LICENSE) file for details.