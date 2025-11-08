<?php
declare(strict_types=1);

define('WP_USE_THEMES', false);
require_once __DIR__ . '/wp-load.php';
require_once WP_PLUGIN_DIR . '/cv-product-categorization/includes/autoload.php';

use Cv\ProductCategorization\Processors\OtrosCleaner;

$offset = isset($argv[1]) ? (int) $argv[1] : 0;
$limit  = isset($argv[2]) ? (int) $argv[2] : 200;
$apply  = in_array('--apply', $argv, true);

$cleaner = new OtrosCleaner($apply);
$cleaner->run($offset, $limit);
