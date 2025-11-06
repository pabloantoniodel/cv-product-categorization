<?php
/**
 * Generar prompts para IA basados en categorías sin imagen
 * Crea un archivo JSON con los prompts para generar imágenes
 */

// Cargar WordPress
require_once(__DIR__ . '/../../../wp-load.php');

global $wpdb;

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
    ORDER BY tt.count DESC
");

echo "==========================================\n";
echo "  GENERADOR DE PROMPTS PARA IA\n";
echo "==========================================\n\n";

echo "📊 Encontradas " . count($categories) . " categorías sin imagen\n\n";

$prompts = array();

// Mapeo de categorías a prompts descriptivos
$prompt_templates = array(
    'peluqueria' => 'Professional hair salon interior, modern styling chairs, mirrors, hair products, bright and clean, photorealistic',
    'moda' => 'Fashion boutique interior, clothing racks with trendy clothes, mannequins, modern retail store, photorealistic',
    'telefonia' => 'Modern mobile phone store, smartphones display, latest technology devices, clean retail environment, photorealistic',
    'alcohol' => 'Premium liquor store shelf, bottles of wine and spirits, elegant display, warm lighting, photorealistic',
    'mujer' => 'Women fashion store, elegant dresses and accessories, modern boutique interior, photorealistic',
    'pasteleria' => 'Bakery display with delicious pastries, cakes, desserts, warm inviting atmosphere, photorealistic',
    'recordatorios' => 'Gift shop with souvenirs, keepsakes, decorative items, colorful display, photorealistic',
    'carne' => 'Butcher shop display, fresh meat cuts, professional meat counter, clean environment, photorealistic',
    'tailandeses' => 'Thai massage spa interior, relaxing atmosphere, massage beds, zen decoration, photorealistic',
    'zapatos' => 'Shoe store interior, shelves with various footwear, modern retail display, photorealistic',
    'frutas' => 'Fresh fruit market display, colorful fruits arranged beautifully, vibrant and fresh, photorealistic',
    'deportivas' => 'Sports shoe store, athletic footwear display, modern retail interior, photorealistic',
    'bocadillos' => 'Sandwich shop interior, fresh sandwiches display, appetizing food presentation, photorealistic',
    'hombre' => 'Men fashion store, suits and casual wear, modern masculine boutique, photorealistic',
    'desayunos' => 'Breakfast cafe, fresh breakfast items, coffee and pastries, cozy morning atmosphere, photorealistic',
    'fotografias' => 'Photography studio, professional camera equipment, modern photo studio setup, photorealistic',
    'flores-naturales' => 'Flower shop interior, beautiful fresh flowers arrangements, colorful blooms, photorealistic',
    'pescado' => 'Fish market display, fresh seafood on ice, professional fishmonger counter, photorealistic',
    'pan' => 'Artisan bread bakery, fresh baked bread display, rustic and warm atmosphere, photorealistic',
    'brazo' => 'Tattoo studio interior, tattoo artist working, modern clean studio, artistic atmosphere, photorealistic',
    'motos' => 'Motorcycle showroom, modern bikes display, professional motorcycle dealership, photorealistic',
    'vehiculos' => 'Car dealership showroom, new vehicles display, modern automotive retail, photorealistic',
    'alquileres' => 'Real estate office interior, property listings, professional real estate agency, photorealistic',
    'hamburguesa' => 'Burger restaurant, gourmet hamburgers, appetizing food presentation, modern fast food, photorealistic',
    'accesorios-flores' => 'Florist accessories shop, vases, flower pots, gardening items, boutique display, photorealistic',
    'relojes' => 'Watch store display, luxury timepieces, elegant jewelry store interior, photorealistic',
    'kebab' => 'Kebab restaurant, döner rotating spit, Mediterranean fast food, inviting atmosphere, photorealistic',
);

foreach ($categories as $category) {
    // Buscar prompt personalizado
    $prompt = isset($prompt_templates[$category->slug]) 
        ? $prompt_templates[$category->slug]
        : "Professional {$category->name} store or business interior, modern and clean, commercial photography style, photorealistic, 4k quality";
    
    $prompts[] = array(
        'term_id' => $category->term_id,
        'name' => $category->name,
        'slug' => $category->slug,
        'count' => $category->count,
        'prompt' => $prompt,
        'filename' => sanitize_file_name($category->slug) . '.jpg'
    );
    
    echo "📝 {$category->name} ({$category->count} productos)\n";
    echo "   Prompt: {$prompt}\n\n";
}

// Guardar JSON
$json_file = __DIR__ . '/category-image-prompts.json';
file_put_contents($json_file, json_encode($prompts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "==========================================\n";
echo "✅ Prompts generados: " . count($prompts) . "\n";
echo "📄 Archivo guardado: category-image-prompts.json\n";
echo "==========================================\n\n";

echo "🎨 SIGUIENTE PASO:\n";
echo "1. Usar estos prompts en una IA de generación de imágenes\n";
echo "   - DALL-E (OpenAI): https://platform.openai.com/\n";
echo "   - Stable Diffusion: https://stability.ai/\n";
echo "   - Midjourney: https://www.midjourney.com/\n";
echo "   - Leonardo.AI: https://leonardo.ai/ (GRATIS)\n\n";
echo "2. Guardar las imágenes generadas en: wp-content/uploads/category-images/\n";
echo "3. Ejecutar: php upload-category-images.php\n\n";

