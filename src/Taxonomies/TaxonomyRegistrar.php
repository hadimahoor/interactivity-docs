<?php

declare(strict_types=1);

namespace InteractivityDocs\Taxonomies;

defined('ABSPATH') || exit;

/**
 * Taxonomy Registrar
 *
 * Registers custom taxonomies for the university documentation system.
 * Includes Magazine, Publication, Year, Language, Book Type, Paper Type,
 * and Gender taxonomies with REST API support.
 *
 * @package InteractivityDocs\Taxonomies
 * @since 1.0.0
 */
final class TaxonomyRegistrar
{
    /**
     * Register all custom taxonomies
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerTaxonomies();
    }

    /**
     * Register university-related custom taxonomies
     *
     * Registers taxonomies for categorizing papers, books, and persons
     * with full Gutenberg and REST API support.
     *
     * @return void
     */
    private function registerTaxonomies(): void
    {
        $taxonomies = [
            'magazine_tax' => [
                'label'      => 'Magazine',
                'post_types' => ['paper'],
            ],
            'publication_tax' => [
                'label'      => 'Publication',
                'post_types' => ['book'],
            ],
            'year_tax' => [
                'label'      => 'Year',
                'post_types' => ['paper', 'book'],
            ],
            'language_tax' => [
                'label'      => 'Language',
                'post_types' => ['paper', 'book'],
            ],
            'booktype_tax' => [
                'label'      => 'Book Type',
                'post_types' => ['book'],
            ],
            'papertype_tax' => [
                'label'      => 'Paper Type',
                'post_types' => ['paper'],
            ],
            'gender_tax' => [
                'label'      => 'Gender',
                'post_types' => ['person'],
            ],
        ];

        foreach ($taxonomies as $key => $config) {
            register_taxonomy($key, $config['post_types'], [
                'hierarchical' => true,
                'labels'       => ['name' => $config['label']],
                'show_ui'      => true,
                'show_in_rest' => true, // Required for Gutenberg editor
                'rewrite'      => ['slug' => str_replace('_tax', '', $key)],
            ]);
        }
    }
}
