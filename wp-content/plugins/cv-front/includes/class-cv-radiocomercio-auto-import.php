<?php
/**
 * Importación Automática de Radio Comercio
 * 
 * Ejecuta un cron diario para importar nuevos artículos sobre autónomos
 * 
 * @package CV_Front
 * @since 2.8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_RadioComercio_Auto_Import {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Registrar cron
        add_action('wp', array($this, 'schedule_import'));
        
        // Hook del cron
        add_action('cv_import_radiocomercio', array($this, 'import_new_articles'));
        
        // Filtrar posts importados por keywords en las páginas públicas
        add_action('pre_get_posts', array($this, 'filter_imported_posts_by_keywords'));
        
        // Establecer 6 posts por página en archivo de noticias (prioridad alta)
        add_action('pre_get_posts', array($this, 'set_posts_per_page'), 999);
        
        // Desactivación: limpiar cron
        register_deactivation_hook(CV_FRONT_PLUGIN_FILE, array($this, 'clear_schedule'));
    }
    
    /**
     * Programar importación diaria
     */
    public function schedule_import() {
        if (!wp_next_scheduled('cv_import_radiocomercio')) {
            wp_schedule_event(time(), 'daily', 'cv_import_radiocomercio');
            error_log('✅ CV Radio: Cron diario programado');
        }
    }
    
    /**
     * Limpiar programación
     */
    public function clear_schedule() {
        $timestamp = wp_next_scheduled('cv_import_radiocomercio');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'cv_import_radiocomercio');
            error_log('🗑️ CV Radio: Cron desprogramado');
        }
    }
    
    /**
     * Importar nuevos artículos
     */
    public function import_new_articles() {
        error_log('🔄 CV Radio: Iniciando importación automática...');
        
        $total_imported = 0;
        
        // Términos de búsqueda relevantes
        $search_terms = array('autónomos', 'emprendedores', 'comercio', 'ciudadvirtual');
        
        // Fuentes a importar
        $sources = array();
        
        // Radio Comercio - con búsquedas específicas
        foreach ($search_terms as $term) {
            $sources[] = array(
                'name' => 'Radio Comercio - ' . ucfirst($term),
                'url' => 'https://radiocomercio.com/wp-json/wp/v2/posts?per_page=100&_embed&search=' . urlencode($term),
                'category' => 'Radio Comercio',
                'filter_keywords' => $search_terms
            );
        }
        
        // Noticias Radio Comercio - todos los posts pero filtrados por keywords
        $sources[] = array(
            'name' => 'Noticias Radio Comercio',
            'url' => 'https://noticias.radiocomercio.org/wp-json/wp/v2/posts?per_page=100&_embed',
            'category' => 'Noticias Radio Comercio',
            'filter_keywords' => $search_terms
        );
        
        foreach ($sources as $source) {
            error_log('📡 CV Radio: Importando desde ' . $source['name'] . '...');
            
            $filter_keywords = isset($source['filter_keywords']) ? $source['filter_keywords'] : array();
            $imported = $this->import_from_source($source['url'], $source['name'], $source['category'], $filter_keywords);
            $total_imported += $imported;
            
            error_log('✅ CV Radio: Importados ' . $imported . ' artículos de ' . $source['name']);
        }
        
        error_log('🎉 CV Radio: Importación completada. Total: ' . $total_imported . ' artículos nuevos');
    }
    
    /**
     * Importar artículos desde una fuente específica
     */
    private function import_from_source($api_url, $source_name, $category_name, $filter_keywords = array()) {
        // Obtener artículos
        $response = wp_remote_get($api_url, array(
            'timeout' => 30,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            error_log('❌ CV Radio: Error al conectar a ' . $source_name . ' - ' . $response->get_error_message());
            return 0;
        }
        
        $body = wp_remote_retrieve_body($response);
        $posts = json_decode($body, true);
        
        if (!is_array($posts) || empty($posts)) {
            error_log('⚠️ CV Radio: No se encontraron artículos en ' . $source_name);
            return 0;
        }
        
        error_log('📊 CV Radio: Encontrados ' . count($posts) . ' artículos en ' . $source_name);
        
        // Filtrar artículos por keywords EN EL TÍTULO
        if (!empty($filter_keywords)) {
            $filtered_posts = array();
            foreach ($posts as $post) {
                if ($this->post_contains_keywords($post, $filter_keywords)) {
                    $filtered_posts[] = $post;
                }
            }
            error_log('🔍 CV Radio: Filtrados ' . count($filtered_posts) . ' de ' . count($posts) . ' artículos con keywords en TÍTULO');
            $posts = $filtered_posts;
        }
        
        // Obtener o crear categorías
        $main_category_id = $this->get_or_create_category($category_name);
        $autonomos_cat_id = $this->get_or_create_category('Autónomos');
        
        $imported = 0;
        $skipped = 0;
        
        foreach ($posts as $post) {
            $title = isset($post['title']['rendered']) ? $post['title']['rendered'] : '';
            $link = isset($post['link']) ? $post['link'] : '';
            $original_id = isset($post['id']) ? $post['id'] : 0;
            
            // Verificar si ya existe por ID original
            $existing = get_posts(array(
                'post_type' => 'post',
                'meta_key' => '_radiocomercio_post_id',
                'meta_value' => $original_id,
                'posts_per_page' => 1
            ));
            
            if (!empty($existing)) {
                $skipped++;
                continue;
            }
            
            // Verificar por título también (WP 6.2+ compatible)
            $existing_by_title = get_posts(array(
                'post_type' => 'post',
                'title' => $title,
                'posts_per_page' => 1
            ));
            if (!empty($existing_by_title)) {
                $skipped++;
                continue;
            }
            
            // Preparar contenido
            $content = isset($post['content']['rendered']) ? $post['content']['rendered'] : '';
            $excerpt = isset($post['excerpt']['rendered']) ? wp_strip_all_tags($post['excerpt']['rendered']) : '';
            $date = isset($post['date']) ? $post['date'] : current_time('mysql');
            
            // Limpiar título de comillas HTML
            $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
            $title = str_replace('"', '', $title);
            
            // Añadir enlace original al final del contenido
            $content .= "\n\n<hr>\n\n";
            $content .= "<p><em>Artículo original publicado en <a href=\"{$link}\" target=\"_blank\" rel=\"noopener\">{$source_name}</a></em></p>";
            
            // Determinar categorías (solo Autónomos si la fuente es Radio Comercio normal)
            $categories = array($main_category_id);
            if ($category_name === 'Radio Comercio') {
                $categories[] = $autonomos_cat_id;
            }
            
            // Crear post
            $post_data = array(
                'post_title' => $title,
                'post_content' => $content,
                'post_excerpt' => $excerpt,
                'post_status' => 'publish',
                'post_type' => 'post',
                'post_date' => $date,
                'post_category' => $categories,
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'meta_input' => array(
                    '_radiocomercio_original_url' => $link,
                    '_radiocomercio_imported' => current_time('mysql'),
                    '_radiocomercio_post_id' => $original_id,
                    '_radiocomercio_source' => $source_name
                )
            );
            
            $new_post_id = wp_insert_post($post_data, true);
            
            if (is_wp_error($new_post_id)) {
                error_log('❌ CV Radio: Error importando "' . $title . '" - ' . $new_post_id->get_error_message());
                continue;
            }
            
            error_log('✅ CV Radio: Importado #' . $new_post_id . ' - ' . $title);
            
            // Descargar e importar imagen destacada
            if (isset($post['_embedded']['wp:featuredmedia'][0]['source_url'])) {
                $image_url = $post['_embedded']['wp:featuredmedia'][0]['source_url'];
                $attachment_id = $this->download_and_attach_image($image_url, $new_post_id, $title);
                
                if ($attachment_id) {
                    set_post_thumbnail($new_post_id, $attachment_id);
                    error_log('   🖼️ CV Radio: Imagen destacada configurada');
                }
            }
            
            $imported++;
        }
        
        return $imported;
    }
    
    /**
     * Obtener o crear categoría
     * 
     * @param string $category_name Nombre de la categoría
     * @return int ID de la categoría
     */
    private function get_or_create_category($category_name) {
        $category = get_term_by('name', $category_name, 'category');
        
        if ($category) {
            return $category->term_id;
        }
        
        $category_id = wp_create_category($category_name);
        error_log('📁 CV Radio: Categoría "' . $category_name . '" creada (ID: ' . $category_id . ')');
        
        return $category_id;
    }
    
    /**
     * Descargar imagen y adjuntarla a un post
     * 
     * @param string $image_url URL de la imagen
     * @param int $post_id ID del post
     * @param string $title Título para alt text
     * @return int|false ID del attachment o false
     */
    private function download_and_attach_image($image_url, $post_id, $title) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            error_log('❌ CV Radio: Error descargando imagen - ' . $tmp->get_error_message());
            return false;
        }
        
        $file_array = array(
            'name' => basename($image_url),
            'tmp_name' => $tmp
        );
        
        $attachment_id = media_handle_sideload($file_array, $post_id, $title);
        
        if (is_wp_error($attachment_id)) {
            @unlink($file_array['tmp_name']);
            error_log('❌ CV Radio: Error procesando imagen - ' . $attachment_id->get_error_message());
            return false;
        }
        
        return $attachment_id;
    }
    
    /**
     * Verificar si un post contiene alguna de las keywords (plural o singular)
     * Busca en TÍTULO y DESCRIPCIÓN CORTA
     */
    private function post_contains_keywords($post, $keywords) {
        $title = isset($post['title']['rendered']) ? strip_tags($post['title']['rendered']) : '';
        $excerpt = isset($post['excerpt']['rendered']) ? strip_tags($post['excerpt']['rendered']) : '';
        
        // Normalizar: quitar tildes y convertir a minúsculas
        $full_text = $this->remove_accents(strtolower($title . ' ' . $excerpt));
        
        // Para cada keyword, buscar en plural y singular
        foreach ($keywords as $keyword) {
            // Generar variaciones (singular/plural) y normalizarlas
            $variations = $this->generate_keyword_variations($keyword);
            
            foreach ($variations as $variation) {
                $variation_normalized = $this->remove_accents(strtolower($variation));
                if (strpos($full_text, $variation_normalized) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Quitar tildes de un texto
     */
    private function remove_accents($string) {
        $unwanted_array = array(
            'á'=>'a', 'Á'=>'a', 'à'=>'a', 'À'=>'a', 'ä'=>'a', 'Ä'=>'a',
            'é'=>'e', 'É'=>'e', 'è'=>'e', 'È'=>'e', 'ë'=>'e', 'Ë'=>'e',
            'í'=>'i', 'Í'=>'i', 'ì'=>'i', 'Ì'=>'i', 'ï'=>'i', 'Ï'=>'i',
            'ó'=>'o', 'Ó'=>'o', 'ò'=>'o', 'Ò'=>'o', 'ö'=>'o', 'Ö'=>'o',
            'ú'=>'u', 'Ú'=>'u', 'ù'=>'u', 'Ù'=>'u', 'ü'=>'u', 'Ü'=>'u',
            'ñ'=>'n', 'Ñ'=>'n'
        );
        return strtr($string, $unwanted_array);
    }
    
    /**
     * Generar variaciones de una keyword (plural/singular)
     */
    private function generate_keyword_variations($keyword) {
        $variations = array($keyword);
        
        // Variaciones específicas conocidas
        $custom_variations = array(
            'autónomo' => array('autónomos', 'autonomo', 'autonomos'),
            'autónomos' => array('autónomo', 'autonomo', 'autonomos'),
            'emprendedor' => array('emprendedores', 'emprendedora', 'emprendedoras'),
            'emprendedores' => array('emprendedor', 'emprendedora', 'emprendedoras'),
            'comercio' => array('comercios', 'comerciante', 'comerciantes', 'comercial', 'comerciales'),
            'ciudadvirtual' => array('ciudad virtual', 'ciudadvirtual.app', 'ciudad-virtual'),
        );
        
        if (isset($custom_variations[$keyword])) {
            $variations = array_merge($variations, $custom_variations[$keyword]);
        }
        
        // Reglas genéricas de plural/singular
        if (substr($keyword, -1) === 's') {
            $variations[] = substr($keyword, 0, -1);
        } else {
            $variations[] = $keyword . 's';
            $variations[] = $keyword . 'es';
        }
        
        return array_unique($variations);
    }
    
    /**
     * Filtrar posts importados de Radio Comercio por keywords
     * Solo afecta a posts con meta _radiocomercio_imported
     * Solo se aplica en páginas públicas (no admin)
     */
    public function filter_imported_posts_by_keywords($query) {
        // Solo en frontend y en la consulta principal
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Solo si es una consulta de posts (home, archivo, búsqueda, etc.)
        $post_type = $query->get('post_type');
        if ($post_type && $post_type !== 'post') {
            return;
        }
        
        // Obtener todos los posts importados de Radio Comercio
        global $wpdb;
        $imported_posts = $wpdb->get_col("
            SELECT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_radiocomercio_imported'
        ");
        
        if (empty($imported_posts)) {
            return;
        }
        
        // Keywords que deben estar presentes
        $keywords = array('autónomos', 'emprendedores', 'comercio', 'ciudadvirtual');
        
        // Verificar cada post importado
        $posts_to_hide = array();
        foreach ($imported_posts as $post_id) {
            $post = get_post($post_id);
            if (!$post) {
                continue;
            }
            
            // Crear objeto similar al de la API para reutilizar la función
            $post_data = array(
                'title' => array('rendered' => $post->post_title),
                'excerpt' => array('rendered' => $post->post_excerpt)
            );
            
            // Si NO contiene keywords en título o excerpt, ocultarlo
            if (!$this->post_contains_keywords($post_data, $keywords)) {
                $posts_to_hide[] = $post_id;
            }
        }
        
        // Excluir posts que no pasan el filtro
        if (!empty($posts_to_hide)) {
            $existing_exclude = $query->get('post__not_in');
            if (!is_array($existing_exclude)) {
                $existing_exclude = array();
            }
            $query->set('post__not_in', array_merge($existing_exclude, $posts_to_hide));
        }
    }
    
    /**
     * Establecer 6 posts por página en archivo de noticias
     */
    public function set_posts_per_page($query) {
        // Solo en frontend, en consulta principal
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Solo para tipo de post 'post'
        $post_type = $query->get('post_type');
        if ($post_type && $post_type !== 'post') {
            return;
        }
        
        // Aplicar en archivo, home y categorías
        if ($query->is_archive() || $query->is_home() || $query->is_category()) {
            $query->set('posts_per_page', 6);
            error_log('CV Radio: posts_per_page establecido a 6');
        }
    }
}

// Inicializar
new CV_RadioComercio_Auto_Import();

