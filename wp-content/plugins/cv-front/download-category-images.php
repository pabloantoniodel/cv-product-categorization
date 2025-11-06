<?php
/**
 * Descargar imágenes de Unsplash para categorías
 * Usa la API gratuita de Unsplash para obtener imágenes de alta calidad
 */

// Cargar WordPress
require_once(__DIR__ . '/../../../wp-load.php');

// Crear directorio para imágenes
$images_dir = WP_CONTENT_DIR . '/uploads/category-images';
if (!file_exists($images_dir)) {
    mkdir($images_dir, 0755, true);
}

echo "==========================================\n";
echo "  DESCARGADOR DE IMÁGENES DE UNSPLASH\n";
echo "==========================================\n\n";

// Leer prompts
$json_file = __DIR__ . '/category-image-prompts.json';
if (!file_exists($json_file)) {
    echo "❌ Error: Ejecuta primero generate-ai-prompts.php\n";
    exit(1);
}

$prompts = json_decode(file_get_contents($json_file), true);

// Mapeo de términos de búsqueda para Unsplash
$search_terms = array(
    'peluqueria' => 'hair salon interior',
    'peluqueria-2' => 'barber shop',
    'moda' => 'fashion boutique',
    'telefonia' => 'mobile phone store',
    'alcohol' => 'liquor store wine',
    'mujer' => 'women fashion store',
    'pasteleria' => 'bakery pastries',
    'recordatorios' => 'gift shop souvenirs',
    'carne' => 'butcher shop meat',
    'tailandeses' => 'thai massage spa',
    'zapatos' => 'shoe store',
    'frutas' => 'fresh fruit market',
    'deportivas' => 'sports shoes store',
    'bocadillos' => 'sandwich shop',
    'hombre' => 'men fashion store',
    'desayunos' => 'breakfast cafe',
    'fotografias' => 'photography studio',
    'flores-naturales' => 'flower shop',
    'pescado' => 'fish market seafood',
    'pan' => 'bread bakery',
    'brazo' => 'tattoo studio',
    'motos' => 'motorcycle showroom',
    'vehiculos' => 'car dealership',
    'alquileres' => 'real estate office',
    'hamburguesa' => 'burger restaurant',
    'accesorios-flores' => 'florist accessories',
    'relojes' => 'watch store luxury',
    'kebab' => 'kebab restaurant',
);

$downloaded = 0;
$errors = 0;

foreach ($prompts as $item) {
    $slug = $item['slug'];
    $name = $item['name'];
    $filename = $item['filename'];
    
    // Buscar término de búsqueda
    $search_term = isset($search_terms[$slug]) ? $search_terms[$slug] : $name;
    
    echo "🔍 Buscando imagen para: {$name}\n";
    echo "   Término: {$search_term}\n";
    
    // URL de Unsplash (API gratuita, no requiere key para URLs directas) - Cuadrada 300x300
    $unsplash_url = 'https://source.unsplash.com/300x300/?' . urlencode($search_term);
    
    // Descargar imagen
    $image_path = $images_dir . '/' . $filename;
    
    $ch = curl_init($unsplash_url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    
    $image_data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200 && $image_data) {
        file_put_contents($image_path, $image_data);
        echo "   ✅ Descargada: {$filename}\n\n";
        $downloaded++;
        
        // Pequeña pausa para no saturar la API
        sleep(1);
    } else {
        echo "   ❌ Error al descargar (HTTP {$http_code})\n\n";
        $errors++;
    }
}

echo "==========================================\n";
echo "✅ Imágenes descargadas: {$downloaded}\n";
echo "❌ Errores: {$errors}\n";
echo "📁 Directorio: {$images_dir}\n";
echo "==========================================\n\n";

echo "🎉 Siguiente paso: php upload-category-images.php\n";

