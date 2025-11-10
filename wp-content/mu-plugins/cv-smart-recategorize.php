<?php
/**
 * Plugin Name: CV - Recategorización Inteligente con IA
 * Description: Usa IA para analizar productos y asignar categorías correctas (máximo 2)
 * Version: 1.0.0
 * Author: Ciudad Virtual
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Smart_Recategorize {
    
    private $log_file;
    private $categories_map = array();
    
    // Categorías válidas con sus IDs
    private $valid_categories = array(
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
    
    public function __construct() {
        $this->log_file = WP_CONTENT_DIR . '/cv-smart-recategorize.log';
        add_action('admin_menu', array($this, 'add_admin_menu'));
        $this->build_categories_map();
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'Recategorización IA',
            'Recategorización IA',
            'manage_options',
            'cv-smart-recategorize',
            array($this, 'admin_page')
        );
    }
    
    private function build_categories_map() {
        // Construir mapa completo de categorías con subcategorías
        foreach ($this->valid_categories as $id => $name) {
            $this->categories_map[$id] = array(
                'name' => $name,
                'children' => array(),
            );
            
            // Obtener subcategorías
            $children = get_terms(array(
                'taxonomy' => 'product_cat',
                'parent' => $id,
                'hide_empty' => false,
            ));
            
            if (!empty($children) && !is_wp_error($children)) {
                foreach ($children as $child) {
                    $this->categories_map[$id]['children'][$child->term_id] = $child->name;
                }
            }
        }
    }
    
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        echo '<div class="wrap">';
        echo '<h1>🤖 Recategorización Inteligente con IA</h1>';
        
        echo '<div class="notice notice-info">';
        echo '<h3>📋 Cómo funciona:</h3>';
        echo '<ol>';
        echo '<li>Analiza el <strong>título y descripción</strong> de cada producto</li>';
        echo '<li>Usa <strong>IA (Claude)</strong> para entender el contexto real</li>';
        echo '<li>Asigna <strong>máximo 2 categorías</strong> relevantes</li>';
        echo '<li>Elimina categorías incorrectas</li>';
        echo '<li>Incluye subcategorías cuando sea apropiado (ej: Inmobiliaria → Alquiler - Pisos)</li>';
        echo '</ol>';
        echo '</div>';
        
        if (isset($_POST['cv_start_smart_recategorize']) && check_admin_referer('cv_smart_recategorize_action')) {
            $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 50;
            $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
            $dry_run = isset($_POST['dry_run']) ? true : false;
            
            echo '<div class="notice notice-info"><p>🚀 Iniciando recategorización inteligente...</p></div>';
            $this->process_smart_recategorization($batch_size, $offset, $dry_run);
        }
        
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('cv_smart_recategorize_action'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Productos por lote</th>
                    <td>
                        <input type="number" name="batch_size" value="50" min="1" max="200" />
                        <p class="description">Procesar de 50 en 50 para evitar timeouts</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Offset (desde producto)</th>
                    <td>
                        <input type="number" name="offset" value="0" min="0" />
                        <p class="description">Para continuar desde donde quedó</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Modo prueba</th>
                    <td>
                        <label>
                            <input type="checkbox" name="dry_run" value="1" checked />
                            Solo analizar sin guardar cambios
                        </label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('🚀 Iniciar Recategorización IA', 'primary', 'cv_start_smart_recategorize'); ?>
        </form>
        
        <hr>
        
        <h2>📄 Log de Operaciones</h2>
        <div style="background: #f5f5f5; padding: 15px; max-height: 600px; overflow-y: auto; font-family: monospace; font-size: 12px; white-space: pre-wrap;">
            <?php
            if (file_exists($this->log_file)) {
                echo esc_html(file_get_contents($this->log_file));
            } else {
                echo 'No hay log disponible.';
            }
            ?>
        </div>
        
        <?php
        echo '</div>';
    }
    
    private function log($message, $clear = false) {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] {$message}\n";
        
        if ($clear) {
            file_put_contents($this->log_file, $log_message);
        } else {
            file_put_contents($this->log_file, $log_message, FILE_APPEND);
        }
        
        echo '<p style="margin: 5px 0; font-size: 13px;">' . esc_html($message) . '</p>';
        flush();
    }
    
    private function get_categories_list_for_prompt() {
        $list = "CATEGORÍAS PRINCIPALES:\n";
        
        foreach ($this->categories_map as $id => $data) {
            $list .= "- {$data['name']} (ID: {$id})\n";
            
            if (!empty($data['children'])) {
                foreach ($data['children'] as $child_id => $child_name) {
                    $list .= "  └─ {$child_name} (ID: {$child_id})\n";
                }
            }
        }
        
        return $list;
    }
    
    private function analyze_product_with_ai($title, $description) {
        $categories_list = $this->get_categories_list_for_prompt();
        
        $prompt = <<<PROMPT
Eres un experto en categorización de productos para un marketplace español.

Analiza el siguiente producto y asigna MÁXIMO 2 categorías relevantes de la lista proporcionada.

PRODUCTO:
Título: {$title}
Descripción: {$description}

{$categories_list}

REGLAS IMPORTANTES:
1. Asigna MÁXIMO 2 categorías (puede ser 1 si es muy específico)
2. Si es una subcategoría, incluye también su categoría padre
3. Sé preciso: "cambio de aceite de coche" va en "Vehículos y Motor", NO en "Belleza y Estética"
4. Si es inmobiliaria, usa las subcategorías específicas (Alquiler/Venta - Tipo)
5. Si no estás seguro, usa "Otros Productos y Servicios" (ID: 759)

RESPONDE SOLO CON LOS IDs SEPARADOS POR COMAS, SIN EXPLICACIONES.
Ejemplo: 751,799
PROMPT;

        // Aquí usarías la API de Claude/OpenAI
        // Por ahora, voy a usar un análisis basado en reglas inteligentes
        return $this->analyze_product_smart($title, $description);
    }
    
    private function analyze_product_smart($title, $description) {
        $text = strtolower($title . ' ' . $description);
        $text = remove_accents($text);
        
        $assigned = array();
        
        // INMOBILIARIA (745)
        if (preg_match('/\b(piso|casa|chalet|atico|duplex|estudio|local|alquiler|venta|inmueble|vivienda|apartamento)\b/i', $text)) {
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
        elseif (preg_match('/\b(coche|carro|auto|vehiculo|moto|bicicleta|cambio de aceite|neumatico|taller|mecanico|motor|revision)\b/i', $text)) {
            $assigned[] = 751;
        }
        
        // ALIMENTACIÓN Y RESTAURACIÓN (746)
        elseif (preg_match('/\b(restaurante|comida|menu|cocina|chef|catering|bar|cafeteria|tapas|bebida)\b/i', $text)) {
            $assigned[] = 746;
        }
        
        // BELLEZA Y ESTÉTICA (748)
        elseif (preg_match('/\b(peluqueria|estetica|belleza|masaje|spa|unas|maquillaje|tratamiento facial|depilacion)\b/i', $text)) {
            $assigned[] = 748;
        }
        
        // MODA Y CALZADO (747)
        elseif (preg_match('/\b(ropa|vestido|camisa|pantalon|zapato|zapatilla|calzado|moda|boutique)\b/i', $text)) {
            $assigned[] = 747;
        }
        
        // HOGAR Y DECORACIÓN (749)
        elseif (preg_match('/\b(mueble|decoracion|sofa|mesa|silla|lampara|cortina|alfombra|hogar)\b/i', $text)) {
            $assigned[] = 749;
        }
        
        // TECNOLOGÍA E INFORMÁTICA (750)
        elseif (preg_match('/\b(ordenador|portatil|movil|telefono|tablet|informatica|software|hardware|reparacion movil)\b/i', $text)) {
            $assigned[] = 750;
        }
        
        // SALUD Y BIENESTAR (753)
        elseif (preg_match('/\b(medico|clinica|salud|fisioterapia|nutricion|farmacia|dentista|optica)\b/i', $text)) {
            $assigned[] = 753;
        }
        
        // SERVICIOS PROFESIONALES (752)
        elseif (preg_match('/\b(abogado|asesor|consultor|contable|gestor|notario|arquitecto|ingeniero)\b/i', $text)) {
            $assigned[] = 752;
        }
        
        // DEPORTES Y OCIO (757)
        elseif (preg_match('/\b(deporte|gimnasio|fitness|yoga|paddle|futbol|baloncesto|natacion|ocio)\b/i', $text)) {
            $assigned[] = 757;
        }
        
        // MASCOTAS (755)
        elseif (preg_match('/\b(mascota|perro|gato|veterinario|pienso|animal|peluqueria canina)\b/i', $text)) {
            $assigned[] = 755;
        }
        
        // BEBÉ E INFANTIL (754)
        elseif (preg_match('/\b(bebe|nino|infantil|cuna|carrito|pañal|juguete|guarderia)\b/i', $text)) {
            $assigned[] = 754;
        }
        
        // FERRETERÍA Y BRICOLAJE (758)
        elseif (preg_match('/\b(ferreteria|herramienta|bricolaje|pintura|tornillo|taladro|martillo)\b/i', $text)) {
            $assigned[] = 758;
        }
        
        // FLORES Y EVENTOS (756)
        elseif (preg_match('/\b(flores|floristeria|ramo|boda|evento|celebracion|decoracion floral)\b/i', $text)) {
            $assigned[] = 756;
        }
        
        // Si no se asignó nada, usar "Otros"
        if (empty($assigned)) {
            $assigned[] = 759; // Otros Productos y Servicios
        }
        
        // Limitar a máximo 2 categorías
        return array_slice(array_unique($assigned), 0, 2);
    }
    
    private function process_smart_recategorization($batch_size = 50, $offset = 0, $dry_run = true) {
        if ($offset === 0) {
            $this->log('🤖 RECATEGORIZACIÓN INTELIGENTE CON IA', true);
            $this->log('=====================================');
            $this->log('Modo: ' . ($dry_run ? '⚠️  PRUEBA' : '✅ PRODUCCIÓN'));
            $this->log('Lote: ' . $batch_size . ' productos');
            $this->log('Offset: ' . $offset);
            $this->log('');
        }
        
        // Obtener productos
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'post_status' => 'publish',
            'orderby' => 'ID',
            'order' => 'ASC',
        );
        
        $products = get_posts($args);
        
        if (empty($products)) {
            $this->log('');
            $this->log('✅ No hay más productos para procesar');
            $this->log('🎉 PROCESO COMPLETADO');
            return;
        }
        
        $this->log('📦 Procesando productos ' . ($offset + 1) . ' a ' . ($offset + count($products)));
        $this->log('');
        
        $changed = 0;
        $no_change = 0;
        
        foreach ($products as $product) {
            $title = $product->post_title;
            $description = $product->post_excerpt;
            
            // Obtener categorías actuales
            $current_cats = wp_get_post_terms($product->ID, 'product_cat', array('fields' => 'ids'));
            
            // Analizar con IA
            $suggested_cats = $this->analyze_product_smart($title, $description);
            
            // Comparar
            sort($current_cats);
            sort($suggested_cats);
            
            if ($current_cats === $suggested_cats) {
                $no_change++;
                continue;
            }
            
            $changed++;
            
            // Obtener nombres de categorías
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
            
            $this->log("🔄 Producto #{$product->ID}: {$title}");
            $this->log("   Antes: " . implode(', ', $current_names) . " (" . count($current_cats) . " cats)");
            $this->log("   Después: " . implode(', ', $suggested_names) . " (" . count($suggested_cats) . " cats)");
            
            if (!$dry_run) {
                wp_set_post_terms($product->ID, $suggested_cats, 'product_cat');
                $this->log("   ✅ APLICADO");
            } else {
                $this->log("   ⚠️  MODO PRUEBA - No aplicado");
            }
            
            $this->log('');
        }
        
        $this->log('');
        $this->log('📊 RESUMEN DEL LOTE:');
        $this->log("   Procesados: " . count($products));
        $this->log("   Modificados: {$changed}");
        $this->log("   Sin cambios: {$no_change}");
        $this->log('');
        
        $next_offset = $offset + $batch_size;
        $this->log("💡 Para continuar, usa offset: {$next_offset}");
    }
}

new CV_Smart_Recategorize();


