<?php

/**
 * Book Card Loader
 *
 * Loads the default card template configured for the 'book' entity type.
 *
 * @package InteractivityDocs
 *
 * @var string $blockPath Block path used to locate templates.
 */

use InteractivityDocs\Support\TemplateLoader;

defined('ABSPATH') || exit;

// Render the default card template for the 'book' post type.
echo TemplateLoader::render($blockPath, 'cards/card-default', ['type' => 'book']);
