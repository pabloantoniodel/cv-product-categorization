#!/usr/bin/env php
<?php
/**
 * Recategorización v4 - Inmobiliaria simplificada
 * Solo 3 subcategorías: Alquiler, Venta, Traspaso
 * Hasta 3 categorías por producto
 */

define('WP_USE_THEMES', false);
require_once(__DIR__ . '/wp-load.php');

$offset = isset($argv[1]) ? intval($argv[1]) : 0;
$limit = isset($argv[2]) ? intval($argv[2]) : 100;
$apply = in_array('--apply', $argv);

echo "🤖 RECATEGORIZACIÓN v4 - Inmobiliaria Simplificada\n";
echo "=====================================\n";
echo "Offset: {$offset}\n";
echo "Límite: {$limit}\n";
echo "Modo: " . ($apply ? "✅ PRODUCCIÓN" : "⚠️  PRUEBA") . "\n\n";

// IDs de subcategorías de Inmobiliaria (a crear si no existen)
$INMOBILIARIA_ALQUILER = null;
$INMOBILIARIA_VENTA = null;
$INMOBILIARIA_TRASPASO = null;

// Buscar o crear las 3 subcategorías
function get_or_create_inmo_subcategory($name) {
    $term = get_term_by('name', $name, 'product_cat');
    
    if ($term && $term->parent == 745) {
        return $term->term_id;
    }
    
    // Crear nueva
    $result = wp_insert_term($name, 'product_cat', array('parent' => 745));
    
    if (is_wp_error($result)) {
        return null;
    }
    
    echo "✨ Subcategoría creada: {$name} (ID: {$result['term_id']})\n";
    return $result['term_id'];
}

$INMOBILIARIA_ALQUILER = get_or_create_inmo_subcategory('Alquiler');
$INMOBILIARIA_VENTA = get_or_create_inmo_subcategory('Venta');
$INMOBILIARIA_TRASPASO = get_or_create_inmo_subcategory('Traspaso');

echo "\n";

// Función para quitar acentos
function remove_accents($string) {
    $unwanted = array(
        'á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A', 'ä' => 'a', 'Ä' => 'A',
        'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E', 'ë' => 'e', 'Ë' => 'E',
        'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I', 'ï' => 'i', 'Ï' => 'I',
        'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O', 'ö' => 'o', 'Ö' => 'O',
        'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U', 'ü' => 'u', 'Ü' => 'U',
        'ñ' => 'n', 'Ñ' => 'N', 'ç' => 'c', 'Ç' => 'C',
    );
    return strtr($string, $unwanted);
}

