<p align="center">
    A minimalistic error formatter for PHPStan
</p>

------

## Installation

```command
$ composer require ticketswap/phpstan-error-formatter
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
