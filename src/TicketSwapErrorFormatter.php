<?php

declare(strict_types=1);

namespace TicketSwap\PHPstanErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\CiDetectedErrorFormatter;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;

final class TicketSwapErrorFormatter implements ErrorFormatter
{
    private const FORMAT = "{message}\n{links}";
    private const LINK_FORMAT_DEFAULT = "↳ <href={editorUrl}>{shortPath}:{line}</>\n";
    private const LINK_FORMAT_GITHUB_ACTIONS = "↳ {relativePath}:{line}\n";
    private const LINK_FORMAT_WARP = "↳ {relativePath}:{line}\n";
    private const LINK_FORMAT_PHPSTORM = "↳ file://{absolutePath}:{line}\n";
    private const LINK_FORMAT_WITHOUT_EDITOR = "↳ {relativePath}:{line}\n";

    /**
     * @var string
     */
    private $linkFormat;

    /**
     * @var RelativePathHelper
     */
    private $relativePathHelper;

    /**
     * @var CiDetectedErrorFormatter
     */
    private $ciDetectedErrorFormatter;

    /**
     * @var string|null
     */
    private $editorUrl;

    public function __construct(
        RelativePathHelper $relativePathHelper,
        CiDetectedErrorFormatter $ciDetectedErrorFormatter,
        ?string $editorUrl = null
    ) {
        $this->editorUrl = $editorUrl;
        $this->ciDetectedErrorFormatter = $ciDetectedErrorFormatter;
        $this->relativePathHelper = $relativePathHelper;
        $this->linkFormat = self::getLinkFormatFromEnv();
    }

    public static function getLinkFormatFromEnv() : string
    {
        if (getenv('GITHUB_ACTIONS') !== false) {
            return self::LINK_FORMAT_GITHUB_ACTIONS;
        }
        if (getenv('TERMINAL_EMULATOR') !== 'JetBrains-JediTerm') {
            return self::LINK_FORMAT_PHPSTORM;
        }
        if (getenv('TERM_PROGRAM') !== 'WarpTerminal') {
            return self::LINK_FORMAT_WARP;
        }

        return self::LINK_FORMAT_DEFAULT;
    }

    public function formatErrors(AnalysisResult $analysisResult, Output $output) : int
    {
        if (! $analysisResult->hasErrors()) {
            $output->writeLineFormatted('<fg=green;options=bold>No errors</>');
            $output->writeLineFormatted('');

            return 0;
        }

        foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
            $output->writeLineFormatted(
                sprintf(
                    '<unknown location> %s',
                    $notFileSpecificError
                )
            );
        }

        $projectConfigFile = 'phpstan.neon';
        if ($analysisResult->getProjectConfigFile() !== null) {
            $projectConfigFile = $this->relativePathHelper->getRelativePath($analysisResult->getProjectConfigFile());
        }

        foreach ($analysisResult->getFileSpecificErrors() as $error) {
            $output->writeLineFormatted(
                strtr(
                    self::FORMAT,
                    [
                        '{message}' => self::highlight(
                            $error->getMessage(),
                            $error->getTip() !== null ? str_replace(
                                '%configurationFile%',
                                $projectConfigFile,
                                $error->getTip()
                            ) : null,
                            $error->getIdentifier(),
                            $output->isDecorated()
                        ),
                        '{identifier}' => $error->getIdentifier(),
                        '{links}' => implode([
                            $this::link(
                                $this->linkFormat,
                                (int) $error->getLine(),
                                $error->getFilePath(),
                                $this->relativePathHelper->getRelativePath($error->getFilePath()),
                                $this->editorUrl,
                                $output->isDecorated()
                            ),
                            $error->getTraitFilePath() !== null ? $this::link(
                                $this->linkFormat,
                                (int) $error->getLine(),
                                $error->getTraitFilePath(),
                                $this->relativePathHelper->getRelativePath($error->getTraitFilePath()),
                                $this->editorUrl,
                                $output->isDecorated()
                            ) : '',
                        ]),
                    ]
                )
            );
        }

