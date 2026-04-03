<?php

declare(strict_types=1);

namespace TicketSwap\PHPStanErrorFormatter;

// Copied from https://github.com/shipfastlabs/agent-detector/blob/main/src/AgentDetector.php
final class AgentDetector
{
    public static function isAgent() : bool
    {
        $aiAgent = getenv('AI_AGENT');

        if (is_string($aiAgent) && trim($aiAgent) !== '') {
            return true;
        }

        $agentsWithEnvVars = [
            'cursor' => ['CURSOR_AGENT'],
            'gemini' => ['GEMINI_CLI'],
            'codex' => ['CODEX_SANDBOX', 'CODEX_THREAD_ID'],
            'augment-cli' => ['AUGMENT_AGENT'],
            'opencode' => ['OPENCODE_CLIENT', 'OPENCODE'],
            'amp' => ['AMP_CURRENT_THREAD_ID'],
            'claude' => ['CLAUDECODE', 'CLAUDE_CODE'],
            'replit' => ['REPL_ID'],
        ];

        foreach ($agentsWithEnvVars as $envVars) {
            foreach ($envVars as $envVar) {
                if (getenv($envVar) !== false) {
                    return true;
                }
            }
        }

        if (file_exists('/opt/.devin')) {
            return true;
        }

        return false;
    }
}
