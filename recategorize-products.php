#!/usr/bin/env php
<?php
/**
 * Script CLI para recategorización inteligente de productos
 * Uso: php recategorize-products.php [offset] [limit] [--apply]
 */

// Cargar WordPress
define('WP_USE_THEMES', false);
require_once(__DIR__ . '/wp-load.php');

// Parámetros
$offset = isset($argv[1]) ? intval($argv[1]) : 0;
$limit = isset($argv[2]) ? intval($argv[2]) : 50;
$apply = in_array('--apply', $argv);

echo "🤖 RECATEGORIZACIÓN INTELIGENTE\n";
echo "=====================================\n";
echo "Offset: {$offset}\n";
echo "Límite: {$limit}\n";
echo "Modo: " . ($apply ? "✅ PRODUCCIÓN (se aplicarán cambios)" : "⚠️  PRUEBA (solo análisis)") . "\n";
echo "\n";

// Categorías válidas
$valid_categories = array(
    746 => 'Alimentación y Restauración',
    754 => 'Bebé e Infantil',
    748 => 'Belleza y Estética',
    757 => 'Deportes y Ocio',
    758 => 'Ferretería y Bricolaje',
    756 => 'Flores y Eventos',
    749 => 'Hogar y Decoración',
    745 => 'Inmobiliaria',
    747 => 'Moda y Calzado',
    755 => 'Mascotas',
    759 => 'Otros Productos y Servicios',
    753 => 'Salud y Bienestar',
    752 => 'Servicios Profesionales',
    750 => 'Tecnología e Informática',
    751 => 'Vehículos y Motor',
);

