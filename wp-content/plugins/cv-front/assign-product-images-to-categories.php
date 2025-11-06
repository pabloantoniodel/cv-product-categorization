<?php
/**
 * Asignar imagen del primer producto a categorías sin imagen
 */

// Cargar WordPress
require_once(__DIR__ . '/../../../wp-load.php');

global $wpdb;

echo "==========================================\n";
echo "  ASIGNAR IMÁGENES DE PRODUCTOS A CATEGORÍAS\n";
echo "==========================================\n\n";

// Buscar categorías sin imagen
$categories = $wpdb->get_results("
    SELECT 
        t.term_id,
        t.name,
        t.slug,
        tt.count
    FROM {$wpdb->terms} t
    INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
    LEFT JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id AND tm.meta_key = 'thumbnail_id'
    WHERE tt.taxonomy = 'product_cat'
    AND (tm.meta_value IS NULL OR tm.meta_value = '0')
    AND tt.count > 0
    ORDER BY tt.count DESC
");

echo "📊 Encontradas " . count($categories) . " categorías sin imagen (con productos)\n\n";

$assigned = 0;
$errors = 0;

foreach ($categories as $category) {
    echo "📂 {$category->name} ({$category->count} productos)\n";
    
    // Obtener productos de esta categoría
    $products = $wpdb->get_results($wpdb->prepare("
        SELECT p.ID
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
        INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        WHERE tt.term_id = %d
        AND p.post_type = 'product'
        AND p.post_status = 'publish'
        ORDER BY p.post_date DESC
        LIMIT 1
    ", $category->term_id));
    
    if (empty($products)) {
        echo "   ⚠️  Sin productos publicados\n\n";
        $errors++;
        continue;
    }
    
    $product_id = $products[0]->ID;
    
    // Obtener imagen destacada del producto
    $thumbnail_id = get_post_thumbnail_id($product_id);
    
    if (!$thumbnail_id) {
        echo "   ⚠️  Producto sin imagen destacada (ID: {$product_id})\n\n";
        $errors++;
        continue;
    }
    
    // Asignar imagen a la categoría
    update_term_meta($category->term_id, 'thumbnail_id', $thumbnail_id);
    
    // Obtener info de la imagen
    $image_url = wp_get_attachment_url($thumbnail_id);
    $product_title = get_the_title($product_id);
    
    echo "   ✅ Asignada imagen de: \"{$product_title}\"\n";
    echo "   🖼️  Attachment ID: {$thumbnail_id}\n";
    echo "   🔗 {$image_url}\n\n";
    
    $assigned++;
}

echo "==========================================\n";
echo "✅ Imágenes asignadas: {$assigned}\n";
echo "❌ Errores/Omitidas: {$errors}\n";
echo "==========================================\n\n";

if ($assigned > 0) {
    echo "🎉 ¡Proceso completado!\n";
    echo "🌐 Verifica: https://ciudadvirtual.app/wp-admin/edit-tags.php?taxonomy=product_cat\n";
    echo "\n";
    echo "ℹ️  Las imágenes son de productos reales de cada categoría\n";
}

