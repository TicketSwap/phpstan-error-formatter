<p align="center">
    A minimalistic error formatter for PHPStan
</p>

<p align="center">
    <img src="https://raw.githubusercontent.com/TicketSwap/phpstan-error-formatter/main/screenshot.png" alt="Screenshot" height="300">
</p>

------

## Features

* Every error has it's own clickable file + line link (default formatter shows the file once, and then displays the line + errors)
* Errors don't wrap, so they take your while terminal (default formatter wraps in a table)
* Highlighting of variables, fully qualified class names and other common types. This is done naively and there are cases where it does not work.
* Long file paths are truncated visually (src/App/../Entity/User.php) while keeping the clickable link intact
* The filename + line is clickable depending on your terminal and their support for clickable links. For example, in PHPStorm's built-in editor, it doesn't work and there we print `file:///Volumes/CS/www/src/App/User.php`.

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
