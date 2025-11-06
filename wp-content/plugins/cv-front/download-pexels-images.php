<?php
/**
 * Descargar imágenes de Pexels (libre de derechos, uso comercial)
 * Licencia: Pexels License - Gratis para uso comercial, sin atribución
 */

// Cargar WordPress
require_once(__DIR__ . '/../../../wp-load.php');

// Crear directorio para imágenes
$images_dir = WP_CONTENT_DIR . '/uploads/category-images';
if (!file_exists($images_dir)) {
    mkdir($images_dir, 0755, true);
}

echo "==========================================\n";
echo "  DESCARGA DE PEXELS (Libre derechos)\n";
echo "==========================================\n\n";

// Leer prompts
$json_file = __DIR__ . '/category-image-prompts.json';
if (!file_exists($json_file)) {
    echo "❌ Error: Ejecuta primero generate-ai-prompts.php\n";
    exit(1);
}

$prompts = json_decode(file_get_contents($json_file), true);

// IDs específicas de Pexels para cada categoría (más confiable que búsqueda)
// Estas son fotos reales de Pexels con licencia libre
$pexels_images = array(
    'peluqueria' => 'https://images.pexels.com/photos/1319461/pexels-photo-1319461.jpeg?auto=compress&cs=tinysrgb&w=400',
    'moda' => 'https://images.pexels.com/photos/1488463/pexels-photo-1488463.jpeg?auto=compress&cs=tinysrgb&w=400',
    'telefonia' => 'https://images.pexels.com/photos/699122/pexels-photo-699122.jpeg?auto=compress&cs=tinysrgb&w=400',
    'alcohol' => 'https://images.pexels.com/photos/52994/wine-bottle-wine-white-wine-52994.jpeg?auto=compress&cs=tinysrgb&w=400',
    'mujer' => 'https://images.pexels.com/photos/972995/pexels-photo-972995.jpeg?auto=compress&cs=tinysrgb&w=400',
    'pasteleria' => 'https://images.pexels.com/photos/205961/pexels-photo-205961.jpeg?auto=compress&cs=tinysrgb&w=400',
    'recordatorios' => 'https://images.pexels.com/photos/264787/pexels-photo-264787.jpeg?auto=compress&cs=tinysrgb&w=400',
    'carne' => 'https://images.pexels.com/photos/65175/pexels-photo-65175.jpeg?auto=compress&cs=tinysrgb&w=400',
    'tailandeses' => 'https://images.pexels.com/photos/3757952/pexels-photo-3757952.jpeg?auto=compress&cs=tinysrgb&w=400',
    'zapatos' => 'https://images.pexels.com/photos/1598505/pexels-photo-1598505.jpeg?auto=compress&cs=tinysrgb&w=400',
    'frutas' => 'https://images.pexels.com/photos/1132047/pexels-photo-1132047.jpeg?auto=compress&cs=tinysrgb&w=400',
    'deportivas' => 'https://images.pexels.com/photos/2529148/pexels-photo-2529148.jpeg?auto=compress&cs=tinysrgb&w=400',
    'bocadillos' => 'https://images.pexels.com/photos/1600727/pexels-photo-1600727.jpeg?auto=compress&cs=tinysrgb&w=400',
    'hombre' => 'https://images.pexels.com/photos/298863/pexels-photo-298863.jpeg?auto=compress&cs=tinysrgb&w=400',
    'desayunos' => 'https://images.pexels.com/photos/376464/pexels-photo-376464.jpeg?auto=compress&cs=tinysrgb&w=400',
    'fotografias' => 'https://images.pexels.com/photos/90946/pexels-photo-90946.jpeg?auto=compress&cs=tinysrgb&w=400',
    'flores-naturales' => 'https://images.pexels.com/photos/931177/pexels-photo-931177.jpeg?auto=compress&cs=tinysrgb&w=400',
    'pescado' => 'https://images.pexels.com/photos/128408/pexels-photo-128408.jpeg?auto=compress&cs=tinysrgb&w=400',
    'peluqueria-2' => 'https://images.pexels.com/photos/1570806/pexels-photo-1570806.jpeg?auto=compress&cs=tinysrgb&w=400',
    'pan' => 'https://images.pexels.com/photos/1775043/pexels-photo-1775043.jpeg?auto=compress&cs=tinysrgb&w=400',
    'brazo' => 'https://images.pexels.com/photos/1557652/pexels-photo-1557652.jpeg?auto=compress&cs=tinysrgb&w=400',
    'motos' => 'https://images.pexels.com/photos/63294/motor-vehicle-motorcycle-motor-scooter-63294.jpeg?auto=compress&cs=tinysrgb&w=400',
    'vehiculos' => 'https://images.pexels.com/photos/164634/pexels-photo-164634.jpeg?auto=compress&cs=tinysrgb&w=400',
    'alquileres' => 'https://images.pexels.com/photos/106399/pexels-photo-106399.jpeg?auto=compress&cs=tinysrgb&w=400',
    'hamburguesa' => 'https://images.pexels.com/photos/1639557/pexels-photo-1639557.jpeg?auto=compress&cs=tinysrgb&w=400',
    'accesorios-flores' => 'https://images.pexels.com/photos/1070850/pexels-photo-1070850.jpeg?auto=compress&cs=tinysrgb&w=400',
    'relojes' => 'https://images.pexels.com/photos/125779/pexels-photo-125779.jpeg?auto=compress&cs=tinysrgb&w=400',
    'kebab' => 'https://images.pexels.com/photos/2474661/pexels-photo-2474661.jpeg?auto=compress&cs=tinysrgb&w=400',
    'pizzas' => 'https://images.pexels.com/photos/315755/pexels-photo-315755.jpeg?auto=compress&cs=tinysrgb&w=400',
    'informatica' => 'https://images.pexels.com/photos/325111/pexels-photo-325111.jpeg?auto=compress&cs=tinysrgb&w=400',
);

