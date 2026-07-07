<?php
/**
 * PHPUnit bootstrap file.
 */

// Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Initialize WP_Mock
WP_Mock::bootstrap();
