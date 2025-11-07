#!/usr/bin/env php
<?php
/**
 * Recategorización INTELIGENTE con IA
 * Analiza título + descripción y asigna categoría + subcategoría
 * Crea subcategorías nuevas si es necesario
 */

define('WP_USE_THEMES', false);
require_once(__DIR__ . '/wp-load.php');

$offset = isset($argv[1]) ? intval($argv[1]) : 0;
$limit = isset($argv[2]) ? intval($argv[2]) : 50;
$apply = in_array('--apply', $argv);

echo "🤖 RECATEGORIZACIÓN INTELIGENTE CON IA\n";
echo "=====================================\n";
echo "Offset: {$offset}\n";
echo "Límite: {$limit}\n";
echo "Modo: " . ($apply ? "✅ PRODUCCIÓN" : "⚠️  PRUEBA") . "\n\n";

// Categorías principales
$main_categories = array(
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
    753 => 'Salud y Bienestar',
    752 => 'Servicios Profesionales',
    750 => 'Tecnología e Informática',
    751 => 'Vehículos y Motor',
);

function get_or_create_subcategory($parent_id, $subcategory_name) {
    // Buscar si existe
    $existing = get_term_by('name', $subcategory_name, 'product_cat');
    
    if ($existing && $existing->parent == $parent_id) {
        return $existing->term_id;
    }
    
    // Crear nueva subcategoría
    $result = wp_insert_term($subcategory_name, 'product_cat', array(
        'parent' => $parent_id,
    ));
    
    if (is_wp_error($result)) {
        return null;
    }
    
    echo "   ✨ Nueva subcategoría creada: {$subcategory_name}\n";
    return $result['term_id'];
}

