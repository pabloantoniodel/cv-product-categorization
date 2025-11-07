<?php
/**
 * Plugin Name: CV - Limpieza de Categorías
 * Description: Elimina categorías antiguas y reasigna productos
 * Version: 1.0.0
 * Author: Ciudad Virtual
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Category_Cleanup {
    
    // Categorías NUEVAS que queremos mantener
    private $good_categories = array(
        746, // Alimentación y Restauración
        754, // Bebé e Infantil
        748, // Belleza y Estética
        757, // Deportes y Ocio
        758, // Ferretería y Bricolaje
        756, // Flores y Eventos
        749, // Hogar y Decoración
        745, // Inmobiliaria
        747, // Moda y Calzado
        755, // Mascotas
        759, // Otros Productos y Servicios
        753, // Salud y Bienestar
        752, // Servicios Profesionales
        750, // Tecnología e Informática
        751, // Vehículos y Motor
    );
    
    private $log_file;
    private $fallback_category = 759; // Otros Productos y Servicios
    
    public function __construct() {
        $this->log_file = WP_CONTENT_DIR . '/cv-category-cleanup.log';
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'Limpieza de Categorías',
            'Limpieza de Categorías',
            'manage_options',
            'cv-category-cleanup',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        echo '<div class="wrap">';
        echo '<h1>🧹 Limpieza de Categorías</h1>';
        
        // Mostrar plan
        echo '<div class="notice notice-info">';
        echo '<h2>📋 Plan de Acción</h2>';
        echo '<ol>';
        echo '<li><strong>FASE 1:</strong> Análisis - Ver qué productos tienen categorías antiguas</li>';
        echo '<li><strong>FASE 2:</strong> Backup - Crear respaldo de seguridad</li>';
        echo '<li><strong>FASE 3:</strong> Reasignación - Mover productos a categorías nuevas</li>';
        echo '<li><strong>FASE 4:</strong> Limpieza - Eliminar categorías antiguas vacías</li>';
        echo '<li><strong>FASE 5:</strong> Verificación - Comprobar que todo está correcto</li>';
        echo '</ol>';
        echo '</div>';
        
        // Botones de acción
        if (isset($_POST['cv_action']) && check_admin_referer('cv_cleanup_action')) {
            $action = sanitize_text_field($_POST['cv_action']);
            
            switch ($action) {
                case 'analyze':
                    $this->analyze();
                    break;
                case 'backup':
                    $this->create_backup();
                    break;
                case 'reassign':
                    $dry_run = isset($_POST['dry_run']) ? true : false;
                    $this->reassign_products($dry_run);
                    break;
                case 'cleanup':
                    $this->cleanup_old_categories();
                    break;
                case 'verify':
                    $this->verify();
                    break;
            }
        }
        
        ?>
        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin: 20px 0;">
            
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('cv_cleanup_action'); ?>
                <input type="hidden" name="cv_action" value="analyze" />
                <?php submit_button('1️⃣ Analizar', 'secondary', 'submit', false); ?>
            </form>
            
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('cv_cleanup_action'); ?>
                <input type="hidden" name="cv_action" value="backup" />
                <?php submit_button('2️⃣ Backup', 'secondary', 'submit', false); ?>
            </form>
            
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('cv_cleanup_action'); ?>
                <input type="hidden" name="cv_action" value="reassign" />
                <input type="hidden" name="dry_run" value="1" />
                <?php submit_button('3️⃣ Reasignar (Prueba)', 'primary', 'submit', false); ?>
            </form>
            
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('cv_cleanup_action'); ?>
                <input type="hidden" name="cv_action" value="reassign" />
                <?php submit_button('3️⃣ Reasignar (Real)', 'primary', 'submit', false, array('onclick' => 'return confirm("¿Estás seguro? Esto modificará productos.");')); ?>
            </form>
            
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('cv_cleanup_action'); ?>
                <input type="hidden" name="cv_action" value="cleanup" />
                <?php submit_button('4️⃣ Limpiar', 'delete', 'submit', false, array('onclick' => 'return confirm("¿Eliminar categorías antiguas vacías?");')); ?>
            </form>
        </div>
        
        <form method="post" style="margin: 20px 0;">
            <?php wp_nonce_field('cv_cleanup_action'); ?>
            <input type="hidden" name="cv_action" value="verify" />
            <?php submit_button('5️⃣ Verificar Todo', 'secondary', 'submit', false); ?>
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
        
        echo '<p>' . esc_html($message) . '</p>';
        flush();
    }
    
    private function get_all_good_category_ids() {
        // Obtener IDs de categorías buenas + sus hijos
        $all_good = $this->good_categories;
        
        foreach ($this->good_categories as $parent_id) {
            $children = get_term_children($parent_id, 'product_cat');
            if (!is_wp_error($children)) {
                $all_good = array_merge($all_good, $children);
            }
        }
        
        return array_unique($all_good);
    }
    
    private function analyze() {
        $this->log('🔍 FASE 1: ANÁLISIS', true);
        $this->log('=====================================');
        $this->log('');
        
        $good_ids = $this->get_all_good_category_ids();
        $this->log('✅ Categorías BUENAS (a mantener): ' . count($good_ids));
        
        // Obtener todas las categorías
        $all_cats = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'fields' => 'ids',
        ));
        
        $bad_ids = array_diff($all_cats, $good_ids);
        $this->log('❌ Categorías ANTIGUAS (a eliminar): ' . count($bad_ids));
        $this->log('');
        
        // Analizar productos
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
        );
        
        $all_products = get_posts($args);
        $this->log('📦 Total de productos: ' . count($all_products));
        $this->log('');
        
        $only_bad = 0;
        $mix = 0;
        $only_good = 0;
        $no_cats = 0;
        
        foreach ($all_products as $product_id) {
            $cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
            
            if (empty($cats)) {
                $no_cats++;
                continue;
            }
            
            $has_good = !empty(array_intersect($cats, $good_ids));
            $has_bad = !empty(array_intersect($cats, $bad_ids));
            
            if ($has_good && $has_bad) {
                $mix++;
            } elseif ($has_good) {
                $only_good++;
            } elseif ($has_bad) {
                $only_bad++;
            }
        }
        
        $this->log('📊 DISTRIBUCIÓN DE PRODUCTOS:');
        $this->log('   ✅ Solo categorías BUENAS: ' . $only_good);
        $this->log('   ⚠️  MIX (buenas + antiguas): ' . $mix);
        $this->log('   ❌ Solo categorías ANTIGUAS: ' . $only_bad . ' ⚠️ REQUIEREN REASIGNACIÓN');
        $this->log('   ⭕ Sin categorías: ' . $no_cats);
        $this->log('');
        
        $this->log('🎯 ACCIONES NECESARIAS:');
        $this->log('   1. Reasignar ' . $only_bad . ' productos que solo tienen categorías antiguas');
        $this->log('   2. Limpiar ' . $mix . ' productos que tienen mix de categorías');
        $this->log('   3. Asignar categoría a ' . $no_cats . ' productos sin categorías');
        $this->log('   4. Eliminar ' . count($bad_ids) . ' categorías antiguas');
        $this->log('');
        $this->log('✅ Análisis completado');
    }
    
    private function create_backup() {
        $this->log('💾 FASE 2: BACKUP', true);
        $this->log('=====================================');
        $this->log('');
        
        $backup_file = '/home/ciudadvirtual/backups/pre-category-cleanup-' . date('Ymd-His') . '.sql';
        
        $this->log('📁 Creando backup en: ' . $backup_file);
        
        $command = "wp db export {$backup_file} --allow-root 2>&1";
        exec($command, $output, $return_code);
        
        if ($return_code === 0) {
            $this->log('✅ Backup creado exitosamente');
            $this->log('   Archivo: ' . $backup_file);
            
            // Verificar tamaño
            if (file_exists($backup_file)) {
                $size = filesize($backup_file);
                $size_mb = round($size / 1024 / 1024, 2);
                $this->log('   Tamaño: ' . $size_mb . ' MB');
            }
        } else {
            $this->log('❌ Error al crear backup');
            $this->log('   ' . implode("\n   ", $output));
        }
    }
    
    private function reassign_products($dry_run = true) {
        $this->log('🔄 FASE 3: REASIGNACIÓN DE PRODUCTOS', true);
        $this->log('=====================================');
        $this->log('Modo: ' . ($dry_run ? '⚠️  PRUEBA (no se guardan cambios)' : '✅ PRODUCCIÓN (se aplicarán cambios)'));
        $this->log('');
        
        $good_ids = $this->get_all_good_category_ids();
        $all_cats = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'fields' => 'ids',
        ));
        $bad_ids = array_diff($all_cats, $good_ids);
        
        // Obtener todos los productos
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
        );
        
        $all_products = get_posts($args);
        $this->log('📦 Procesando ' . count($all_products) . ' productos...');
        $this->log('');
        
        $reassigned = 0;
        $cleaned = 0;
        $no_change = 0;
        
        foreach ($all_products as $product_id) {
            $cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
            
            if (empty($cats)) {
                // Producto sin categorías - asignar fallback
                if (!$dry_run) {
                    wp_set_post_terms($product_id, array($this->fallback_category), 'product_cat');
                }
                $this->log("⭕ Producto #{$product_id}: Sin categorías → Asignado a 'Otros'");
                $reassigned++;
                continue;
            }
            
            $has_good = array_intersect($cats, $good_ids);
            $has_bad = array_intersect($cats, $bad_ids);
            
            if (!empty($has_bad) && empty($has_good)) {
                // Solo tiene categorías antiguas - necesita reasignación
                if (!$dry_run) {
                    wp_set_post_terms($product_id, array($this->fallback_category), 'product_cat');
                }
                $this->log("❌ Producto #{$product_id}: Solo categorías antiguas → Reasignado a 'Otros'");
                $reassigned++;
                
            } elseif (!empty($has_bad) && !empty($has_good)) {
                // Tiene mix - eliminar solo las antiguas
                if (!$dry_run) {
                    wp_set_post_terms($product_id, $has_good, 'product_cat');
                }
                $this->log("⚠️  Producto #{$product_id}: Mix → Eliminadas categorías antiguas");
                $cleaned++;
                
            } else {
                // Solo tiene categorías buenas - no hacer nada
                $no_change++;
            }
            
            // Evitar timeout
            if (($reassigned + $cleaned) % 100 === 0) {
                usleep(50000); // 0.05 segundos
            }
        }
        
        $this->log('');
        $this->log('📊 RESUMEN:');
        $this->log('   ✅ Sin cambios: ' . $no_change);
        $this->log('   🔄 Reasignados: ' . $reassigned);
        $this->log('   🧹 Limpiados: ' . $cleaned);
        $this->log('');
        $this->log('✅ Reasignación completada');
    }
    
    private function cleanup_old_categories() {
        $this->log('🗑️  FASE 4: LIMPIEZA DE CATEGORÍAS ANTIGUAS', true);
        $this->log('=====================================');
        $this->log('');
        
        $good_ids = $this->get_all_good_category_ids();
        $all_cats = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));
        
        $deleted = 0;
        $skipped = 0;
        
        foreach ($all_cats as $cat) {
            if (in_array($cat->term_id, $good_ids)) {
                continue; // Es una categoría buena, no tocar
            }
            
            // Verificar que no tenga productos
            if ($cat->count > 0) {
                $this->log("⚠️  Categoría '{$cat->name}' ({$cat->term_id}) tiene {$cat->count} productos - OMITIDA");
                $skipped++;
                continue;
            }
            
            // Eliminar categoría
            $result = wp_delete_term($cat->term_id, 'product_cat');
            
            if (!is_wp_error($result)) {
                $this->log("✅ Eliminada: '{$cat->name}' ({$cat->term_id})");
                $deleted++;
            } else {
                $this->log("❌ Error al eliminar '{$cat->name}': " . $result->get_error_message());
            }
        }
        
        $this->log('');
        $this->log('📊 RESUMEN:');
        $this->log('   ✅ Eliminadas: ' . $deleted);
        $this->log('   ⚠️  Omitidas (con productos): ' . $skipped);
        $this->log('');
        $this->log('✅ Limpieza completada');
    }
    
    private function verify() {
        $this->log('✅ FASE 5: VERIFICACIÓN FINAL', true);
        $this->log('=====================================');
        $this->log('');
        
        // Recalcular contadores
        $this->log('🔄 Recalculando contadores de términos...');
        $terms = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));
        
        foreach ($terms as $term) {
            wp_update_term_count_now(array($term->term_id), 'product_cat');
        }
        
        $this->log('✅ Contadores recalculados');
        $this->log('');
        
        // Verificar productos sin categorías
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'operator' => 'NOT EXISTS',
                ),
            ),
            'fields' => 'ids',
        );
        
        $no_cats = get_posts($args);
        
        $this->log('📊 VERIFICACIÓN:');
        $this->log('   Productos sin categorías: ' . count($no_cats));
        
        if (count($no_cats) > 0) {
            $this->log('   ⚠️  IDs: ' . implode(', ', array_slice($no_cats, 0, 20)));
        }
        
        $this->log('');
        
        // Contar categorías buenas
        $good_ids = $this->get_all_good_category_ids();
        $this->log('   Categorías activas: ' . count($good_ids));
        
        $this->log('');
        $this->log('✅ Verificación completada');
    }
}

new CV_Category_Cleanup();

