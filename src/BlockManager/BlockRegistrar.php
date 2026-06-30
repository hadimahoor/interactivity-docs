<?php

declare(strict_types=1);

namespace InteractivityDocs\BlockManager;

/**
 * Block Registrar
 *
 * Automatically discovers and registers all Gutenberg blocks from the build directory.
 * Supports grouped block structure: build/blocks/{group}/{block-name}/block.json
 *
 * @package InteractivityDocs\BlockManager
 * @since 1.0.0
 */
final class BlockRegistrar
{
    /**
     * Register all blocks found in the build directory
     *
     * Scans for block.json files using the pattern: build/blocks/{group}/{block-name}/block.json
     * and registers each block with WordPress.
     *
     * @return void
     */
    public function register(): void
    {
        $build_dir = \INTERACTIVITY_DOCS_PLUGIN_DIR . 'build/blocks/';

        if (!file_exists($build_dir)) {
            error_log('Interactivity Docs: Blocks directory does not exist: ' . $build_dir);
            return;
        }

        // Pattern matches: build/blocks/{group}/{block-name}/block.json
        $pattern = $build_dir . '*/*/block.json';
        $block_json_files = glob($pattern);

        if (empty($block_json_files)) {
            error_log('Interactivity Docs: No block.json files found with pattern: ' . $pattern);
            return;
        }

        foreach ($block_json_files as $file) {
            register_block_type(dirname($file));
        }
    }
}
