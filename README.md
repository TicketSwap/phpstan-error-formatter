<p align="center">
    A minimalistic error formatter for PHPStan
</p>

<p align="center">
    <img src="https://raw.githubusercontent.com/TicketSwap/phpstan-error-formatter/main/screenshot.png" alt="Screenshot" height="300">
</p>

------

## Installation

```command
$ composer require --dev ticketswap/phpstan-error-formatter
```

## Usage

Configure PHPStan to use the `ticketswap` error formatter:
```neon
parameters:
    errorFormat: ticketswap
```

When you haven't done so, make sure to configure the [editorUrl](https://phpstan.org/user-guide/output-format#opening-file-in-an-editor):
```neon
parameters:
    editorUrl: 'phpstorm://open?file=%%file%%&line=%%line%%'
