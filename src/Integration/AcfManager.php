<?php

namespace InteractivityDocs\Integration;

/**
 * Handles ACF integration: Local JSON path and dependency checks.
 */
class AcfManager
{
    /**
     * Register ACF-related hooks.
     */
    public function register(): void
    {
        add_filter('acf/settings/load_json', [ $this, 'addLocalJsonPath' ]);

        // Native dependency management exists since WP 6.5.
        // Only show a manual notice on older versions.
        if (version_compare(get_bloginfo('version'), '6.5', '<')) {
            add_action('admin_notices', [ $this, 'renderMissingDependencyNotice' ]);
        }
    }

    /**
     * Add the plugin's acf-json directory to ACF's load paths.
     *
     * @param array $paths Existing load paths.
     * @return array
     */
    public function addLocalJsonPath(array $paths): array
    {
        $paths[] = INTERACTIVITY_DOCS_ACF_PATH;

        return $paths;
    }

    /**
     * Show an admin notice when ACF is not active (WP < 6.5 only).
     */
    public function renderMissingDependencyNotice(): void
    {
        if (class_exists('ACF')) {
            return;
        }

        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__(
                'Interactivity Docs requires the Advanced Custom Fields plugin to be installed and active.',
                'interactivity-docs'
            )
        );
    }
}
