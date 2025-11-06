<?php
/**
 * Generador de imágenes para categorías sin thumbnail
 * Crea imágenes con gradientes y el nombre de la categoría
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Category_Image_Generator {
    
    private $gradients = array(
        array('#667eea', '#764ba2'), // Morado
        array('#f093fb', '#f5576c'), // Rosa
        array('#4facfe', '#00f2fe'), // Azul
        array('#43e97b', '#38f9d7'), // Verde
        array('#fa709a', '#fee140'), // Naranja-Rosa
        array('#30cfd0', '#330867'), // Azul oscuro
        array('#a8edea', '#fed6e3'), // Pastel
        array('#ff9a56', '#ff6a88'), // Coral
    );
    
    /**
     * Generar imagen para una categoría
     */
    public function generate_image($term_id, $category_name) {
        // Dimensiones de la imagen (cuadrada 300x300)
        $width = 300;
        $height = 300;
        
        // Crear imagen
        $image = imagecreatetruecolor($width, $height);
        
        // Seleccionar gradiente basado en el term_id
        $gradient_index = $term_id % count($this->gradients);
        $colors = $this->gradients[$gradient_index];
        
        // Convertir colores hex a RGB
        $color1 = $this->hex_to_rgb($colors[0]);
        $color2 = $this->hex_to_rgb($colors[1]);
        
        // Crear gradiente diagonal
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                // Calcular posición en el gradiente (diagonal)
                $ratio = ($x + $y) / ($width + $height);
                
                $r = $color1['r'] + ($color2['r'] - $color1['r']) * $ratio;
                $g = $color1['g'] + ($color2['g'] - $color1['g']) * $ratio;
                $b = $color1['b'] + ($color2['b'] - $color1['b']) * $ratio;
                
                $color = imagecolorallocate($image, $r, $g, $b);
                imagesetpixel($image, $x, $y, $color);
            }
        }
        
        // Añadir texto (nombre de la categoría)
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        // Buscar fuente TrueType
        $font_paths = array(
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/msttcorefonts/Arial_Bold.ttf',
        );
        
        $font_file = null;
        foreach ($font_paths as $path) {
            if (file_exists($path)) {
                $font_file = $path;
                break;
            }
        }
        
        if ($font_file) {
            // Usar fuente TrueType (tamaño ajustado para 300x300)
            $font_size = 24; // Reducido de 60 a 24 para imagen más pequeña
            
            // Calcular posición centrada
            $bbox = imagettfbbox($font_size, 0, $font_file, $category_name);
            $text_width = abs($bbox[4] - $bbox[0]);
            $text_height = abs($bbox[5] - $bbox[1]);
            
            $x = ($width - $text_width) / 2;
            $y = ($height + $text_height) / 2;
            
            // Sombra del texto
            imagettftext($image, $font_size, 0, $x + 2, $y + 2, $black, $font_file, $category_name);
            // Texto principal
            imagettftext($image, $font_size, 0, $x, $y, $white, $font_file, $category_name);
        } else {
            // Fallback a fuente integrada
            $text_x = ($width - (strlen($category_name) * 10)) / 2;
            $text_y = ($height - 20) / 2;
            imagestring($image, 5, $text_x + 2, $text_y + 2, $category_name, $black);
            imagestring($image, 5, $text_x, $text_y, $category_name, $white);
        }
        
        // Guardar imagen temporalmente
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['path'] . '/cat-' . $term_id . '-' . time() . '.jpg';
        
        imagejpeg($image, $temp_file, 90);
        imagedestroy($image);
        
        return $temp_file;
    }
    
    /**
     * Convertir color hexadecimal a RGB
     */
    private function hex_to_rgb($hex) {
        $hex = str_replace('#', '', $hex);
        
        return array(
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        );
    }
    
    /**
     * Subir imagen a biblioteca de medios
     */
    public function upload_to_media_library($file_path, $category_name) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $file_array = array(
            'name' => sanitize_file_name($category_name) . '.jpg',
            'tmp_name' => $file_path
        );
        
        // Subir imagen
        $attachment_id = media_handle_sideload($file_array, 0, 'Categoría: ' . $category_name);
        
        if (is_wp_error($attachment_id)) {
            @unlink($file_path);
            return false;
        }
        
        return $attachment_id;
    }
    
    /**
     * Procesar todas las categorías sin imagen
     */
    public function process_categories_without_images() {
        global $wpdb;
        
        // Buscar categorías sin imagen
        $categories = $wpdb->get_results("
            SELECT 
                t.term_id,
                t.name,
                t.slug
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            LEFT JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id AND tm.meta_key = 'thumbnail_id'
            WHERE tt.taxonomy = 'product_cat'
            AND (tm.meta_value IS NULL OR tm.meta_value = '0')
            ORDER BY tt.count DESC
        ");
        
        $results = array();
        
        foreach ($categories as $category) {
            error_log("📸 Generando imagen para: {$category->name} (ID: {$category->term_id})");
            
            // Generar imagen
            $image_file = $this->generate_image($category->term_id, $category->name);
            
            if ($image_file && file_exists($image_file)) {
                // Subir a biblioteca de medios
                $attachment_id = $this->upload_to_media_library($image_file, $category->name);
                
                if ($attachment_id) {
                    // Asignar como thumbnail de la categoría
                    update_term_meta($category->term_id, 'thumbnail_id', $attachment_id);
                    
                    $results[] = array(
                        'term_id' => $category->term_id,
                        'name' => $category->name,
                        'attachment_id' => $attachment_id,
                        'status' => 'success'
                    );
                    
                    error_log("✅ Imagen creada y asignada: {$category->name} (Attachment ID: {$attachment_id})");
                } else {
                    $results[] = array(
                        'term_id' => $category->term_id,
                        'name' => $category->name,
                        'status' => 'error_upload'
                    );
                    
                    error_log("❌ Error al subir imagen: {$category->name}");
                }
            } else {
                $results[] = array(
                    'term_id' => $category->term_id,
                    'name' => $category->name,
                    'status' => 'error_generate'
                );
                
                error_log("❌ Error al generar imagen: {$category->name}");
            }
        }
        
        return $results;
    }
}

