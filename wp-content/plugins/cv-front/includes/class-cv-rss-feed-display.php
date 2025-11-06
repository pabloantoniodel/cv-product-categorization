<?php
/**
 * Visualizador de RSS Feed
 * 
 * Muestra entradas de feeds RSS externos en formato moderno
 * 
 * @package CV_Front
 * @since 2.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_RSS_Feed_Display {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('cv_rss_feed', array($this, 'render_rss_feed'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Encolar scripts y estilos
     */
    public function enqueue_scripts() {
        if (is_page() && has_shortcode(get_post()->post_content, 'cv_rss_feed')) {
            wp_enqueue_style(
                'cv-rss-feed',
                plugins_url('assets/css/rss-feed.css', dirname(__FILE__)),
                array(),
                '2.7.0'
            );
            
            wp_enqueue_script(
                'cv-rss-feed',
                plugins_url('assets/js/rss-feed.js', dirname(__FILE__)),
                array('jquery'),
                '2.7.0',
                true
            );
        }
    }
    
    /**
     * Renderizar feed RSS
     * 
     * @param array $atts Atributos del shortcode
     * @return string HTML
     */
    public function render_rss_feed($atts) {
        $atts = shortcode_atts(array(
            'url' => 'https://radiocomercio.com/feed',
            'limit' => 20,
            'cache_time' => 3600, // 1 hora
            'title' => 'Últimas Noticias',
            'show_date' => 'yes',
            'show_excerpt' => 'yes',
            'show_image' => 'yes',
            'excerpt_length' => 150,
            'layout' => 'grid' // grid, list, cards
        ), $atts);
        
        // Obtener feed desde cache o fetchear nuevo
        $cache_key = 'cv_rss_feed_' . md5($atts['url'] . $atts['limit']);
        $feed_items = get_transient($cache_key);
        
        if ($feed_items === false) {
            error_log('🔄 CV RSS: Fetching feed from ' . $atts['url']);
            $feed_items = $this->fetch_rss_feed($atts['url'], intval($atts['limit']));
            
            if (!empty($feed_items)) {
                set_transient($cache_key, $feed_items, intval($atts['cache_time']));
                error_log('✅ CV RSS: Feed cacheado - ' . count($feed_items) . ' items');
            }
        } else {
            error_log('📦 CV RSS: Feed desde cache - ' . count($feed_items) . ' items');
        }
        
        if (empty($feed_items)) {
            return '<div class="cv-rss-error"><p>No se pudieron cargar las noticias en este momento.</p></div>';
        }
        
        ob_start();
        ?>
        <div class="cv-rss-container" data-layout="<?php echo esc_attr($atts['layout']); ?>">
            
            <?php if (!empty($atts['title'])): ?>
            <div class="cv-rss-header">
                <h2 class="cv-rss-title">
                    <i class="fas fa-rss"></i>
                    <?php echo esc_html($atts['title']); ?>
                </h2>
                <div class="cv-rss-count">
                    <?php echo count($feed_items); ?> artículos
                </div>
            </div>
            <?php endif; ?>
            
            <div class="cv-rss-grid cv-rss-<?php echo esc_attr($atts['layout']); ?>">
                <?php foreach ($feed_items as $item): ?>
                <article class="cv-rss-item">
                    
                    <?php if ($atts['show_image'] === 'yes' && !empty($item['image'])): ?>
                    <div class="cv-rss-image">
                        <a href="<?php echo esc_url($item['link']); ?>" target="_blank" rel="noopener">
                            <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title']); ?>" loading="lazy">
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <div class="cv-rss-content">
                        
                        <?php if ($atts['show_date'] === 'yes' && !empty($item['date'])): ?>
                        <div class="cv-rss-meta">
                            <i class="far fa-calendar-alt"></i>
                            <time datetime="<?php echo esc_attr($item['date_iso']); ?>">
                                <?php echo esc_html($item['date']); ?>
                            </time>
                            <?php if (!empty($item['author'])): ?>
                            <span class="cv-rss-author">
                                <i class="far fa-user"></i>
                                <?php echo esc_html($item['author']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <h3 class="cv-rss-item-title">
                            <a href="<?php echo esc_url($item['link']); ?>" target="_blank" rel="noopener">
                                <?php echo esc_html($item['title']); ?>
                            </a>
                        </h3>
                        
                        <?php if ($atts['show_excerpt'] === 'yes' && !empty($item['excerpt'])): ?>
                        <div class="cv-rss-excerpt">
                            <?php echo wp_kses_post($item['excerpt']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($item['categories'])): ?>
                        <div class="cv-rss-categories">
                            <?php foreach (array_slice($item['categories'], 0, 3) as $category): ?>
                            <span class="cv-rss-category">
                                <i class="fas fa-tag"></i>
                                <?php echo esc_html($category); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="cv-rss-footer">
                            <a href="<?php echo esc_url($item['link']); ?>" target="_blank" rel="noopener" class="cv-rss-read-more">
                                Leer más
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        
                    </div>
                    
                </article>
                <?php endforeach; ?>
            </div>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Obtener items del feed RSS
     * 
     * @param string $feed_url URL del feed
     * @param int $limit Número de items a obtener
     * @return array Array de items
     */
    private function fetch_rss_feed($feed_url, $limit = 20) {
        // Intentar usar API REST de WordPress si el feed lo soporta
        $wp_json_url = $this->convert_feed_to_api_url($feed_url, $limit);
        
        if ($wp_json_url) {
            error_log('🔄 CV RSS: Usando WP REST API: ' . $wp_json_url);
            $api_items = $this->fetch_from_wp_api($wp_json_url);
            if (!empty($api_items)) {
                return $api_items;
            }
        }
        
        // Fallback a RSS tradicional
        error_log('🔄 CV RSS: Usando RSS Feed: ' . $feed_url);
        
        include_once(ABSPATH . WPINC . '/feed.php');
        
        $rss = fetch_feed($feed_url);
        
        if (is_wp_error($rss)) {
            error_log('❌ CV RSS: Error fetching feed - ' . $rss->get_error_message());
            return array();
        }
        
        $maxitems = $rss->get_item_quantity($limit);
        error_log('🔍 CV RSS: Solicitados ' . $limit . ' items, obtenidos: ' . $maxitems);
        
        $rss_items = $rss->get_items(0, $maxitems);
        
        $items = array();
        
        foreach ($rss_items as $item) {
            // Obtener imagen del contenido
            $image = $this->extract_image_from_content($item);
            
            // Obtener excerpt
            $excerpt = $this->get_excerpt($item);
            
            // Obtener categorías
            $categories = array();
            if ($cats = $item->get_categories()) {
                foreach ($cats as $cat) {
                    $categories[] = $cat->get_label();
                }
            }
            
            // Obtener autor
            $author = '';
            if ($author_obj = $item->get_author()) {
                $author = $author_obj->get_name();
            }
            
            // Obtener fecha
            $date = '';
            $date_iso = '';
            if ($item->get_date()) {
                $date = $item->get_date('d/m/Y');
                $date_iso = $item->get_date('c');
            }
            
            $items[] = array(
                'title' => $item->get_title(),
                'link' => $item->get_permalink(),
                'excerpt' => $excerpt,
                'content' => $item->get_content(),
                'date' => $date,
                'date_iso' => $date_iso,
                'author' => $author,
                'image' => $image,
                'categories' => $categories
            );
        }
        
        return $items;
    }
    
    /**
     * Extraer imagen del contenido del feed
     * 
     * @param object $item Item del feed
     * @return string URL de la imagen o vacío
     */
    private function extract_image_from_content($item) {
        // Intentar obtener imagen de enclosure
        if ($enclosure = $item->get_enclosure()) {
            if ($enclosure->get_thumbnail()) {
                return $enclosure->get_thumbnail();
            }
            if ($enclosure->get_link() && strpos($enclosure->get_type(), 'image') !== false) {
                return $enclosure->get_link();
            }
        }
        
        // Intentar extraer imagen del contenido
        $content = $item->get_content();
        if (preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches)) {
            return $matches[1];
        }
        
        // Intentar extraer de media:thumbnail
        if (method_exists($item, 'get_item_tags')) {
            $media = $item->get_item_tags('http://search.yahoo.com/mrss/', 'thumbnail');
            if (isset($media[0]['attribs']['']['url'])) {
                return $media[0]['attribs']['']['url'];
            }
        }
        
        return '';
    }
    
    /**
     * Obtener excerpt del item
     * 
     * @param object $item Item del feed
     * @return string Excerpt
     */
    private function get_excerpt($item) {
        $excerpt = '';
        
        // Prioridad 1: Usar contenido completo (content:encoded)
        if ($item->get_content()) {
            $excerpt = $item->get_content();
        }
        // Prioridad 2: Usar description si no hay contenido
        else if ($item->get_description()) {
            $excerpt = $item->get_description();
        }
        
        // Limpiar HTML pero mantener algunos tags básicos
        $excerpt = strip_tags($excerpt, '<p><br><strong><em><ul><ol><li><h1><h2><h3><h4><a>');
        
        // Limitar longitud si es muy largo
        if (strlen($excerpt) > 500) {
            $excerpt = wp_trim_words($excerpt, 50, '...');
        }
        
        return $excerpt;
    }
    
    /**
     * Convertir URL de feed RSS a URL de API REST de WordPress
     * 
     * @param string $feed_url URL del feed RSS
     * @param int $limit Límite de posts
     * @return string|false URL de la API o false
     */
    private function convert_feed_to_api_url($feed_url, $limit) {
        // Extraer dominio base del feed
        $parsed = parse_url($feed_url);
        if (!$parsed || !isset($parsed['host'])) {
            return false;
        }
        
        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] : 'https';
        $base_url = $scheme . '://' . $parsed['host'];
        
        // Construir URL de API REST
        $api_url = $base_url . '/wp-json/wp/v2/posts?per_page=' . $limit . '&_embed';
        
        return $api_url;
    }
    
    /**
     * Obtener posts desde WordPress REST API
     * 
     * @param string $api_url URL de la API
     * @return array Array de items
     */
    private function fetch_from_wp_api($api_url) {
        $response = wp_remote_get($api_url, array(
            'timeout' => 15,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            error_log('❌ CV RSS API: Error - ' . $response->get_error_message());
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $posts = json_decode($body, true);
        
        if (!is_array($posts)) {
            error_log('❌ CV RSS API: Respuesta no es array');
            return array();
        }
        
        error_log('✅ CV RSS API: Obtenidos ' . count($posts) . ' posts');
        
        $items = array();
        
        foreach ($posts as $post) {
            // Extraer imagen destacada
            $image = '';
            if (isset($post['_embedded']['wp:featuredmedia'][0]['source_url'])) {
                $image = $post['_embedded']['wp:featuredmedia'][0]['source_url'];
            }
            
            // Extraer categorías
            $categories = array();
            if (isset($post['_embedded']['wp:term'][0])) {
                foreach ($post['_embedded']['wp:term'][0] as $term) {
                    $categories[] = $term['name'];
                }
            }
            
            // Extraer autor
            $author = '';
            if (isset($post['_embedded']['author'][0]['name'])) {
                $author = $post['_embedded']['author'][0]['name'];
            }
            
            // Procesar contenido
            $content = isset($post['content']['rendered']) ? $post['content']['rendered'] : '';
            $excerpt = strip_tags($content, '<p><br><strong><em><ul><ol><li><h1><h2><h3><h4><a>');
            
            // Limitar longitud
            if (strlen($excerpt) > 500) {
                $excerpt = wp_trim_words($excerpt, 50, '...');
            }
            
            $items[] = array(
                'title' => isset($post['title']['rendered']) ? $post['title']['rendered'] : '',
                'link' => isset($post['link']) ? $post['link'] : '',
                'excerpt' => $excerpt,
                'content' => $content,
                'date' => isset($post['date']) ? date('d/m/Y', strtotime($post['date'])) : '',
                'date_iso' => isset($post['date']) ? $post['date'] : '',
                'author' => $author,
                'image' => $image,
                'categories' => $categories
            );
        }
        
        return $items;
    }
}

// Inicializar
new CV_RSS_Feed_Display();

