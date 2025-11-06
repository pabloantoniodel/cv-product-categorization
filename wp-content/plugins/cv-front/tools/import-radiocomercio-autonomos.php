<?php
/**
 * Importar artículos de Radio Comercio sobre autónomos
 * 
 * USO:
 * sudo -u ciudadvirtual wp eval-file wp-content/plugins/cv-front/tools/import-radiocomercio-autonomos.php --allow-root
 * 
 * @package CV_Front
 */

// Asegurar que se ejecuta en contexto WordPress
if (!defined('ABSPATH')) {
    require_once(__DIR__ . '/../../../../wp-load.php');
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  IMPORTAR ARTÍCULOS DE RADIO COMERCIO SOBRE AUTÓNOMOS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// URL de la API REST de Radio Comercio
$api_url = 'https://radiocomercio.com/wp-json/wp/v2/posts?per_page=100&_embed&search=autónomos';

echo "🔄 Descargando artículos desde API REST...\n";
echo "   URL: {$api_url}\n\n";

// Obtener artículos
$response = wp_remote_get($api_url, array(
    'timeout' => 30,
    'sslverify' => false
));

if (is_wp_error($response)) {
    echo "❌ Error al conectar con Radio Comercio: " . $response->get_error_message() . "\n\n";
    exit(1);
}

$body = wp_remote_retrieve_body($response);
$posts = json_decode($body, true);

if (!is_array($posts) || empty($posts)) {
    echo "⚠️ No se encontraron artículos sobre autónomos\n\n";
    exit(0);
}

echo "✅ Encontrados " . count($posts) . " artículos sobre autónomos\n\n";

echo "───────────────────────────────────────────────────────────────\n";
echo "  IMPORTANDO ARTÍCULOS\n";
echo "───────────────────────────────────────────────────────────────\n\n";

$imported = 0;
$skipped = 0;
$errors = 0;

// Crear o obtener categoría "Radio Comercio"
$category = get_term_by('name', 'Radio Comercio', 'category');
if (!$category) {
    $category_id = wp_create_category('Radio Comercio');
    echo "📁 Categoría 'Radio Comercio' creada (ID: {$category_id})\n\n";
} else {
    $category_id = $category->term_id;
    echo "📁 Usando categoría existente 'Radio Comercio' (ID: {$category_id})\n\n";
}

// Crear o obtener categoría "Autónomos"
$autonomos_cat = get_term_by('name', 'Autónomos', 'category');
if (!$autonomos_cat) {
    $autonomos_cat_id = wp_create_category('Autónomos');
    echo "📁 Categoría 'Autónomos' creada (ID: {$autonomos_cat_id})\n\n";
} else {
    $autonomos_cat_id = $autonomos_cat->term_id;
    echo "📁 Usando categoría existente 'Autónomos' (ID: {$autonomos_cat_id})\n\n";
}

foreach ($posts as $post) {
    $title = isset($post['title']['rendered']) ? $post['title']['rendered'] : '';
    $link = isset($post['link']) ? $post['link'] : '';
    
    // Verificar si ya existe (por título)
    $existing = get_page_by_title($title, OBJECT, 'post');
    if ($existing) {
        echo "⏭️  {$title}\n";
        echo "   Ya existe en la base de datos\n\n";
        $skipped++;
        continue;
    }
    
    // Preparar contenido
    $content = isset($post['content']['rendered']) ? $post['content']['rendered'] : '';
    $excerpt = isset($post['excerpt']['rendered']) ? wp_strip_all_tags($post['excerpt']['rendered']) : '';
    $date = isset($post['date']) ? $post['date'] : current_time('mysql');
    $author_name = isset($post['_embedded']['author'][0]['name']) ? $post['_embedded']['author'][0]['name'] : 'Francisco';
    
    // Añadir enlace original al final del contenido
    $content .= "\n\n<hr>\n\n";
    $content .= "<p><em>Artículo original publicado en <a href=\"{$link}\" target=\"_blank\" rel=\"noopener\">Radio Comercio</a></em></p>";
    
    // Crear post
    $post_data = array(
        'post_title' => $title,
        'post_content' => $content,
        'post_excerpt' => $excerpt,
        'post_status' => 'publish',
        'post_type' => 'post',
        'post_date' => $date,
        'post_category' => array($category_id, $autonomos_cat_id),
        'meta_input' => array(
            '_radiocomercio_original_url' => $link,
            '_radiocomercio_imported' => current_time('mysql'),
            '_radiocomercio_post_id' => $post['id']
        )
    );
    
    $new_post_id = wp_insert_post($post_data, true);
    
    if (is_wp_error($new_post_id)) {
        echo "❌ {$title}\n";
        echo "   Error: " . $new_post_id->get_error_message() . "\n\n";
        $errors++;
        continue;
    }
    
    echo "✅ {$title}\n";
    echo "   ID: {$new_post_id}\n";
    
    // Descargar e importar imagen destacada
    if (isset($post['_embedded']['wp:featuredmedia'][0]['source_url'])) {
        $image_url = $post['_embedded']['wp:featuredmedia'][0]['source_url'];
        echo "   🖼️ Descargando imagen...\n";
        
        $attachment_id = download_and_attach_image($image_url, $new_post_id, $title);
        
        if ($attachment_id) {
            set_post_thumbnail($new_post_id, $attachment_id);
            echo "   ✅ Imagen destacada configurada\n";
        }
    }
    
    echo "\n";
    $imported++;
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "  RESUMEN\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Total encontrados:   " . count($posts) . "\n";
echo "Importados:          {$imported}\n";
echo "Ya existían:         {$skipped}\n";
echo "Errores:             {$errors}\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if ($imported > 0) {
    echo "✅ Artículos importados correctamente\n";
    echo "   Ver en: https://ciudadvirtual.app/category/autonomos/\n\n";
}

/**
 * Descargar imagen y adjuntarla a un post
 */
function download_and_attach_image($image_url, $post_id, $title) {
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    $tmp = download_url($image_url);
    
    if (is_wp_error($tmp)) {
        return false;
    }
    
    $file_array = array(
        'name' => basename($image_url),
        'tmp_name' => $tmp
    );
    
    $attachment_id = media_handle_sideload($file_array, $post_id, $title);
    
    if (is_wp_error($attachment_id)) {
        @unlink($file_array['tmp_name']);
        return false;
    }
    
    return $attachment_id;
}

exit(0);