function analyze_product_v4($title, $description) {
    global $INMOBILIARIA_ALQUILER, $INMOBILIARIA_VENTA, $INMOBILIARIA_TRASPASO;
    
    $text = strtolower($title . ' ' . $description);
    $text = remove_accents($text);
    
    $assigned = array();
    
    // INMOBILIARIA (745) - Simplificado
    if (preg_match('/\b(inmueble|vivienda|apartamento|propiedad|terreno|parcela)\b/i', $text) ||
        (preg_match('/\b(piso|casa|chalet|atico|duplex|estudio|local|garaje|plaza de garaje)\b/i', $text) && 
         preg_match('/\b(alquiler|venta|alquilar|vender|comprar|se alquila|se vende|en alquiler|en venta|traspaso)\b/i', $text))) {
        
        $assigned[] = 745; // Inmobiliaria SIEMPRE
        
        // Determinar si es Traspaso (prioridad)
        if (preg_match('/\btraspaso\b/i', $text)) {
            $assigned[] = $INMOBILIARIA_TRASPASO;
        }
        
        // Determinar si es Alquiler
        if (preg_match('/\b(alquiler|alquilar|se alquila|en alquiler)\b/i', $text)) {
            $assigned[] = $INMOBILIARIA_ALQUILER;
        }
        
        // Determinar si es Venta
        if (preg_match('/\b(venta|vender|se vende|en venta)\b/i', $text)) {
            $assigned[] = $INMOBILIARIA_VENTA;
        }
        
        return array_slice(array_unique($assigned), 0, 3);
    }
    
    // MASCOTAS (755)
    if (preg_match('/\b(mascota|perro|gato|veterinario|pienso|animal|peluqueria canina)\b/i', $text) ||
        preg_match('/\b(comida para (perro|gato|mascota)s?|chuches para mascota|cama.*mascota|lata.*gato|alimento.*perro)\b/i', $text)) {
        
        $assigned[] = 755; // Mascotas
        
        if (preg_match('/\b(comida|pienso|lata|alimento)\b/i', $text)) {
            $assigned[] = 811; // Alimentación Mascotas
        }
        if (preg_match('/\b(cama|collar|correa|juguete|accesorio)\b/i', $text)) {
            $assigned[] = 812; // Accesorios Mascotas
        }
        
        return array_slice(array_unique($assigned), 0, 3);
    }
    
    // ALIMENTACIÓN Y RESTAURACIÓN (746)
    if (preg_match('/\b(restaurante|comida|menu|cocina|chef|catering|bar|cafeteria|tapas|desayuno|almuerzo|cena)\b/i', $text) ||
        preg_match('/\b(cerveza|vino|bebida|refresco|cafe|te|jarra|cana|corto)\b/i', $text) ||
        preg_match('/\b(mejillon|marisco|pescado|carne|verdura|fruta|pan|pasta|arroz|tomate|calabaza|platano|melocoton)\b/i', $text) ||
        preg_match('/\b(chuches|chupa chup|chicle|caramelo|dulce|golosina|tarta)\b/i', $text)) {
        
        $assigned[] = 746; // Alimentación y Restauración
        
        if (preg_match('/\b(restaurante|bar|cafeteria|tapas)\b/i', $text)) {
            $assigned[] = 772; // Restaurantes y Bares
        }
        if (preg_match('/\b(cerveza|vino|bebida|refresco|cana|corto|jarra)\b/i', $text)) {
            $assigned[] = 774; // Bebidas
        }
        if (preg_match('/\b(fruta|verdura|tomate|calabaza|platano|melocoton|mejillon|marisco|pescado)\b/i', $text)) {
            $assigned[] = 775; // Productos Frescos
        }
        if (preg_match('/\b(chuches|dulce|caramelo|golosina|tarta.*chuches)\b/i', $text)) {
            $assigned[] = 831; // Dulces y Golosinas
        }
        
        return array_slice(array_unique($assigned), 0, 3);
    }
    
    // BELLEZA Y ESTÉTICA (748)
    if (preg_match('/\b(peluqueria|estetica|belleza|masaje|spa|unas|maquillaje|tratamiento facial|depilacion|salon de belleza)\b/i', $text) ||
        preg_match('/\b(corte de pelo|barba|limpieza facial|alisado|tinte|manicura|pedicura)\b/i', $text)) {
        
        $assigned[] = 748; // Belleza y Estética
        
        if (preg_match('/\b(unas|manicura|pedicura)\b/i', $text)) {
            $assigned[] = 786; // Manicura y Pedicura
        }
        if (preg_match('/\b(limpieza facial|tratamiento|mascarilla|estetica facial)\b/i', $text)) {
            $assigned[] = 784; // Estética Facial
        }
        if (preg_match('/\b(masaje|spa)\b/i', $text)) {
            $assigned[] = 839; // Masajes y Spa
        }
        
        return array_slice(array_unique($assigned), 0, 3);
    }
    
    // MODA Y CALZADO (747)
    if (preg_match('/\b(zapatos?|zapatillas?|calzado|botas?|sandalias?|mocas[ií]n|mocasines|deportivas?|tacones?|ballenero)\b/i', $text) ||
        preg_match('/\b(ropa|vestidos?|camisas?|camisetas?|pantalon(es)?|faldas?|jerseys?|abrigos?|chaquetas?|sudadera|polo)\b/i', $text) ||
        preg_match('/\b(moda|boutique|tienda de ropa|banadors?|sujetador|bragas|lenceria|patucos)\b/i', $text) ||
        preg_match('/\b(reloj|collar|pulsera|anillo|joya|bisuteria)\b/i', $text)) {
        
        $assigned[] = 747; // Moda y Calzado
        
        if (preg_match('/\b(camiseta|camisa|pantalon|vestido|falda|jersey|abrigo|chaqueta|sudadera|polo|banador)\b/i', $text)) {
            if (preg_match('/\b(mujer|señora|femenin)\b/i', $text)) {
                $assigned[] = 777; // Ropa Mujer
            } elseif (preg_match('/\b(hombre|caballero|masculin)\b/i', $text)) {
                $assigned[] = 778; // Ropa Hombre
            } elseif (preg_match('/\b(niño|niña|infantil|bebe)\b/i', $text)) {
                $assigned[] = 781; // Ropa Infantil
            } else {
                $assigned[] = 834; // Ropa (genérico)
            }
        }
        
        if (preg_match('/\b(sujetador|bragas|lenceria)\b/i', $text)) {
            $assigned[] = 837; // Lencería
        }
        
        if (preg_match('/\b(reloj|collar|pulsera|anillo|joya)\b/i', $text)) {
            $assigned[] = 836; // Joyería y Relojes
        }
        
        return array_slice(array_unique($assigned), 0, 3);
    }
    
    // DEPORTES Y OCIO (757)
    if (preg_match('/\b(deporte|gimnasio|fitness|yoga|paddle|futbol|baloncesto|natacion|ocio|entrenador|balon)\b/i', $text)) {
        $assigned[] = 757; // Deportes y Ocio
        $assigned[] = 833; // Equipamiento Deportivo
        return array_slice(array_unique($assigned), 0, 3);
    }
    
    // FLORES Y EVENTOS (756)
    if (preg_match('/\b(flores|floristeria|ramo|boda|evento|celebracion|decoracion floral|orquidea|rosa|letra preservada)\b/i', $text)) {
        $assigned[] = 756; // Flores y Eventos
        
        if (preg_match('/\b(orquidea|rosa|ramo)\b/i', $text)) {
            $assigned[] = 814; // Flores Naturales
        }
        if (preg_match('/\b(preservada)\b/i', $text)) {
            $assigned[] = 835; // Flores Preservadas
        }
        
        return array_slice(array_unique($assigned), 0, 3);
    }
    
    // VEHÍCULOS Y MOTOR (751)
    if (preg_match('/\b(coche|carro|auto|vehiculo|moto|bicicleta|cambio de aceite|neumatico|taller|mecanico|motor|revision|itv|rueda|freno|palanca)\b/i', $text)) {
        $assigned[] = 751; // Vehículos y Motor
        $assigned[] = 838; // Mantenimiento y Reparación
        return array_slice(array_unique($assigned), 0, 3);
    }
    
    // TECNOLOGÍA E INFORMÁTICA (750)
    if (preg_match('/\b(ordenador|portatil|movil|telefono|tablet|informatica|software|hardware|reparacion movil|pc|mac|tarifa|fibra|gb|mb|web|android)\b/i', $text)) {
        $assigned[] = 750; // Tecnología e Informática
        
        if (preg_match('/\b(tarifa|fibra|gb|mb|llamadas|simetrico)\b/i', $text)) {
            $assigned[] = 793; // Tarifas y Telecomunicaciones
        }
        
        return array_slice(array_unique($assigned), 0, 3);
    }
    
    // HOGAR Y DECORACIÓN (749)
    if (preg_match('/\b(mueble|decoracion|sofa|mesa|silla|lampara|cortina|alfombra|hogar|interiorismo)\b/i', $text)) {
        $assigned[] = 749; // Hogar y Decoración
        $assigned[] = 788; // Muebles
        return array_slice(array_unique($assigned), 0, 3);
    }
    
    // SALUD Y BIENESTAR (753)
    if (preg_match('/\b(medico|clinica|salud|fisioterapia|nutricion|farmacia|dentista|optica|psicologo|terapeuta)\b/i', $text)) {
        $assigned[] = 753; // Salud y Bienestar
        $assigned[] = 840; // Servicios Médicos
        return array_slice(array_unique($assigned), 0, 3);
    }
    
    // SERVICIOS PROFESIONALES (752)
    if (preg_match('/\b(abogado|asesor|consultor|contable|gestor|notario|arquitecto|ingeniero|administrador de fincas|proteccion solar|sistema)\b/i', $text)) {
        $assigned[] = 752; // Servicios Profesionales
        $assigned[] = 802; // Servicios del Hogar
        return array_slice(array_unique($assigned), 0, 3);
    }
    
    // BEBÉ E INFANTIL (754)
    if (preg_match('/\b(bebe|nino|infantil|cuna|carrito|panal|juguete|guarderia)\b/i', $text)) {
        $assigned[] = 754; // Bebé e Infantil
        $assigned[] = 832; // Productos Bebé
        return array_slice(array_unique($assigned), 0, 3);
    }
    
    // FERRETERÍA Y BRICOLAJE (758)
    if (preg_match('/\b(ferreteria|herramienta|bricolaje|pintura|tornillo|taladro|martillo|fontaneria|electricidad)\b/i', $text)) {
        $assigned[] = 758; // Ferretería y Bricolaje
        $assigned[] = 822; // Herramientas
        return array_slice(array_unique($assigned), 0, 3);
    }
    
    // Si no se encontró nada específico
    $assigned[] = 759; // Otros Productos y Servicios
    $assigned[] = 828; // Varios
    
    return array_slice(array_unique($assigned), 0, 3);
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
    echo "✅ No hay más productos\n";
    exit(0);
}

