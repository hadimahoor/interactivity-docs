<?php

declare(strict_types=1);

namespace InteractivityDocs\Cli;

defined('ABSPATH') || exit;

/**
 * Registers WP-CLI commands for the plugin.
 */
class CliRegistrar
{
    /**
     * Registers all CLI commands if WP-CLI is available.
     */
    public function register(): void
    {
        if (!defined('WP_CLI') || !WP_CLI) {
            return;
        }

        \WP_CLI::add_command('docs sync', Commands\SyncCommand::class);
    }
}
