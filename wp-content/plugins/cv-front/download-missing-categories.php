<?php
/**
 * Descargar imágenes para categorías específicas sin productos
 */

require_once(__DIR__ . '/../../../wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

$images_dir = WP_CONTENT_DIR . '/uploads/category-images';
if (!file_exists($images_dir)) {
    mkdir($images_dir, 0755, true);
}

echo "==========================================\n";
echo "  CATEGORÍAS FALTANTES (sin productos)\n";
echo "==========================================\n\n";

// Imágenes específicas de Pexels para categorías sin productos
$categories = array(
    array(
        'term_id' => 560,
        'slug' => 'joyeria',
        'name' => 'JOYERIA',
        'url' => 'https://images.pexels.com/photos/1721943/pexels-photo-1721943.jpeg?auto=compress&cs=tinysrgb&w=400'
    ),
    array(
        'term_id' => 597,
        'slug' => 'marketing',
        'name' => 'MARKETING',
        'url' => 'https://images.pexels.com/photos/7413915/pexels-photo-7413915.jpeg?auto=compress&cs=tinysrgb&w=400'
    ),
    array(
        'term_id' => 581,
        'slug' => 'masajes',
        'name' => 'MASAJES',
        'url' => 'https://images.pexels.com/photos/3757376/pexels-photo-3757376.jpeg?auto=compress&cs=tinysrgb&w=400'
    ),
    array(
        'term_id' => 501,
        'slug' => 'musica',
        'name' => 'MUSICA',
        'url' => 'https://images.pexels.com/photos/1407322/pexels-photo-1407322.jpeg?auto=compress&cs=tinysrgb&w=400'
    ),
    array(
        'term_id' => 473,
        'slug' => 'perfumes',
        'name' => 'Perfumes',
        'url' => 'https://images.pexels.com/photos/965989/pexels-photo-965989.jpeg?auto=compress&cs=tinysrgb&w=400'
    ),
    array(
        'term_id' => 479,
        'slug' => 'reportajes',
        'name' => 'REPORTAJES',
        'url' => 'https://images.pexels.com/photos/2074130/pexels-photo-2074130.jpeg?auto=compress&cs=tinysrgb&w=400'
    ),
    array(
        'term_id' => 593,
        'slug' => 'tarot',
        'name' => 'TAROT',
        'url' => 'https://images.pexels.com/photos/7192994/pexels-photo-7192994.jpeg?auto=compress&cs=tinysrgb&w=400'
    ),
);

$success = 0;
$errors = 0;

foreach ($categories as $cat) {
    echo "📥 {$cat['name']}\n";
    
    // Descargar imagen
    $ch = curl_init($cat['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $image_data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200 && $image_data) {
        // Guardar temporalmente
        $temp_file = $images_dir . '/' . $cat['slug'] . '.jpg';
        file_put_contents($temp_file, $image_data);
        
        // Subir a WordPress
        $file_array = array(
            'name' => $cat['slug'] . '.jpg',
            'tmp_name' => $temp_file
        );
        
        $attachment_id = media_handle_sideload($file_array, 0, 'Categoría: ' . $cat['name']);
        
        if (!is_wp_error($attachment_id)) {
            // Asignar a categoría
            update_term_meta($cat['term_id'], 'thumbnail_id', $attachment_id);
            
            echo "   ✅ Asignada (Attachment ID: {$attachment_id})\n\n";
            $success++;
        } else {
            echo "   ❌ Error al subir\n\n";
            $errors++;
        }
    } else {
        echo "   ❌ Error descargando (HTTP {$http_code})\n\n";
        $errors++;
    }
}

echo "==========================================\n";
echo "✅ Asignadas: {$success}\n";
echo "❌ Errores: {$errors}\n";
echo "==========================================\n\n";

if ($success > 0) {
    echo "🎉 Categorías sin productos ahora tienen imágenes!\n";
}

