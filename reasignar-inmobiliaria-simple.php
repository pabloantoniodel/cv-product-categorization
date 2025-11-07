<?php
define('WP_USE_THEMES', false);
require_once('./wp-load.php');

echo "🏠 REASIGNANDO PRODUCTOS DE INMOBILIARIA\n";
echo "=====================================\n\n";

$alquiler_id = 841;
$venta_id = 842;
$traspaso_id = 843;

// IDs de las subcategorías antiguas de Inmobiliaria
$old_alquiler_cats = array(760, 761, 762, 763, 764, 765); // Alquiler - Pisos, Chalets, etc.
$old_venta_cats = array(766, 767, 768, 769, 770, 771); // Venta - Pisos, Chalets, etc.

// Obtener todos los productos de Inmobiliaria
$args = array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'tax_query' => array(
        array(
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => 745,
        ),
    ),
);

$products = get_posts($args);

echo "📦 Encontrados: " . count($products) . " productos de Inmobiliaria\n\n";

$changed = 0;

foreach ($products as $product) {
    $current_cats = wp_get_post_terms($product->ID, 'product_cat', array('fields' => 'ids'));
    
    $new_cats = array(745); // Inmobiliaria siempre
    
    // Determinar si tiene alguna subcategoría de Alquiler antigua
    $has_old_alquiler = !empty(array_intersect($current_cats, $old_alquiler_cats));
    
    // Determinar si tiene alguna subcategoría de Venta antigua
    $has_old_venta = !empty(array_intersect($current_cats, $old_venta_cats));
    
    // Analizar el título y descripción para determinar la subcategoría
    $title = strtolower($product->post_title);
    $desc = strtolower($product->post_excerpt);
    $text = $title . ' ' . $desc;
    
    // Traspaso (prioridad)
    if (stripos($text, 'traspaso') !== false) {
        $new_cats[] = $traspaso_id;
    }
    
    // Alquiler
    if (stripos($text, 'alquiler') !== false || stripos($text, 'alquilar') !== false || 
        stripos($text, 'se alquila') !== false || stripos($text, 'en alquiler') !== false ||
        $has_old_alquiler) {
        $new_cats[] = $alquiler_id;
    }
    
    // Venta
    if (stripos($text, 'venta') !== false || stripos($text, 'vender') !== false || 
        stripos($text, 'se vende') !== false || stripos($text, 'en venta') !== false ||
        $has_old_venta) {
        $new_cats[] = $venta_id;
    }
    
    $new_cats = array_unique($new_cats);
    
    // Actualizar
    wp_set_post_terms($product->ID, $new_cats, 'product_cat');
    $changed++;
    
    if ($changed % 100 == 0) {
        echo "✅ Procesados: {$changed}\n";
    }
}

echo "\n=====================================\n";
echo "📊 RESUMEN:\n";
echo "   Total procesados: {$changed}\n";
echo "   ✅ Todos reasignados a las nuevas subcategorías\n";