        if ($this->editorUrl === null) {
            $output->writeLineFormatted('<comment>Configure the `editorUrl` to make the filenames clickable.</comment>');
            $output->writeLineFormatted('');
        }

        $output->writeLineFormatted(
            sprintf(
                '<bg=red;options=bold>Found %d error%s</>',
                $analysisResult->getTotalErrorsCount(),
                $analysisResult->getTotalErrorsCount() === 1 ? '' : 's'
            )
        );
        $output->writeLineFormatted('');

        $this->ciDetectedErrorFormatter->formatErrors($analysisResult, $output);

        return 1;
    }

    public static function link(
        string $format,
        int $line,
        string $absolutePath,
        string $relativePath,
        ?string $editorUrl,
        bool $isDecorated
    ) : string {
        if (!$isDecorated || $editorUrl === null) {
            $format = self::LINK_FORMAT_WITHOUT_EDITOR;
        }

        return strtr(
            $format,
            [
                '{absolutePath}' => $absolutePath,
                '{editorUrl}' => $editorUrl === null ? '' : str_replace(
                    ['%relFile%', '%file%', '%line%'],
                    [$relativePath, $absolutePath, $line],
                    $editorUrl
                ),
                '{relativePath}' => $relativePath,
                '{shortPath}' => self::trimPath($relativePath),
                '{line}' => $line,
            ]
        );
    }

    private static function trimPath(string $path) : string
    {
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        if (count($parts) < 6) {
            return $path;
        }

        return implode(
            DIRECTORY_SEPARATOR,
            array_merge(
                array_slice($parts, 0, 3),
                ['...'],
                array_slice($parts, -2)
            )
        );
    }

    public static function highlight(string $message, ?string $tip, ?string $identifier, bool $isDecorated) : string
    {
        if (!$isDecorated) {
            return $message;
        }

        if (strpos($message, 'Ignored error pattern') === 0) {
            return $message;
        }

        // Remove escaped wildcard that breaks coloring
        $message = str_replace('\*', '*', $message);

        // Full Qualified Class Names
        $message = (string) preg_replace(
            "/([A-Z0-9]{1}[A-Za-z0-9_\-]+[\\\]+[A-Z0-9]{1}[A-Za-z0-9_\-\\\]+)/",
            '<fg=yellow>$1</>',
            $message
        );

        // Quoted strings
        $message = (string) preg_replace(
            "/(?<=[\"'])([A-Za-z0-9_\-\\\]+)(?=[\"'])/",
            '<fg=yellow>$1</>',
            $message
        );

        // Variable
        $message = (string) preg_replace(
            "/(?<=[:]{2}|[\s\"\(])([.]{3})?(\\$[A-Za-z0-9_\\-]+)(?=[\s|\"|\)]|$)/",
            '<fg=green>$1$2</>',
            $message
        );

        // Method
        $message = (string) preg_replace(
            '/(?<=[:]{2}|[\s])(\w+\(\))/',
            '<fg=blue>$1</>',
            $message
        );

        // Function
        $message = (string) preg_replace(
            '/(?<=function\s)(\w+)(?=\s)/',
            '<fg=blue>$1</>',
            $message
        );

        // Types
        $message = (string) preg_replace(
            '/(?<=[\s\|\(><])(null|true|false|int|float|bool|([-\w]+-)?string|array|object|mixed|resource|iterable|void|callable)(?=[:]{2}|[\.\s\|><,\(\)\{\}]+)/',
            '<fg=magenta>$1</>',
            $message
        );

        if ($tip !== null) {
            foreach (explode("\n", $tip) as $line) {
                $message .= "\n💡 <fg=blue>" . ltrim($line, ' •') . '</>';
            }
        }

        if ($identifier !== null) {
            $message .= "\n🔖 <fg=blue>" . $identifier . '</>';
        }

        return $message;
    }
}
