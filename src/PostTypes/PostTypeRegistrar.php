<?php

declare(strict_types=1);

namespace InteractivityDocs\PostTypes;

defined('ABSPATH') || exit;

/**
 * Post Type Registrar
 *
 * Registers custom post types for the university documentation system.
 * Includes Paper, Book, and Person post types with REST API support.
 *
 * @package InteractivityDocs\PostTypes
 * @since 1.0.0
 */
final class PostTypeRegistrar
{
    /**
     * Register all custom post types
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerPostTypes();
    }

    /**
     * Register university-related custom post types
     *
     * Registers Paper, Book, and Person post types with full Gutenberg
     * and REST API support for the Interactivity API.
     *
     * @return void
     */
    private function registerPostTypes(): void
    {
        $postTypes = [
            'paper' => [
                'label' => 'Paper',
                'icon'  => 'dashicons-media-document',
                'slug'  => 'paper',
            ],
            'book' => [
                'label' => 'Book',
                'icon'  => 'dashicons-book',
                'slug'  => 'book',
            ],
            'person' => [
                'label' => 'Person',
                'icon'  => 'dashicons-admin-users',
                'slug'  => 'person',
            ],
        ];
        foreach ($postTypes as $key => $config) {
            register_post_type($key, [
                'labels' => [
                    'name'          => $config['label'],
                    'singular_name' => $config['label'],
                ],
                'public'       => true,
                'show_in_rest' => true, // Required for Gutenberg and Interactivity API
                'has_archive'  => true,
                'menu_icon'    => $config['icon'],
                'supports'     => ['title', 'editor', 'excerpt', 'thumbnail'],
                'rewrite'      => ['slug' => $config['slug']],
                'taxonomies'   => ['post_tag'],
            ]);
        }
    }
}
