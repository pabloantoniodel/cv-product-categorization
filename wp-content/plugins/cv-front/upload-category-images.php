<?php
/**
 * Subir imágenes a WordPress y asignarlas a categorías
 */

// Cargar WordPress
require_once(__DIR__ . '/../../../wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

$images_dir = WP_CONTENT_DIR . '/uploads/category-images';

/**
 * Redimensionar imagen a 300x300
 */
function resize_image_to_300($image_path) {
    if (!file_exists($image_path)) {
        return false;
    }
    
    // Obtener información de la imagen
    $image_info = getimagesize($image_path);
    if (!$image_info) {
        return false;
    }
    
    list($width, $height, $type) = $image_info;
    
    // Si ya es 300x300, no redimensionar
    if ($width == 300 && $height == 300) {
        return $image_path;
    }
    
    // Crear imagen según el tipo
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($image_path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($image_path);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($image_path);
            break;
        default:
            return false;
    }
    
    if (!$source) {
        return false;
    }
    
    // Crear nueva imagen 300x300
    $new_image = imagecreatetruecolor(300, 300);
    
    // Preservar transparencia para PNG
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
    }
    
    // Redimensionar (crop desde el centro para mantener aspecto cuadrado)
    $src_aspect = $width / $height;
    
    if ($src_aspect > 1) {
        // Imagen más ancha que alta
        $src_width = $height;
        $src_height = $height;
        $src_x = ($width - $height) / 2;
        $src_y = 0;
    } else {
        // Imagen más alta que ancha o cuadrada
        $src_width = $width;
        $src_height = $width;
        $src_x = 0;
        $src_y = ($height - $width) / 2;
    }
    
    imagecopyresampled($new_image, $source, 0, 0, $src_x, $src_y, 300, 300, $src_width, $src_height);
    
    // Guardar imagen redimensionada
    $resized_path = str_replace('.jpg', '-300x300.jpg', $image_path);
    imagejpeg($new_image, $resized_path, 90);
    
    // Liberar memoria
    imagedestroy($source);
    imagedestroy($new_image);
    
    return $resized_path;
}

echo "==========================================\n";
echo "  SUBIR IMÁGENES A WORDPRESS\n";
echo "  (Auto-redimensionado a 300x300)\n";
echo "==========================================\n\n";

// Verificar directorio
if (!file_exists($images_dir)) {
    echo "❌ Error: Directorio de imágenes no existe\n";
    echo "   Ejecuta primero: php download-category-images.php\n";
    exit(1);
}

// Leer prompts
$json_file = __DIR__ . '/category-image-prompts.json';
if (!file_exists($json_file)) {
    echo "❌ Error: Ejecuta primero generate-ai-prompts.php\n";
    exit(1);
}

$prompts = json_decode(file_get_contents($json_file), true);

$uploaded = 0;
$errors = 0;
$skipped = 0;

foreach ($prompts as $item) {
    $term_id = $item['term_id'];
    $name = $item['name'];
    $filename = $item['filename'];
    $image_path = $images_dir . '/' . $filename;
    
    echo "📤 Procesando: {$name} (ID: {$term_id})\n";
    
    // Verificar si la imagen existe
    if (!file_exists($image_path)) {
        echo "   ⚠️  Imagen no encontrada: {$filename}\n\n";
        $skipped++;
        continue;
    }
    
    // Verificar si ya tiene thumbnail
    $existing_thumbnail = get_term_meta($term_id, 'thumbnail_id', true);
    if ($existing_thumbnail && $existing_thumbnail != '0') {
        echo "   ℹ️  Ya tiene imagen asignada (ID: {$existing_thumbnail})\n\n";
        $skipped++;
        continue;
    }
    
    // Redimensionar imagen a 300x300 antes de subir
    $resized_path = resize_image_to_300($image_path);
    if (!$resized_path) {
        echo "   ⚠️  No se pudo redimensionar, usando original\n";
        $resized_path = $image_path;
    }
    
    // Preparar archivo para subida
    $file_array = array(
        'name' => $filename,
        'tmp_name' => $resized_path
    );
    
    // Subir a biblioteca de medios
    $attachment_id = media_handle_sideload($file_array, 0, 'Categoría: ' . $name);
    
    if (is_wp_error($attachment_id)) {
        echo "   ❌ Error al subir: " . $attachment_id->get_error_message() . "\n\n";
        $errors++;
        continue;
    }
    
    // Asignar como thumbnail de la categoría
    update_term_meta($term_id, 'thumbnail_id', $attachment_id);
    
    // Añadir metadatos
    update_post_meta($attachment_id, '_wp_attachment_image_alt', $name);
    
    echo "   ✅ Subida exitosa (Attachment ID: {$attachment_id})\n";
    echo "   🔗 Asignada a categoría\n\n";
    $uploaded++;
}

echo "==========================================\n";
echo "✅ Imágenes subidas: {$uploaded}\n";
echo "⚠️  Omitidas: {$skipped}\n";
echo "❌ Errores: {$errors}\n";
echo "==========================================\n\n";

if ($uploaded > 0) {
    echo "🎉 ¡Proceso completado! Las categorías ahora tienen imágenes.\n";
    echo "🌐 Revisa: https://ciudadvirtual.app/wp-admin/edit-tags.php?taxonomy=product_cat\n";
}

