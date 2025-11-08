<?php
/**
 * Plugin Name: CV Product Categorization Toolkit
 * Plugin URI:  https://ciudadvirtual.store
 * Description: Herramientas reutilizables para clasificar productos, reasignar categorías y mantener la taxonomía limpia.
 * Author:      Ciudad Virtual
 * Version:     1.0.0
 */

declare(strict_types=1);

namespace Cv\ProductCategorization;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/autoload.php';

Plugin::init();