$downloaded = 0;
$errors = 0;
$total = min(30, count($prompts));

echo "📥 Descargando top {$total} categorías de Pexels...\n\n";

for ($i = 0; $i < $total; $i++) {
    $item = $prompts[$i];
    $slug = $item['slug'];
    $name = $item['name'];
    $filename = $item['filename'];
    
    $num = $i + 1;
    echo "🔍 [{$num}/{$total}] {$name}\n";
    
    // Buscar URL de imagen
    $image_url = isset($pexels_images[$slug]) ? $pexels_images[$slug] : null;
    
    if (!$image_url) {
        echo "   ⚠️  Sin imagen predefinida para: {$slug}\n\n";
        $errors++;
        continue;
    }
    
    echo "   📥 Descargando de Pexels...\n";
    
    // Descargar imagen
    $ch = curl_init($image_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    
    $image_data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200 && $image_data) {
        $image_path = $images_dir . '/' . $filename;
        file_put_contents($image_path, $image_data);
        
        $size = filesize($image_path);
        $size_kb = round($size / 1024, 1);
        
        echo "   ✅ Guardada: {$filename} ({$size_kb} KB)\n\n";
        $downloaded++;
    } else {
        echo "   ❌ Error descargando (HTTP {$http_code})\n\n";
        $errors++;
    }
    
    // Pequeña pausa
    usleep(300000); // 0.3 segundos
}

echo "==========================================\n";
echo "✅ Descargadas: {$downloaded}\n";
echo "❌ Errores/Omitidas: {$errors}\n";
echo "📁 Directorio: {$images_dir}\n";
echo "==========================================\n\n";

if ($downloaded > 0) {
    echo "🎉 Siguiente paso:\n";
    echo "   php upload-category-images.php\n\n";
    echo "ℹ️  Todas las imágenes son de Pexels\n";
    echo "   Licencia: Pexels License - Uso comercial permitido\n";
    echo "   https://www.pexels.com/license/\n";
}