function analyze_product_intelligent($title, $description) {
    $text = strtolower($title . ' ' . $description);
    $text = remove_accents($text);
    
    $result = array(
        'main' => null,
        'sub' => null,
        'sub_name' => null,
    );
    
    // INMOBILIARIA (745)
    if (preg_match('/\b(inmueble|vivienda|apartamento|propiedad|terreno|parcela)\b/i', $text) ||
        (preg_match('/\b(piso|casa|chalet|atico|duplex|estudio|local)\b/i', $text) && 
         preg_match('/\b(alquiler|venta|alquilar|vender|comprar|se alquila|se vende|en alquiler|en venta)\b/i', $text))) {
        
        $result['main'] = 745;
        
        // Determinar subcategoría
        if (preg_match('/\balquiler\b/i', $text)) {
            if (preg_match('/\b(atico|aticos)\b/i', $text)) {
                $result['sub'] = 763;
                $result['sub_name'] = 'Alquiler - Áticos';
            } elseif (preg_match('/\b(chalet|chalets)\b/i', $text)) {
                $result['sub'] = 761;
                $result['sub_name'] = 'Alquiler - Chalets';
            } elseif (preg_match('/\b(duplex)\b/i', $text)) {
                $result['sub'] = 764;
                $result['sub_name'] = 'Alquiler - Dúplex';
            } elseif (preg_match('/\b(estudio|estudios)\b/i', $text)) {
                $result['sub'] = 762;
                $result['sub_name'] = 'Alquiler - Estudios';
            } elseif (preg_match('/\b(local|locales)\b/i', $text)) {
                $result['sub'] = 765;
                $result['sub_name'] = 'Alquiler - Locales';
            } elseif (preg_match('/\b(piso|pisos)\b/i', $text)) {
                $result['sub'] = 760;
                $result['sub_name'] = 'Alquiler - Pisos';
            }
        } elseif (preg_match('/\bventa\b/i', $text)) {
            if (preg_match('/\b(atico|aticos)\b/i', $text)) {
                $result['sub'] = 769;
                $result['sub_name'] = 'Venta - Áticos';
            } elseif (preg_match('/\b(casa de pueblo|casas de pueblo)\b/i', $text)) {
                $result['sub'] = 771;
                $result['sub_name'] = 'Venta - Casas de Pueblo';
            } elseif (preg_match('/\b(chalet|chalets)\b/i', $text)) {
                $result['sub'] = 767;
                $result['sub_name'] = 'Venta - Chalets';
            } elseif (preg_match('/\b(duplex)\b/i', $text)) {
                $result['sub'] = 770;
                $result['sub_name'] = 'Venta - Dúplex';
            } elseif (preg_match('/\b(estudio|estudios)\b/i', $text)) {
                $result['sub'] = 768;
                $result['sub_name'] = 'Venta - Estudios';
            } elseif (preg_match('/\b(piso|pisos)\b/i', $text)) {
                $result['sub'] = 766;
                $result['sub_name'] = 'Venta - Pisos';
            }
        }
        
        return $result;
    }
    
    // MASCOTAS (755) - ANTES que Alimentación para evitar confusiones
    if (preg_match('/\b(mascota|perro|gato|veterinario|pienso|animal|peluqueria canina|tienda de mascotas)\b/i', $text) ||
        preg_match('/\b(comida para (perro|gato|mascota)s?|chuches para mascota|cama.*mascota|lata.*gato|alimento.*perro)\b/i', $text)) {
        
        $result['main'] = 755;
        
        if (preg_match('/\b(comida|pienso|lata|alimento)\b/i', $text)) {
            $result['sub_name'] = 'Alimentación Mascotas';
        } else {
            $result['sub_name'] = 'Accesorios Mascotas';
        }
        
        return $result;
    }
    
    // ALIMENTACIÓN Y RESTAURACIÓN (746)
    if (preg_match('/\b(restaurante|comida|menu|cocina|chef|catering|bar|cafeteria|tapas|desayuno|almuerzo|cena)\b/i', $text) ||
        preg_match('/\b(cerveza|vino|bebida|refresco|cafe|te|jarra|cana|corto)\b/i', $text) ||
        preg_match('/\b(mejillon|marisco|pescado|carne|verdura|fruta|pan|pasta|arroz|tomate|calabaza|platano|melocoton)\b/i', $text) ||
        preg_match('/\b(chuches|chupa chup|chicle|caramelo|dulce|golosina)\b/i', $text)) {
        
        $result['main'] = 746;
        
        // Subcategorías
        if (preg_match('/\b(restaurante|bar|cafeteria|tapas)\b/i', $text)) {
            $result['sub_name'] = 'Restaurantes y Bares';
        } elseif (preg_match('/\b(cerveza|vino|bebida|refresco)\b/i', $text)) {
            $result['sub_name'] = 'Bebidas';
        } elseif (preg_match('/\b(fruta|verdura|tomate|calabaza|platano)\b/i', $text)) {
            $result['sub_name'] = 'Productos Frescos';
        } elseif (preg_match('/\b(chuches|dulce|caramelo|golosina)\b/i', $text)) {
            $result['sub_name'] = 'Dulces y Golosinas';
        }
        
        return $result;
    }
    
    // BELLEZA Y ESTÉTICA (748)
    if (preg_match('/\b(peluqueria|estetica|belleza|masaje|spa|unas|maquillaje|tratamiento facial|depilacion|salon de belleza)\b/i', $text) ||
        preg_match('/\b(corte de pelo|barba|limpieza facial|alisado|tinte)\b/i', $text)) {
        
        $result['main'] = 748;
        
        // Subcategorías
        if (preg_match('/\b(corte|pelo|barba|alisado|tinte)\b/i', $text)) {
            $result['sub_name'] = 'Peluquería';
        } elseif (preg_match('/\b(unas|manicura|pedicura)\b/i', $text)) {
            $result['sub_name'] = 'Manicura y Pedicura';
        } elseif (preg_match('/\b(limpieza facial|tratamiento|mascarilla)\b/i', $text)) {
            $result['sub_name'] = 'Estética Facial';
        } elseif (preg_match('/\b(masaje|spa)\b/i', $text)) {
            $result['sub_name'] = 'Masajes y Spa';
        }
        
        return $result;
    }
    
    // MODA Y CALZADO (747)
    if (preg_match('/\b(zapatos?|zapatillas?|calzado|botas?|sandalias?|mocas[ií]n|mocasines|deportivas?|tacones?|ballenero)\b/i', $text) ||
        preg_match('/\b(ropa|vestidos?|camisas?|camisetas?|pantalon(es)?|faldas?|jerseys?|abrigos?|chaquetas?|sudadera|polo)\b/i', $text) ||
        preg_match('/\b(moda|boutique|tienda de ropa|banadors?|sujetador|bragas|lenceria|patucos)\b/i', $text) ||
        preg_match('/\b(reloj|collar|pulsera|anillo|joya|bisuteria)\b/i', $text)) {
        
        $result['main'] = 747;
        
        // Subcategorías
        if (preg_match('/\b(zapatos?|zapatillas?|botas?|calzado|sandalias?|mocasin|ballenero)\b/i', $text)) {
            $result['sub_name'] = 'Calzado';
        } elseif (preg_match('/\b(camiseta|camisa|pantalon|vestido|falda|jersey|abrigo|chaqueta|sudadera|polo)\b/i', $text)) {
            if (preg_match('/\b(mujer|señora|femenin)\b/i', $text)) {
                $result['sub_name'] = 'Ropa Mujer';
            } elseif (preg_match('/\b(hombre|caballero|masculin)\b/i', $text)) {
                $result['sub_name'] = 'Ropa Hombre';
            } elseif (preg_match('/\b(niño|niña|infantil|bebe)\b/i', $text)) {
                $result['sub_name'] = 'Ropa Infantil';
            } else {
                $result['sub_name'] = 'Ropa';
            }
        } elseif (preg_match('/\b(sujetador|bragas|lenceria)\b/i', $text)) {
            $result['sub_name'] = 'Lencería';
        } elseif (preg_match('/\b(reloj|collar|pulsera|anillo|joya)\b/i', $text)) {
            $result['sub_name'] = 'Joyería y Relojes';
        }
        
        return $result;
    }
    
    // DEPORTES Y OCIO (757)
    if (preg_match('/\b(deporte|gimnasio|fitness|yoga|paddle|futbol|baloncesto|natacion|ocio|entrenador|balon)\b/i', $text)) {
        $result['main'] = 757;
        $result['sub_name'] = 'Equipamiento Deportivo';
        return $result;
    }
    
    // FLORES Y EVENTOS (756)
    if (preg_match('/\b(flores|floristeria|ramo|boda|evento|celebracion|decoracion floral|orquidea|rosa|letra preservada)\b/i', $text)) {
        $result['main'] = 756;
        
        if (preg_match('/\b(orquidea|rosa|ramo)\b/i', $text)) {
            $result['sub_name'] = 'Flores Naturales';
        } elseif (preg_match('/\b(preservada)\b/i', $text)) {
            $result['sub_name'] = 'Flores Preservadas';
        }
        
        return $result;
    }
    
    // VEHÍCULOS Y MOTOR (751)
    if (preg_match('/\b(coche|carro|auto|vehiculo|moto|bicicleta|cambio de aceite|neumatico|taller|mecanico|motor|revision|itv|rueda|freno|palanca)\b/i', $text)) {
        $result['main'] = 751;
        $result['sub_name'] = 'Mantenimiento y Reparación';
        return $result;
    }
    
    // TECNOLOGÍA E INFORMÁTICA (750)
    if (preg_match('/\b(ordenador|portatil|movil|telefono|tablet|informatica|software|hardware|reparacion movil|pc|mac|tarifa|fibra|gb|mb|web|android)\b/i', $text)) {
        $result['main'] = 750;
        
        if (preg_match('/\b(tarifa|fibra|gb|mb|llamadas)\b/i', $text)) {
            $result['sub_name'] = 'Tarifas y Telecomunicaciones';
        } elseif (preg_match('/\b(web|diseno|marketing)\b/i', $text)) {
            $result['sub_name'] = 'Diseño y Marketing';
        } else {
            $result['sub_name'] = 'Informática';
        }
        
        return $result;
    }
    
    // HOGAR Y DECORACIÓN (749)
    if (preg_match('/\b(mueble|decoracion|sofa|mesa|silla|lampara|cortina|alfombra|hogar|interiorismo)\b/i', $text)) {
        $result['main'] = 749;
        $result['sub_name'] = 'Muebles';
        return $result;
    }
    
    // SALUD Y BIENESTAR (753)
    if (preg_match('/\b(medico|clinica|salud|fisioterapia|nutricion|farmacia|dentista|optica|psicologo|terapeuta)\b/i', $text)) {
        $result['main'] = 753;
        $result['sub_name'] = 'Servicios Médicos';
        return $result;
    }
    
    // SERVICIOS PROFESIONALES (752)
    if (preg_match('/\b(abogado|asesor|consultor|contable|gestor|notario|arquitecto|ingeniero|administrador de fincas|proteccion solar|sistema)\b/i', $text)) {
        $result['main'] = 752;
        $result['sub_name'] = 'Servicios del Hogar';
        return $result;
    }
    
    // BEBÉ E INFANTIL (754)
    if (preg_match('/\b(bebe|nino|infantil|cuna|carrito|panal|juguete|guarderia)\b/i', $text)) {
        $result['main'] = 754;
        $result['sub_name'] = 'Productos Bebé';
        return $result;
    }
    
    // FERRETERÍA Y BRICOLAJE (758)
    if (preg_match('/\b(ferreteria|herramienta|bricolaje|pintura|tornillo|taladro|martillo|fontaneria|electricidad)\b/i', $text)) {
        $result['main'] = 758;
        $result['sub_name'] = 'Herramientas';
        return $result;
    }
    
    // Si no se encontró nada, usar Otros
    $result['main'] = 759;
    $result['sub_name'] = 'Varios';
    
    return $result;
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
$created_subcats = array();

foreach ($products as $product) {
    $title = $product->post_title;
    $description = $product->post_excerpt;
    
    // Analizar
    $analysis = analyze_product_intelligent($title, $description);
    
    if (!$analysis['main']) {
        continue;
    }
    
    $categories_to_assign = array($analysis['main']);
    
    // Obtener o crear subcategoría
    if ($analysis['sub_name']) {
        $sub_id = $analysis['sub'];
        
        if (!$sub_id) {
            $sub_id = get_or_create_subcategory($analysis['main'], $analysis['sub_name']);
            if ($sub_id) {
                $created_subcats[$analysis['sub_name']] = $sub_id;
            }
        }
        
        if ($sub_id) {
            $categories_to_assign[] = $sub_id;
        }
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

if (!empty($created_subcats)) {
    echo "\n✨ Subcategorías creadas:\n";
    foreach ($created_subcats as $name => $id) {
        echo "   - {$name} (ID: {$id})\n";
    }
}

$next_offset = $offset + $limit;
if ($next_offset < 2854) {
    echo "\n💡 Siguiente lote:\n";
    echo "   php recategorize-smart-ai.php {$next_offset} {$limit}" . ($apply ? " --apply" : "") . "\n";
    echo "\n📊 Progreso: " . round((($offset + count($products)) / 2854) * 100, 1) . "%\n";
}

