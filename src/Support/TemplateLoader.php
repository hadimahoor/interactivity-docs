<?php

namespace InteractivityDocs\Support;

defined('ABSPATH') || exit;

/**
 * Template Loader
 *
 * Handles loading and rendering block templates with theme override support.
 * Searches in theme directory first, then falls back to plugin templates.
 */
class TemplateLoader
{
    /**
     * Render a template with provided data
     *
     * @param string $blockPath Block directory name (e.g., 'docs-archive')
     * @param string $template  Template filename without .php extension
     * @param array  $data      Data to extract into template scope
     * @return string           Rendered template output
     */
    public static function render(
        string $blockPath,
        string $template,
        array $data = []
    ): string {
        $path = self::locate($blockPath, $template);

        if (!$path) {
            return '';
        }

        ob_start();
        extract($data, EXTR_SKIP);
        include $path;
        return (string) ob_get_clean();
    }

    /**
     * Locate template file in theme or plugin
     *
     * Priority:
     * 1. Child theme: {child-theme}/blocks/{blockPath}/{template}.php
     * 2. Parent theme: {parent-theme}/blocks/{blockPath}/{template}.php
     * 3. Plugin: {plugin}/src/blocks/{blockPath}/templates/{template}.php
     *
     * @param string $blockPath Block directory name
     * @param string $template  Template filename without extension
     * @return string|null      Full path to template file, or null if not found
     */
    private static function locate(string $blockPath, string $template): ?string
    {

        $templateFile = 'blocks/' . trailingslashit($blockPath) . $template . '.php';

        // Check child theme first
        $childPath = trailingslashit(get_stylesheet_directory()) . $templateFile;
        if (file_exists($childPath)) {
            return $childPath;
        }

        // Check parent theme if exists
        if (get_template_directory() !== get_stylesheet_directory()) {
            $parentPath = trailingslashit(get_template_directory()) . $templateFile;
            if (file_exists($parentPath)) {
                return $parentPath;
            }
        }

        // Fall back to plugin template
        $pluginPath = \INTERACTIVITY_DOCS_PLUGIN_DIR
            . 'src/blocks/'
            . trailingslashit($blockPath)
            . 'templates/'
            . $template
            . '.php';

        if (file_exists($pluginPath)) {
            return $pluginPath;
        }

        return null;
    }
}