function analyze_product($title, $description) {
    $text = strtolower($title . ' ' . $description);
    $text = remove_accents($text);
    
    $assigned = array();
    
    // MASCOTAS (755) - ANTES que otros para evitar confusiones
    if (preg_match('/\b(mascota|perro|gato|veterinario|pienso|animal|peluqueria canina|tienda de mascotas|comida para (perro|gato|mascota)s?)\b/i', $text)) {
        $assigned[] = 755;
        return array_slice(array_unique($assigned), 0, 2);
    }
    
    // MODA Y CALZADO (747) - Específico antes de genérico
    if (preg_match('/\b(zapatos?|zapatillas?|calzado|botas?|sandalias?|mocas[ií]n|mocasines|deportivas?|tacones?)\b/i', $text) ||
        preg_match('/\b(ropa|vestidos?|camisas?|pantalon(es)?|faldas?|jerseys?|abrigos?|chaquetas?|moda|boutique|tienda de ropa|banadors?)\b/i', $text)) {
        $assigned[] = 747;
        return array_slice(array_unique($assigned), 0, 2);
    }
    
    // ALIMENTACIÓN Y RESTAURACIÓN (746) - Incluir bebidas
    if (preg_match('/\b(restaurante|comida|menu|cocina|chef|catering|bar|cafeteria|tapas|desayuno|almuerzo|cena)\b/i', $text) ||
        preg_match('/\b(cerveza|vino|bebida|refresco|cafe|te|jarra|cana|corto)\b/i', $text) ||
        preg_match('/\b(mejillon|marisco|pescado|carne|verdura|fruta|pan|pasta|arroz)\b/i', $text)) {
        $assigned[] = 746;
        return array_slice(array_unique($assigned), 0, 2);
    }
    
    // INMOBILIARIA (745) - Solo si realmente es inmobiliaria
    if (preg_match('/\b(inmueble|vivienda|apartamento|propiedad|terreno|parcela)\b/i', $text) ||
        (preg_match('/\b(piso|casa|chalet|atico|duplex|estudio|local)\b/i', $text) && 
         preg_match('/\b(alquiler|venta|alquilar|vender|comprar|se alquila|se vende|en alquiler|en venta)\b/i', $text))) {
        $assigned[] = 745; // Inmobiliaria
        
        // Subcategorías específicas
        if (preg_match('/\balquiler\b/i', $text)) {
            if (preg_match('/\b(atico|aticos)\b/i', $text)) {
                $assigned[] = 763; // Alquiler - Áticos
            } elseif (preg_match('/\b(chalet|chalets)\b/i', $text)) {
                $assigned[] = 761; // Alquiler - Chalets
            } elseif (preg_match('/\b(duplex)\b/i', $text)) {
                $assigned[] = 764; // Alquiler - Dúplex
            } elseif (preg_match('/\b(estudio|estudios)\b/i', $text)) {
                $assigned[] = 762; // Alquiler - Estudios
            } elseif (preg_match('/\b(local|locales)\b/i', $text)) {
                $assigned[] = 765; // Alquiler - Locales
            } elseif (preg_match('/\b(piso|pisos)\b/i', $text)) {
                $assigned[] = 760; // Alquiler - Pisos
            }
        } elseif (preg_match('/\bventa\b/i', $text)) {
            if (preg_match('/\b(atico|aticos)\b/i', $text)) {
                $assigned[] = 769; // Venta - Áticos
            } elseif (preg_match('/\b(casa de pueblo|casas de pueblo)\b/i', $text)) {
                $assigned[] = 771; // Venta - Casas de Pueblo
            } elseif (preg_match('/\b(chalet|chalets)\b/i', $text)) {
                $assigned[] = 767; // Venta - Chalets
            } elseif (preg_match('/\b(duplex)\b/i', $text)) {
                $assigned[] = 770; // Venta - Dúplex
            } elseif (preg_match('/\b(estudio|estudios)\b/i', $text)) {
                $assigned[] = 768; // Venta - Estudios
            } elseif (preg_match('/\b(piso|pisos)\b/i', $text)) {
                $assigned[] = 766; // Venta - Pisos
            }
        }
    }
    
    // VEHÍCULOS Y MOTOR (751)
    if (preg_match('/\b(coche|carro|auto|vehiculo|moto|bicicleta|cambio de aceite|neumatico|taller|mecanico|motor|revision|itv|rueda|freno)\b/i', $text)) {
        $assigned[] = 751;
        return array_slice(array_unique($assigned), 0, 2);
    }
    
    // BELLEZA Y ESTÉTICA (748) - Solo humanos
    if (preg_match('/\b(peluqueria|estetica|belleza|masaje|spa|unas|maquillaje|tratamiento facial|depilacion|salon de belleza)\b/i', $text) &&
        !preg_match('/\b(canina|perro|gato|mascota)\b/i', $text)) {
        $assigned[] = 748;
        return array_slice(array_unique($assigned), 0, 2);
    }
    
    // HOGAR Y DECORACIÓN (749)
    if (preg_match('/\b(mueble|decoracion|sofa|mesa|silla|lampara|cortina|alfombra|hogar|interiorismo)\b/i', $text)) {
        $assigned[] = 749;
        return array_slice(array_unique($assigned), 0, 2);
    }
    
    // TECNOLOGÍA E INFORMÁTICA (750) - Incluir tarifas móvil/fibra
    if (preg_match('/\b(ordenador|portatil|movil|telefono|tablet|informatica|software|hardware|reparacion movil|pc|mac|tarifa|fibra|gb|mb)\b/i', $text)) {
        $assigned[] = 750;
        return array_slice(array_unique($assigned), 0, 2);
    }
    
    // SALUD Y BIENESTAR (753)
    if (preg_match('/\b(medico|clinica|salud|fisioterapia|nutricion|farmacia|dentista|optica|psicologo|terapeuta)\b/i', $text)) {
        $assigned[] = 753;
        return array_slice(array_unique($assigned), 0, 2);
    }
    
    // SERVICIOS PROFESIONALES (752)
    if (preg_match('/\b(abogado|asesor|consultor|contable|gestor|notario|arquitecto|ingeniero|administrador de fincas|diseno web|marketing)\b/i', $text)) {
        $assigned[] = 752;
        return array_slice(array_unique($assigned), 0, 2);
    }
    
    // DEPORTES Y OCIO (757)
    if (preg_match('/\b(deporte|gimnasio|fitness|yoga|paddle|futbol|baloncesto|natacion|ocio|entrenador)\b/i', $text)) {
        $assigned[] = 757;
        return array_slice(array_unique($assigned), 0, 2);
    }
    
    // BEBÉ E INFANTIL (754)
    if (preg_match('/\b(bebe|nino|infantil|cuna|carrito|panal|juguete|guarderia)\b/i', $text)) {
        $assigned[] = 754;
        return array_slice(array_unique($assigned), 0, 2);
    }
    
    // FERRETERÍA Y BRICOLAJE (758)
    if (preg_match('/\b(ferreteria|herramienta|bricolaje|pintura|tornillo|taladro|martillo|fontaneria|electricidad)\b/i', $text)) {
        $assigned[] = 758;
        return array_slice(array_unique($assigned), 0, 2);
    }
    
    // FLORES Y EVENTOS (756)
    if (preg_match('/\b(flores|floristeria|ramo|boda|evento|celebracion|decoracion floral)\b/i', $text)) {
        $assigned[] = 756;
        return array_slice(array_unique($assigned), 0, 2);
    }
    
    // Si no se asignó nada, usar "Otros"
    if (empty($assigned)) {
        $assigned[] = 759; // Otros Productos y Servicios
    }
    
    // Limitar a máximo 2 categorías
    return array_slice(array_unique($assigned), 0, 2);
}

