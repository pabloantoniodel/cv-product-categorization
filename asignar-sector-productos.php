<?php
declare(strict_types=1);

define('WP_USE_THEMES', false);
require_once __DIR__ . '/wp-load.php';
require_once WP_PLUGIN_DIR . '/cv-product-categorization/includes/autoload.php';

use Cv\ProductCategorization\Processors\SectorAssigner;

$perPage = isset($argv[1]) ? (int) $argv[1] : 200;

$assigner = new SectorAssigner();
$assigner->run([
    'per_page' => $perPage > 0 ? $perPage : 200,
]);
