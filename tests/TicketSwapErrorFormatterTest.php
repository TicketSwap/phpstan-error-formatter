<?php

declare(strict_types=1);

namespace TicketSwap\PHPStanErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use PHPStan\File\NullRelativePathHelper;
use PHPUnit\Framework\TestCase;

/**
 * @covers TicketSwapErrorFormatter
 */
final class TicketSwapErrorFormatterTest extends TestCase
{
    private const PHPSTOR_EDITOR_URL = 'phpstorm://open?file=%file%&line=%line%';

    private TicketSwapErrorFormatter $formatter;

    protected function setUp() : void
    {
        parent::setUp();

        $this->formatter = new TicketSwapErrorFormatter(
            new NullRelativePathHelper(),
            new class() implements ErrorFormatter{
                public function formatErrors(AnalysisResult $analysisResult, Output $output): int
                {
                    return 0;
                }
            },
            self::PHPSTOR_EDITOR_URL,
            []
        );
    }

    /**
     * @return iterable<array{TicketSwapErrorFormatter::LINK_FORMAT_*, array<string, string>}>
     */
    public static function provideLinkFormatFromEnv() : iterable
    {
        yield 'GitHub Actions' => [
            TicketSwapErrorFormatter::LINK_FORMAT_GITHUB_ACTIONS,
            ['GITHUB_ACTIONS' => 'true'],
        ];
        yield 'JetBrains' => [
            TicketSwapErrorFormatter::LINK_FORMAT_PHPSTORM,
            ['TERMINAL_EMULATOR' => 'JetBrains-JediTerm'],
        ];
        yield 'Warp' => [
            TicketSwapErrorFormatter::LINK_FORMAT_WARP,
            ['TERM_PROGRAM' => 'WarpTerminal'],
        ];
        yield 'Ghostty' => [
            TicketSwapErrorFormatter::LINK_FORMAT_DEFAULT,
            ['TERM_PROGRAM' => 'ghostty'],
        ];
        yield 'Default' => [
            TicketSwapErrorFormatter::LINK_FORMAT_DEFAULT,
            [],
        ];
    }

    /**
     * @dataProvider provideLinkFormatFromEnv
     */
    public function testGetLinkFormatFromEnv(string $expected, array $environmentVariables) : void
    {
        self::assertSame(
            $expected,
            TicketSwapErrorFormatter::getLinkFormatFromEnv($environmentVariables)
        );
    }

    /**
     * @return iterable<array{string, TicketSwapErrorFormatter::LINK_FORMAT_*, int, string, string, null|string, bool}>
     */
    public static function provideLinkFormats() : iterable
    {
        yield [
            "↳ <href=phpstorm://open?file=/www/project/src/Core/Admin/Controller/Dashboard/User/AddUserController.php&line=20>src/Core/Admin/.../User/AddUserController.php:20</>\n",
            TicketSwapErrorFormatter::LINK_FORMAT_DEFAULT,
            20,
            '/www/project/src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            'src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            self::PHPSTOR_EDITOR_URL,
            true,
        ];
        yield [
            "↳ src/Core/Admin/Controller/Dashboard/User/AddUserController.php:20\n",
            TicketSwapErrorFormatter::LINK_FORMAT_GITHUB_ACTIONS,
            20,
            '/www/project/src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            'src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            self::PHPSTOR_EDITOR_URL,
            true,
        ];
        yield [
            "↳ src/Core/Admin/Controller/Dashboard/User/AddUserController.php:20\n",
            TicketSwapErrorFormatter::LINK_FORMAT_WARP,
            20,
            '/www/project/src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            'src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            self::PHPSTOR_EDITOR_URL,
            true,
        ];
        yield [
            "↳ file:///www/project/src/Core/Admin/Controller/Dashboard/User/AddUserController.php:20\n",
            TicketSwapErrorFormatter::LINK_FORMAT_PHPSTORM,
            20,
            '/www/project/src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            'src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            self::PHPSTOR_EDITOR_URL,
            true,
        ];
        yield [
            "↳ src/Core/Admin/Controller/Dashboard/User/AddUserController.php:20\n",
            TicketSwapErrorFormatter::LINK_FORMAT_WITHOUT_EDITOR,
            20,
            '/www/project/src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            'src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            self::PHPSTOR_EDITOR_URL,
            true,
        ];
        yield [
            "↳ src/Core/Admin/Controller/Dashboard/User/AddUserController.php:20\n",
            TicketSwapErrorFormatter::LINK_FORMAT_DEFAULT,
            20,
            '/www/project/src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            'src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            self::PHPSTOR_EDITOR_URL,
            false,
        ];
        yield [
            "↳ src/Core/Admin/Controller/Dashboard/User/AddUserController.php:20\n",
            TicketSwapErrorFormatter::LINK_FORMAT_DEFAULT,
            20,
            '/www/project/src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            'src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            null,
            true,
        ];
    }

    /**
     * @dataProvider provideLinkFormats
     * @param TicketSwapErrorFormatter::LINK_FORMAT_* $format
     */
    public function testLink(
        string $expected,
        string $format,
        int $line,
        string $absolutePath,
        string $relativePath,
        ?string $editorUrl,
        bool $isDecorated
    ) : void {
        self::assertSame(
            $expected,
            TicketSwapErrorFormatter::link(
                $format,
                $line,
                $absolutePath,
                $relativePath,
                $editorUrl,
                $isDecorated
            )
        );
    }

    /**
     * @return iterable<array{string, string, null|string, null|string, bool}>
     */
    public static function provideHighlight() : iterable
    {
        yield [
            "Parameter #1 <fg=green>\$currentWorkingDirectory</> of method <fg=yellow>Application\AnalyzeCommand</>: <fg=blue>getFinder()</> expects <fg=magenta>string</>, <fg=magenta>string</>|<fg=magenta>false</> given.",
            'Parameter #1 $currentWorkingDirectory of method Application\AnalyzeCommand: getFinder() expects string, string|false given.',
            null,
            null,
            true
        ];
        yield [
            'Parameter #1 $currentWorkingDirectory of method Application\AnalyzeCommand: getFinder() expects string, string|false given.',
            'Parameter #1 $currentWorkingDirectory of method Application\AnalyzeCommand: getFinder() expects string, string|false given.',
            null,
            null,
            false
        ];
        yield [
            "Parameter #1 <fg=green>\$currentWorkingDirectory</> of method <fg=yellow>Application\AnalyzeCommand</>: <fg=blue>getFinder()</> expects <fg=magenta>string</>, <fg=magenta>string</>|<fg=magenta>false</> given.\n💡 <fg=blue>Tip: you can do blabla.</>\n🔖 <fg=blue>argument.type</>",
            'Parameter #1 $currentWorkingDirectory of method Application\AnalyzeCommand: getFinder() expects string, string|false given.',
            'Tip: you can do blabla.',
            'argument.type',
            true
        ];
    }

    /**
     * @dataProvider provideHighlight
     */
    public function testHighlight(string $expected, string $message, ?string $tip, ?string $identifier, bool $isDecorated) : void
    {
        self::assertSame(
            $expected,
            TicketSwapErrorFormatter::highlight(
                $message,
                $tip,
                $identifier,
                $isDecorated
            )
        );
    }
}
