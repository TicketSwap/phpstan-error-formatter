<p align="center">
    A minimalistic error formatter for PHPStan
</p>

<p align="center">
    <img src="https://raw.githubusercontent.com/TicketSwap/phpstan-error-formatter/main/screenshot.png" alt="Screenshot" height="300">
</p>

------

## Installation

To use this extension, require it in [Composer](https://getcomposer.org/):

```bash
composer require --dev ticketswap/phpstan-error-formatter
```

If you also install [phpstan/extension-installer](https://github.com/phpstan/extension-installer) then you're all set!

<details>
  <summary>Manual installation</summary>

If you don't want to use `phpstan/extension-installer`, include extension.neon in your project's PHPStan config:

```neon
includes:
    - vendor/ticketswap/phpstan-error-formatter/extension.neon
```
</details>

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
