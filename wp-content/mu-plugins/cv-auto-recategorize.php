<?php
/**
 * Plugin Name: CV - Auto Recategorizar Productos
 * Description: Recategoriza productos basándose en título y descripción corta
 * Version: 1.0.0
 * Author: Ciudad Virtual
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Auto_Recategorize {
    
    private $category_keywords = array();
    private $log_file;
    
    public function __construct() {
        $this->log_file = WP_CONTENT_DIR . '/cv-recategorize.log';
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'Auto Recategorizar',
            'Auto Recategorizar',
            'manage_options',
            'cv-auto-recategorize',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        echo '<div class="wrap">';
        echo '<h1>Auto Recategorizar Productos</h1>';
        
        if (isset($_POST['cv_start_recategorize']) && check_admin_referer('cv_recategorize_action')) {
            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 100;
            $dry_run = isset($_POST['dry_run']) ? true : false;
            
            echo '<div class="notice notice-info"><p>Iniciando recategorización...</p></div>';
            $this->process_recategorization($limit, $dry_run);
        }
        
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('cv_recategorize_action'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Límite de productos</th>
                    <td>
                        <input type="number" name="limit" value="100" min="1" max="10000" />
                        <p class="description">Número máximo de productos a procesar</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Modo prueba</th>
                    <td>
                        <label>
                            <input type="checkbox" name="dry_run" value="1" checked />
                            Solo mostrar cambios sin aplicarlos
                        </label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Iniciar Recategorización', 'primary', 'cv_start_recategorize'); ?>
        </form>
        
        <hr>
        
        <h2>Log de operaciones</h2>
        <div style="background: #f5f5f5; padding: 15px; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px;">
            <?php
            if (file_exists($this->log_file)) {
                echo nl2br(esc_html(file_get_contents($this->log_file)));
            } else {
                echo 'No hay log disponible.';
            }
            ?>
        </div>
        
        <?php
        echo '</div>';
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] {$message}\n";
        file_put_contents($this->log_file, $log_message, FILE_APPEND);
        echo '<p>' . esc_html($message) . '</p>';
        flush();
    }
    
    private function normalize_text($text) {
        $text = strtolower($text);
        $text = remove_accents($text);
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
    
    private function build_category_keywords() {
        $this->log('📚 Construyendo mapa de palabras clave por categoría...');
        
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));
        
        foreach ($categories as $cat) {
            $keywords = array();
            
            // Nombre de la categoría
            $name_normalized = $this->normalize_text($cat->name);
            $name_words = explode(' ', $name_normalized);
            
            // Agregar palabras individuales del nombre
            foreach ($name_words as $word) {
                if (strlen($word) > 2) { // Ignorar palabras muy cortas
                    $keywords[] = $word;
                }
            }
            
            // Agregar el nombre completo
            $keywords[] = $name_normalized;
            
            // Si tiene padre, agregar contexto del padre
            if ($cat->parent > 0) {
                $parent = get_term($cat->parent, 'product_cat');
                if ($parent && !is_wp_error($parent)) {
                    $parent_normalized = $this->normalize_text($parent->name);
                    $parent_words = explode(' ', $parent_normalized);
                    foreach ($parent_words as $word) {
                        if (strlen($word) > 2) {
                            $keywords[] = $word;
                        }
                    }
                }
            }
            
            $this->category_keywords[$cat->term_id] = array(
                'name' => $cat->name,
                'keywords' => array_unique($keywords),
                'parent' => $cat->parent,
            );
        }
        
        $this->log('✅ Mapa construido con ' . count($this->category_keywords) . ' categorías');
    }
    
    private function find_best_categories($title, $description, $max_results = 3) {
        $text = $title . ' ' . $description;
        $text_normalized = $this->normalize_text($text);
        $text_words = explode(' ', $text_normalized);
        
        $scores = array();
        
        foreach ($this->category_keywords as $cat_id => $cat_data) {
            $score = 0;
            
            foreach ($cat_data['keywords'] as $keyword) {
                // Buscar coincidencia exacta de la palabra clave
                if (strpos($text_normalized, $keyword) !== false) {
                    // Peso mayor si coincide el nombre completo
                    if ($keyword === $this->normalize_text($cat_data['name'])) {
                        $score += 10;
                    } else {
                        $score += 5;
                    }
                }
                
                // Buscar coincidencias en palabras individuales
                foreach ($text_words as $word) {
                    if ($word === $keyword && strlen($word) > 3) {
                        $score += 3;
                    }
                }
            }
            
            if ($score > 0) {
                $scores[$cat_id] = $score;
            }
        }
        
        // Ordenar por puntuación descendente
        arsort($scores);
        
        // Devolver top N categorías
        $results = array();
        $count = 0;
        foreach ($scores as $cat_id => $score) {
            if ($count >= $max_results) {
                break;
            }
            $results[] = array(
                'id' => $cat_id,
                'name' => $this->category_keywords[$cat_id]['name'],
                'score' => $score,
                'parent' => $this->category_keywords[$cat_id]['parent'],
            );
            $count++;
        }
        
        return $results;
    }
    
    private function process_recategorization($limit = 100, $dry_run = true) {
        // Limpiar log
        file_put_contents($this->log_file, '');
        
        $this->log('🚀 Iniciando recategorización...');
        $this->log('📊 Límite: ' . $limit . ' productos');
        $this->log('🔧 Modo: ' . ($dry_run ? 'PRUEBA (no se guardarán cambios)' : 'PRODUCCIÓN (se aplicarán cambios)'));
        $this->log('');
        
        // Construir mapa de palabras clave
        $this->build_category_keywords();
        $this->log('');
        
        // Obtener productos
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'orderby' => 'ID',
            'order' => 'ASC',
        );
        
        $products = get_posts($args);
        $this->log('📦 Productos a procesar: ' . count($products));
        $this->log('');
        
        $processed = 0;
        $changed = 0;
        $skipped = 0;
        
        foreach ($products as $product) {
            $processed++;
            
            $title = $product->post_title;
            $description = $product->post_excerpt;
            
            // Obtener categorías actuales
            $current_cats = wp_get_post_terms($product->ID, 'product_cat', array('fields' => 'ids'));
            
            // Encontrar mejores categorías
            $suggested_cats = $this->find_best_categories($title, $description, 3);
            
            if (empty($suggested_cats)) {
                $this->log("⏭️  #{$product->ID} - {$title} - Sin sugerencias");
                $skipped++;
                continue;
            }
            
            $suggested_ids = array_column($suggested_cats, 'id');
            
            // Agregar categorías padre si es necesario
            $final_cats = array();
            foreach ($suggested_ids as $cat_id) {
                $final_cats[] = $cat_id;
                
                // Agregar padre si existe
                $parent_id = $this->category_keywords[$cat_id]['parent'];
                if ($parent_id > 0 && !in_array($parent_id, $final_cats)) {
                    $final_cats[] = $parent_id;
                }
            }
            
            $final_cats = array_unique($final_cats);
            
            // Comparar con categorías actuales
            $cats_to_add = array_diff($final_cats, $current_cats);
            
            if (empty($cats_to_add)) {
                $this->log("✅ #{$product->ID} - {$title} - Ya tiene categorías correctas");
                continue;
            }
            
            $changed++;
            
            // Mostrar cambios
            $cat_names = array();
            foreach ($suggested_cats as $cat) {
                $cat_names[] = $cat['name'] . ' (score: ' . $cat['score'] . ')';
            }
            
            $this->log("🔄 #{$product->ID} - {$title}");
            $this->log("   Sugerencias: " . implode(', ', $cat_names));
            
            // Aplicar cambios si no es dry run
            if (!$dry_run) {
                // Combinar categorías actuales con las nuevas (no eliminar las existentes)
                $all_cats = array_unique(array_merge($current_cats, $final_cats));
                wp_set_post_terms($product->ID, $all_cats, 'product_cat');
                $this->log("   ✅ Categorías actualizadas");
            } else {
                $this->log("   ⚠️  MODO PRUEBA - No se aplicaron cambios");
            }
            
            $this->log('');
            
            // Evitar timeout
            if ($processed % 10 === 0) {
                usleep(100000); // 0.1 segundos
            }
        }
        
        $this->log('');
        $this->log('📊 RESUMEN:');
        $this->log("   Procesados: {$processed}");
        $this->log("   Modificados: {$changed}");
        $this->log("   Sin cambios: {$skipped}");
        $this->log('');
        $this->log('✅ Proceso completado');
    }
}

new CV_Auto_Recategorize();