echo "📦 Procesando productos " . ($offset + 1) . " a " . ($offset + count($products)) . "\n\n";

$changed = 0;
$no_change = 0;

foreach ($products as $product) {
    $title = $product->post_title;
    $description = $product->post_excerpt;
    
    // Analizar
    $categories_to_assign = analyze_product_v4($title, $description);
    
    if (empty($categories_to_assign)) {
        continue;
    }
    
    // Obtener categorías actuales
    $current_cats = wp_get_post_terms($product->ID, 'product_cat', array('fields' => 'ids'));
    
    sort($current_cats);
    sort($categories_to_assign);
    
    if ($current_cats === $categories_to_assign) {
        $no_change++;
        continue;
    }
    
    $changed++;
    
    // Mostrar cambio
    $current_names = array();
    foreach ($current_cats as $cat_id) {
        $term = get_term($cat_id, 'product_cat');
        if ($term && !is_wp_error($term)) {
            $current_names[] = $term->name;
        }
    }
    
    $new_names = array();
    foreach ($categories_to_assign as $cat_id) {
        $term = get_term($cat_id, 'product_cat');
        if ($term && !is_wp_error($term)) {
            $new_names[] = $term->name;
        }
    }
    
    $display_title = strlen($title) > 50 ? substr($title, 0, 50) . '...' : $title;
    
    echo "🔄 #{$product->ID}: {$display_title}\n";
    echo "   Antes: " . implode(', ', $current_names) . " (" . count($current_cats) . ")\n";
    echo "   Después: " . implode(', ', $new_names) . " (" . count($categories_to_assign) . ")\n";
    
    if ($apply) {
        wp_set_post_terms($product->ID, $categories_to_assign, 'product_cat');
        echo "   ✅ APLICADO\n";
    } else {
        echo "   ⚠️  PRUEBA\n";
    }
    
    echo "\n";
}

echo "=====================================\n";
echo "📊 RESUMEN:\n";
echo "   Procesados: " . count($products) . "\n";
echo "   Modificados: {$changed}\n";
echo "   Sin cambios: {$no_change}\n";

$next_offset = $offset + $limit;
if ($next_offset < 2854) {
    echo "\n💡 Siguiente lote:\n";
    echo "   php recategorize-inmobiliaria-v4.php {$next_offset} {$limit}" . ($apply ? " --apply" : "") . "\n";
    echo "\n📊 Progreso: " . round((($offset + count($products)) / 2854) * 100, 1) . "%\n";
}
