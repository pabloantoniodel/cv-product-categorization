<?php
/**
 * Descargar imágenes de Pixabay (libre de derechos)
 * API Key gratuita: https://pixabay.com/api/docs/
 */

// Cargar WordPress
require_once(__DIR__ . '/../../../wp-load.php');

// Crear directorio para imágenes
$images_dir = WP_CONTENT_DIR . '/uploads/category-images';
if (!file_exists($images_dir)) {
    mkdir($images_dir, 0755, true);
}

echo "==========================================\n";
echo "  DESCARGA DE PIXABAY (Libre derechos)\n";
echo "==========================================\n\n";

// API Key de Pixabay (gratuita, 100 búsquedas/minuto)
// Registrarse en: https://pixabay.com/api/docs/
$API_KEY = '48382876-d0e04c95ff88dec97526fc3c3'; // Key pública de ejemplo

// Leer prompts
$json_file = __DIR__ . '/category-image-prompts.json';
if (!file_exists($json_file)) {
    echo "❌ Error: Ejecuta primero generate-ai-prompts.php\n";
    exit(1);
}

$prompts = json_decode(file_get_contents($json_file), true);

// Mapeo de términos de búsqueda para Pixabay
$search_terms = array(
    'peluqueria' => 'hair salon',
    'peluqueria-2' => 'barber shop',
    'moda' => 'fashion store',
    'telefonia' => 'mobile phone store',
    'alcohol' => 'wine store',
    'mujer' => 'women fashion',
    'pasteleria' => 'bakery',
    'recordatorios' => 'gift shop',
    'carne' => 'butcher shop',
    'tailandeses' => 'thai massage',
    'zapatos' => 'shoe store',
    'frutas' => 'fresh fruit',
    'deportivas' => 'sports shoes',
    'bocadillos' => 'sandwich',
    'hombre' => 'men fashion',
    'desayunos' => 'breakfast',
    'fotografias' => 'photography studio',
    'flores-naturales' => 'flower shop',
    'pescado' => 'fish market',
    'pan' => 'bread',
    'brazo' => 'tattoo',
    'motos' => 'motorcycle',
    'vehiculos' => 'car',
    'alquileres' => 'real estate',
    'hamburguesa' => 'burger',
    'accesorios-flores' => 'flowers vase',
    'relojes' => 'watches',
    'kebab' => 'kebab',
);

$downloaded = 0;
$errors = 0;
$total = min(30, count($prompts)); // Solo top 30 para no saturar

echo "📥 Descargando top {$total} categorías...\n\n";

for ($i = 0; $i < $total; $i++) {
    $item = $prompts[$i];
    $slug = $item['slug'];
    $name = $item['name'];
    $filename = $item['filename'];
    
    // Buscar término de búsqueda
    $search_term = isset($search_terms[$slug]) ? $search_terms[$slug] : $name;
    
    $num = $i + 1;
    echo "🔍 [{$num}/{$total}] {$name}\n";
    echo "   Buscando: {$search_term}\n";
    
    // Construir URL de Pixabay API
    $api_url = "https://pixabay.com/api/?key={$API_KEY}&q=" . urlencode($search_term) . 
               "&image_type=photo&min_width=300&min_height=300&per_page=3";
    
    // Hacer petición a Pixabay
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200 && $response) {
        $data = json_decode($response, true);
        
        if (isset($data['hits']) && count($data['hits']) > 0) {
            // Tomar la primera imagen (mejor resultado)
            $image = $data['hits'][0];
            $image_url = $image['webformatURL']; // 640x360 o similar
            
            // Descargar imagen
            $ch = curl_init($image_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $image_data = curl_exec($ch);
            curl_close($ch);
            
            if ($image_data) {
                $image_path = $images_dir . '/' . $filename;
                file_put_contents($image_path, $image_data);
                
                echo "   ✅ Descargada: {$filename}\n";
                echo "   📊 {$image['imageWidth']}x{$image['imageHeight']} px\n";
                echo "   👤 Por: {$image['user']}\n\n";
                
                $downloaded++;
            } else {
                echo "   ❌ Error descargando imagen\n\n";
                $errors++;
            }
        } else {
            echo "   ⚠️  Sin resultados\n\n";
            $errors++;
        }
    } else {
        echo "   ❌ Error API (HTTP {$http_code})\n\n";
        $errors++;
    }
    
    // Pausa para no saturar API (100 req/min = 1 cada 0.6s)
    usleep(700000); // 0.7 segundos
}

echo "==========================================\n";
echo "✅ Descargadas: {$downloaded}\n";
echo "❌ Errores: {$errors}\n";
echo "📁 Directorio: {$images_dir}\n";
echo "==========================================\n\n";

if ($downloaded > 0) {
    echo "🎉 Siguiente paso:\n";
    echo "   php upload-category-images.php\n";
    echo "\n";
    echo "ℹ️  Todas las imágenes son de Pixabay (CC0 - Dominio Público)\n";
    echo "   Uso comercial y modificación permitidos\n";
}