// Obtener productos
$args = array(
    'post_type' => 'product',
    'posts_per_page' => $limit,
    'offset' => $offset,
    'post_status' => 'publish',
    'orderby' => 'ID',
    'order' => 'ASC',
);

$products = get_posts($args);

if (empty($products)) {
    echo "✅ No hay más productos para procesar\n";
    echo "🎉 PROCESO COMPLETADO\n";
    exit(0);
}

echo "📦 Procesando productos " . ($offset + 1) . " a " . ($offset + count($products)) . "\n\n";

$changed = 0;
$no_change = 0;
$errors = 0;

foreach ($products as $product) {
    $title = $product->post_title;
    $description = $product->post_excerpt;
    
    // Obtener categorías actuales
    $current_cats = wp_get_post_terms($product->ID, 'product_cat', array('fields' => 'ids'));
    
    // Analizar
    $suggested_cats = analyze_product($title, $description);
    
    // Comparar
    sort($current_cats);
    sort($suggested_cats);
    
    if ($current_cats === $suggested_cats) {
        $no_change++;
        continue;
    }
    
    $changed++;
    
    // Obtener nombres
    $current_names = array();
    foreach ($current_cats as $cat_id) {
        $term = get_term($cat_id, 'product_cat');
        if ($term && !is_wp_error($term)) {
            $current_names[] = $term->name;
        }
    }
    
    $suggested_names = array();
    foreach ($suggested_cats as $cat_id) {
        $term = get_term($cat_id, 'product_cat');
        if ($term && !is_wp_error($term)) {
            $suggested_names[] = $term->name;
        }
    }
    
    // Truncar título si es muy largo
    $display_title = strlen($title) > 60 ? substr($title, 0, 60) . '...' : $title;
    
    echo "🔄 #{$product->ID}: {$display_title}\n";
    echo "   Antes: " . implode(', ', $current_names) . " (" . count($current_cats) . " cats)\n";
    echo "   Después: " . implode(', ', $suggested_names) . " (" . count($suggested_cats) . " cats)\n";
    
    if ($apply) {
        $result = wp_set_post_terms($product->ID, $suggested_cats, 'product_cat');
        if (is_wp_error($result)) {
            echo "   ❌ ERROR: " . $result->get_error_message() . "\n";
            $errors++;
        } else {
            echo "   ✅ APLICADO\n";
        }
    } else {
        echo "   ⚠️  MODO PRUEBA - No aplicado\n";
    }
    
    echo "\n";
}

echo "=====================================\n";
echo "📊 RESUMEN DEL LOTE:\n";
echo "   Procesados: " . count($products) . "\n";
echo "   Modificados: {$changed}\n";
echo "   Sin cambios: {$no_change}\n";
if ($errors > 0) {
    echo "   Errores: {$errors}\n";
}
echo "\n";

$next_offset = $offset + $limit;
$remaining = 2854 - $next_offset;

if ($remaining > 0) {
    echo "💡 Para continuar con el siguiente lote:\n";
    echo "   php recategorize-products.php {$next_offset} {$limit}" . ($apply ? " --apply" : "") . "\n";
    echo "\n";
    echo "📊 Progreso: " . round((($offset + count($products)) / 2854) * 100, 1) . "% completado\n";
    echo "   Quedan aproximadamente {$remaining} productos\n";
} else {
    echo "🎉 TODOS LOS PRODUCTOS HAN SIDO PROCESADOS\n";
}

