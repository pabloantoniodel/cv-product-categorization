<?php
/**
 * Script para generar imágenes de categorías
 * Ejecutar: php generate-category-images.php
 */

// Cargar WordPress
require_once(__DIR__ . '/../../../wp-load.php');

// Cargar clase generadora
require_once(__DIR__ . '/includes/class-cv-category-image-generator.php');

echo "==========================================\n";
echo "  GENERADOR DE IMÁGENES DE CATEGORÍAS\n";
echo "==========================================\n\n";

// Verificar que GD está instalado
if (!extension_loaded('gd')) {
    echo "❌ ERROR: La extensión GD no está instalada.\n";
    echo "   Instalar con: sudo apt-get install php-gd\n";
    exit(1);
}

echo "✅ Extensión GD detectada\n\n";

// Crear instancia del generador
$generator = new CV_Category_Image_Generator();

echo "🔍 Buscando categorías sin imagen...\n\n";

// Procesar categorías
$results = $generator->process_categories_without_images();

echo "\n==========================================\n";
echo "  RESULTADOS\n";
echo "==========================================\n\n";

$success_count = 0;
$error_count = 0;

foreach ($results as $result) {
    if ($result['status'] === 'success') {
        echo "✅ {$result['name']} (ID: {$result['term_id']}) - Attachment: {$result['attachment_id']}\n";
        $success_count++;
    } else {
        echo "❌ {$result['name']} (ID: {$result['term_id']}) - Error: {$result['status']}\n";
        $error_count++;
    }
}

echo "\n==========================================\n";
echo "✅ Éxitos: $success_count\n";
echo "❌ Errores: $error_count\n";
echo "📊 Total procesadas: " . count($results) . "\n";
echo "==========================================\n\n";

echo "🎉 ¡Proceso completado!\n";

