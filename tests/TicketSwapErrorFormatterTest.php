<?php

declare(strict_types=1);

namespace TicketSwap\PHPStanErrorFormatter;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ProgressBar;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use PHPStan\Command\OutputStyle;
use PHPStan\File\NullRelativePathHelper;
use PHPUnit\Framework\TestCase;

/**
 * @covers TicketSwapErrorFormatter
 */
final class TicketSwapErrorFormatterTest extends TestCase
{
    private const PHPSTORM_EDITOR_URL = 'phpstorm://open?file=%file%&line=%line%';

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
            self::PHPSTORM_EDITOR_URL,
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
            self::PHPSTORM_EDITOR_URL,
            true,
        ];
        yield [
            "↳ src/Core/Admin/Controller/Dashboard/User/AddUserController.php:20\n",
            TicketSwapErrorFormatter::LINK_FORMAT_GITHUB_ACTIONS,
            20,
            '/www/project/src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            'src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            self::PHPSTORM_EDITOR_URL,
            true,
        ];
        yield [
            "↳ src/Core/Admin/Controller/Dashboard/User/AddUserController.php:20\n",
            TicketSwapErrorFormatter::LINK_FORMAT_WARP,
            20,
            '/www/project/src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            'src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            self::PHPSTORM_EDITOR_URL,
            true,
        ];
        yield [
            "↳ file:///www/project/src/Core/Admin/Controller/Dashboard/User/AddUserController.php:20\n",
            TicketSwapErrorFormatter::LINK_FORMAT_PHPSTORM,
            20,
            '/www/project/src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            'src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            self::PHPSTORM_EDITOR_URL,
            true,
        ];
        yield [
            "↳ src/Core/Admin/Controller/Dashboard/User/AddUserController.php:20\n",
            TicketSwapErrorFormatter::LINK_FORMAT_WITHOUT_EDITOR,
            20,
            '/www/project/src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            'src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            self::PHPSTORM_EDITOR_URL,
            true,
        ];
        yield [
            "↳ src/Core/Admin/Controller/Dashboard/User/AddUserController.php:20\n",
            TicketSwapErrorFormatter::LINK_FORMAT_DEFAULT,
            20,
            '/www/project/src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            'src/Core/Admin/Controller/Dashboard/User/AddUserController.php',
            self::PHPSTORM_EDITOR_URL,
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
            "Parameter #1 <fg=green>\$currentWorkingDirectory</>.",
            'Parameter #1 $currentWorkingDirectory.',
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
            "Parameter #1 \$currentWorkingDirectory of method Application\AnalyzeCommand: getFinder() expects string, string|false given.\nTip: you can do blabla.\nIdentifier: argument.type",
            'Parameter #1 $currentWorkingDirectory of method Application\AnalyzeCommand: getFinder() expects string, string|false given.',
            'you can do blabla.',
            'argument.type',
            false
        ];
        yield [
            "Parameter #1 <fg=green>\$currentWorkingDirectory</> of method <fg=yellow>Application\AnalyzeCommand</>: <fg=blue>getFinder()</> expects <fg=magenta>string</>, <fg=magenta>string</>|<fg=magenta>false</> given.\n💡 <fg=blue>Tip: you can do blabla.</>\n🔖 <fg=blue>argument.type</>",
            'Parameter #1 $currentWorkingDirectory of method Application\AnalyzeCommand: getFinder() expects string, string|false given.',
            'Tip: you can do blabla.',
            'argument.type',
            true
        ];
        yield [
            'Parameter #1 <fg=green>$currentWorkingDirectory</> of method <fg=yellow>Application\AnalyzeCommand</>: <fg=blue>getFinder()</> expects <fg=magenta>string</>, <fg=magenta>Stringable</>|<fg=magenta>false</> given.',
            'Parameter #1 $currentWorkingDirectory of method Application\AnalyzeCommand: getFinder() expects string, Stringable|false given.',
            null,
            null,
            true
        ];
        yield [
            "Array has 3 duplicate keys with value '<fg=yellow>App\Activity</>' (<fg=yellow>\App\Activity</>::class).",
            "Array has 3 duplicate keys with value 'App\Activity' (\App\Activity::class).",
            null,
            null,
            true
        ];
        yield [
            'Property <fg=yellow>App\Models\ExampleModel</>::<fg=green>$example_property</> (<fg=magenta>stdClass</>|<fg=magenta>null</>) does not accept <fg=magenta>mixed</>.',
            'Property App\Models\ExampleModel::$example_property (stdClass|null) does not accept mixed.',
            null,
            null,
            true,
        ];
         yield [
            'Parameter #1 <fg=green>$callback</> of method <fg=yellow>Illuminate\Support\Collection</><<fg=magenta>int</>,<fg=magenta>mixed</>>::<fg=blue>map()</> expects',
            'Parameter #1 $callback of method Illuminate\Support\Collection<int,mixed>::map() expects',
            null,
            null,
            true,
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

    /**
     * @param array{writes: array<string>} $writesWrapper
     */
    private function createOutput(array $writesWrapper) : Output
    {
        return new class($writesWrapper) implements Output {
            private array $writesWrapper;

            public function __construct(array $writesWrapper)
            {
                $this->writesWrapper = $writesWrapper;
            }

            public function writeFormatted(string $message) : void
            {
            }

            public function writeLineFormatted(string $message) : void
            {
                $this->writesWrapper['writes'][] = $message;
            }

            public function writeRaw(string $message) : void
            {
                $this->writesWrapper['writes'][] = $message;
            }

            public function getStyle() : OutputStyle
            {
                return new class() implements OutputStyle {
                    public function title(string $message) : void
                    {
                    }

                    public function section(string $message) : void
                    {
                    }

                    public function listing(array $elements) : void
                    {
                    }

                    public function success(string $message) : void
                    {
                    }

                    public function error(string $message) : void
                    {
                    }

                    public function warning(string $message) : void
                    {
                    }

                    public function note(string $message) : void
                    {
                    }

                    public function caution(string $message) : void
                    {
                    }

                    public function table(array $headers, array $rows) : void
                    {
                    }

                    public function createProgressBar(int $max = 0) : ProgressBar
                    {
                        return new class() implements ProgressBar {
                            public function start(int $max = 0) : void
                            {
                            }

                            public function advance(int $step = 1) : void
                            {
                            }

                            public function finish() : void
                            {
                            }
                        };
                    }
                };
            }

            public function isVerbose() : bool
            {
                return false;
            }

            public function isVeryVerbose() : bool
            {
                return false;
            }

            public function isDebug() : bool
            {
                return false;
            }

            public function isDecorated() : bool
            {
                return true;
            }

            public function getWrites() : array
            {
                return $this->writesWrapper['writes'];
            }
        };
    }

    public function testFormatErrorsNoErrorsWritesNoErrorsAndReturnsZero() : void
    {
        $analysisResult = new AnalysisResult(
            [],
            [],
            [],
            [],
            [],
            false,
            null,
            false,
            0,
            false,
            [],
        );

        $writesWrapper = ['writes' => []];
        $output = $this->createOutput($writesWrapper);

        $result = $this->formatter->formatErrors($analysisResult, $output);

        self::assertSame(0, $result);
        self::assertSame(['<fg=green;options=bold>No errors</>', ''], $output->getWrites());
    }

    public function testFormatErrorsWithErrorsPrintsMessagesLinksSummaryAndReturnsOne() : void
    {
        $fileError = new Error(
            'Parameter #1 $var expects string, int given.',
            '/www/project/src/Foo/Bar.php',
            12,
            null,
            '/www/project/src/Foo/Bar.php',
            null,
            'Adjust in %configurationFile%',
            null,
            null,
            'argument.type',
            [],
        );

        $analysisResult = new AnalysisResult(
            [$fileError],
            [],
            [],
            [],
            [],
            false,
            '/www/project/phpstan.neon',
            false,
            0,
            false,
            [],
        );

        $writesWrapper = ['writes' => []];
        $output = $this->createOutput($writesWrapper);

        $result = $this->formatter->formatErrors($analysisResult, $output);

        self::assertSame(1, $result);

        $expectedLink = "↳ <href=phpstorm://open?file=/www/project/src/Foo/Bar.php&line=12>/www/project/.../Foo/Bar.php:12</>\n";
        $expectedSummary = '<bg=red;options=bold>Found 1 error</>';

        $writes = $output->getWrites();
        $linkFound = false;
        foreach ($writes as $w) {
            if (strpos($w, $expectedLink) !== false) {
                $linkFound = true;
                break;
            }
        }
        self::assertTrue($linkFound, 'Expected link not found. Output writes: ' . implode("\n---\n", $writes));
        self::assertContains($expectedSummary, $writes);
        self::assertContains('', $writes);
    }
}
