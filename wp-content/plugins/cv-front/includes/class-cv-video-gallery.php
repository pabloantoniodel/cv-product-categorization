<?php
/**
 * CV Video Gallery
 *
 * Shortcode para mostrar galería de videos de YouTube con miniaturas
 *
 * @package CV_Front
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Video_Gallery {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('cv-video-gallery', array($this, 'render_gallery'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Encolar CSS y JS
     */
    public function enqueue_assets() {
        // Solo encolar si el shortcode está presente
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'cv-video-gallery')) {
            wp_enqueue_style(
                'cv-video-gallery',
                CV_FRONT_PLUGIN_URL . 'assets/css/video-gallery.css',
                array(),
                CV_FRONT_VERSION
            );
            
            wp_enqueue_script(
                'cv-video-gallery',
                CV_FRONT_PLUGIN_URL . 'assets/js/video-gallery.js',
                array('jquery'),
                CV_FRONT_VERSION,
                true
            );
        }
    }
    
    /**
     * Renderizar galería de videos
     * 
     * Uso manual: [cv-video-gallery videos="ID1,ID2,ID3" titles="Título 1,Título 2,Título 3"]
     * Uso con canal: [cv-video-gallery channel="@TuCanal" max="12"]
     * Uso con playlist: [cv-video-gallery playlist="PLxxxxxxxxx" max="12"]
     * 
     * @param array $atts Atributos del shortcode
     * @return string HTML de la galería
     */
    public function render_gallery($atts) {
        $atts = shortcode_atts(array(
            'videos' => '',
            'titles' => '',
            'channel' => '',
            'playlist' => '',
            'max' => '12',
            'columns' => '3', // 2, 3, o 4 columnas
        ), $atts);
        
        $videos_data = array();
        
        // Opción 1: Videos desde canal de YouTube
        if (!empty($atts['channel'])) {
            $videos_data = $this->get_channel_videos($atts['channel'], intval($atts['max']));
        }
        // Opción 2: Videos desde playlist
        elseif (!empty($atts['playlist'])) {
            $videos_data = $this->get_playlist_videos($atts['playlist'], intval($atts['max']));
        }
        // Opción 3: Videos manuales
        elseif (!empty($atts['videos'])) {
            $video_ids = array_map('trim', explode(',', $atts['videos']));
            $titles = !empty($atts['titles']) ? array_map('trim', explode(',', $atts['titles'])) : array();
            
            foreach ($video_ids as $index => $video_id) {
                $videos_data[] = array(
                    'id' => $video_id,
                    'title' => isset($titles[$index]) ? $titles[$index] : 'Video Tutorial ' . ($index + 1)
                );
            }
        } else {
            return '<p style="color: red;">Error: Debes especificar "channel", "playlist" o "videos". Ejemplos:<br>
                    [cv-video-gallery channel="@TuCanal"]<br>
                    [cv-video-gallery playlist="PLxxxxxxxxx"]<br>
                    [cv-video-gallery videos="ID1,ID2,ID3"]</p>';
        }
        
        if (empty($videos_data)) {
            return '<p style="color: orange;">No se encontraron videos para mostrar.</p>';
        }
        
        $columns = intval($atts['columns']);
        if ($columns < 2) $columns = 2;
        if ($columns > 4) $columns = 4;
        
        ob_start();
        ?>
        
        <div class="cv-video-gallery" data-columns="<?php echo esc_attr($columns); ?>">
            <div class="cv-video-grid cv-video-grid-<?php echo esc_attr($columns); ?>">
                <?php foreach ($videos_data as $video): ?>
                    <?php
                    $video_id = $video['id'];
                    $title = $video['title'];
                    $thumbnail_url = "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg";
                    ?>
                    
                    <div class="cv-video-item" data-video-id="<?php echo esc_attr($video_id); ?>">
                        <div class="cv-video-thumbnail">
                            <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
                            <div class="cv-video-play-overlay">
                                <svg class="cv-video-play-icon" viewBox="0 0 68 48" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M66.52,7.74c-0.78-2.93-2.49-5.41-5.42-6.19C55.79,.13,34,0,34,0S12.21,.13,6.9,1.55 C3.97,2.33,2.27,4.81,1.48,7.74C0.06,13.05,0,24,0,24s0.06,10.95,1.48,16.26c0.78,2.93,2.49,5.41,5.42,6.19 C12.21,47.87,34,48,34,48s21.79-0.13,27.1-1.55c2.93-0.78,4.64-3.26,5.42-6.19C67.94,34.95,68,24,68,24S67.94,13.05,66.52,7.74z" fill="#f00"></path>
                                    <path d="M 45,24 27,14 27,34" fill="#fff"></path>
                                </svg>
                            </div>
                            <div class="cv-video-duration">Ver tutorial</div>
                        </div>
                        <div class="cv-video-info">
                            <h3 class="cv-video-title"><?php echo esc_html($title); ?></h3>
                            <button class="cv-video-watch-btn" data-video-id="<?php echo esc_attr($video_id); ?>">
                                ▶ Ver ahora
                            </button>
                        </div>
                    </div>
                    
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Modal para reproducir video -->
        <div id="cv-video-modal" class="cv-video-modal">
            <div class="cv-video-modal-overlay"></div>
            <div class="cv-video-modal-content">
                <button class="cv-video-modal-close" aria-label="Cerrar">✕</button>
                <div class="cv-video-player-wrapper">
                    <div id="cv-video-player"></div>
                </div>
            </div>
        </div>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Obtener videos de un canal de YouTube
     * Usa el feed RSS de YouTube que no requiere API key
     * 
     * @param string $channel ID del canal o @username
     * @param int $max Número máximo de videos
     * @return array Array de videos con id y title
     */
    private function get_channel_videos($channel, $max = 12) {
        // Primero, verificar si hay videos configurados manualmente
        $custom_videos = get_option('cv_video_gallery_custom_videos', array());
        
        if (!empty($custom_videos)) {
            // Usar videos configurados manualmente
            $videos = array();
            foreach ($custom_videos as $video) {
                $title = $video['title'];
                if ($video['type'] === 'short' && strpos($title, '🩳') === false) {
                    $title .= ' 🩳';
                }
                
                $videos[] = array(
                    'id' => $video['id'],
                    'title' => $title
                );
            }
            
            return array_slice($videos, 0, $max);
        }
        
        // Si no hay videos configurados, usar el método automático
        // Quitar @ si viene con él
        $channel = ltrim($channel, '@');
        
        // Intentar obtener del caché (30 minutos)
        $cache_key = 'cv_video_gallery_channel_' . md5($channel);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return array_slice($cached, 0, $max);
        }
        
        $videos = array();
        $channel_id = $channel;
        
        // Si no empieza con UC, probablemente es un @username, intentar obtener el channel ID
        if (substr($channel, 0, 2) !== 'UC') {
            $feed_url = "https://www.youtube.com/@{$channel}/videos";
            $response = wp_remote_get($feed_url, array(
                'timeout' => 15,
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ));
            
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                
                // Intentar extraer el channel ID de varias formas
                if (preg_match('/"channelId":"([^"]+)"/', $body, $matches)) {
                    $channel_id = $matches[1];
                } elseif (preg_match('/channel_id=([A-Za-z0-9_-]+)/', $body, $matches)) {
                    $channel_id = $matches[1];
                } elseif (preg_match('/"externalId":"([^"]+)"/', $body, $matches)) {
                    $channel_id = $matches[1];
                }
            }
        }
        
        // Obtener videos regulares del feed RSS
        $rss_url = "https://www.youtube.com/feeds/videos.xml?channel_id={$channel_id}";
        $rss_response = wp_remote_get($rss_url, array(
            'timeout' => 15,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ));
        
        if (!is_wp_error($rss_response)) {
            $rss_body = wp_remote_retrieve_body($rss_response);
            
            // Parsear XML
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($rss_body);
            
            if ($xml && isset($xml->entry)) {
                foreach ($xml->entry as $entry) {
                    // Extraer video ID del link
                    $video_url = (string)$entry->link['href'];
                    if (preg_match('/v=([^&]+)/', $video_url, $id_matches)) {
                        $videos[] = array(
                            'id' => $id_matches[1],
                            'title' => (string)$entry->title
                        );
                    }
                    
                    if (count($videos) >= $max) {
                        break;
                    }
                }
            }
            libxml_clear_errors();
        }
        
        // Añadir Shorts configurados manualmente en opciones
        $configured_shorts = get_option('cv_video_gallery_shorts', '');
        if (!empty($configured_shorts) && count($videos) < $max) {
            $shorts_ids = array_map('trim', explode(',', $configured_shorts));
            $seen_ids = array_column($videos, 'id');
            
            foreach ($shorts_ids as $short_id) {
                if (!empty($short_id) && !in_array($short_id, $seen_ids)) {
                    $videos[] = array(
                        'id' => $short_id,
                        'title' => 'Short Tutorial 🩳'
                    );
                    
                    if (count($videos) >= $max) {
                        break;
                    }
                }
            }
        }
        
        // Si aún no hay suficientes videos, intentar scraping de Shorts
        if (count($videos) < $max) {
            // Intentar múltiples URLs para shorts
            $shorts_urls = array(
                "https://www.youtube.com/channel/{$channel_id}/shorts",
                "https://www.youtube.com/@{$channel}/shorts",
            );
            
            $shorts_found = array();
            $seen_ids = array_column($videos, 'id');
            
            foreach ($shorts_urls as $shorts_url) {
                $shorts_response = wp_remote_get($shorts_url, array(
                    'timeout' => 20,
                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'headers' => array(
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                        'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
                    )
                ));
                
                if (!is_wp_error($shorts_response)) {
                    $shorts_body = wp_remote_retrieve_body($shorts_response);
                    
                    // Múltiples patrones para encontrar shorts
                    $patterns = array(
                        '/"videoId":"([A-Za-z0-9_-]{11})"/',
                        '/\/shorts\/([A-Za-z0-9_-]{11})/',
                        '/"url":"\/shorts\/([A-Za-z0-9_-]{11})"/',
                        '/watch\?v=([A-Za-z0-9_-]{11})/',
                    );
                    
                    foreach ($patterns as $pattern) {
                        preg_match_all($pattern, $shorts_body, $matches);
                        if (!empty($matches[1])) {
                            foreach ($matches[1] as $video_id) {
                                if (!in_array($video_id, $seen_ids) && strlen($video_id) === 11 && !isset($shorts_found[$video_id])) {
                                    $shorts_found[$video_id] = true;
                                }
                            }
                        }
                    }
                    
                    // Si encontramos shorts, salir del bucle
                    if (!empty($shorts_found)) {
                        break;
                    }
                }
            }
            
            // Procesar los shorts encontrados
            if (!empty($shorts_found)) {
                foreach (array_keys($shorts_found) as $short_id) {
                    // Intentar obtener el título del Short
                    $title = 'Short Tutorial';
                    
                    // Intentar varios patrones para el título
                    $title_patterns = array(
                        '/"videoId":"' . preg_quote($short_id, '/') . '"[^}]*"title"[^}]*"text":"([^"]+)"/s',
                        '/"title":"([^"]*)"[^}]*"videoId":"' . preg_quote($short_id, '/') . '"/s',
                        '/data-title="([^"]*)"[^>]*href="\/shorts\/' . preg_quote($short_id, '/') . '"/s',
                    );
                    
                    foreach ($title_patterns as $title_pattern) {
                        if (preg_match($title_pattern, $shorts_body, $title_match)) {
                            $title = html_entity_decode($title_match[1], ENT_QUOTES, 'UTF-8');
                            break;
                        }
                    }
                    
                    $videos[] = array(
                        'id' => $short_id,
                        'title' => $title . ' 🩳'
                    );
                    
                    if (count($videos) >= $max) {
                        break;
                    }
                }
            }
        }
        
        // Guardar en caché por 30 minutos
        if (!empty($videos)) {
            set_transient($cache_key, $videos, 30 * MINUTE_IN_SECONDS);
        }
        
        return $videos;
    }
    
    /**
     * Obtener videos de una playlist de YouTube
     * 
     * @param string $playlist_id ID de la playlist
     * @param int $max Número máximo de videos
     * @return array Array de videos con id y title
     */
    private function get_playlist_videos($playlist_id, $max = 12) {
        // Intentar obtener del caché (30 minutos)
        $cache_key = 'cv_video_gallery_playlist_' . md5($playlist_id);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return array_slice($cached, 0, $max);
        }
        
        $videos = array();
        
        // Usar oEmbed para obtener información básica
        // Alternativamente, usar scraping del HTML de la playlist
        $playlist_url = "https://www.youtube.com/playlist?list={$playlist_id}";
        $response = wp_remote_get($playlist_url, array('timeout' => 15));
        
        if (is_wp_error($response)) {
            return $videos;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        // Extraer videos del HTML usando regex
        preg_match_all('/"videoId":"([^"]+)".*?"title":{"runs":\[{"text":"([^"]+)"}/', $body, $matches, PREG_SET_ORDER);
        
        $unique_videos = array();
        foreach ($matches as $match) {
            $video_id = $match[1];
            
            // Evitar duplicados
            if (!isset($unique_videos[$video_id])) {
                $unique_videos[$video_id] = array(
                    'id' => $video_id,
                    'title' => html_entity_decode($match[2], ENT_QUOTES, 'UTF-8')
                );
                
                if (count($unique_videos) >= $max) {
                    break;
                }
            }
        }
        
        $videos = array_values($unique_videos);
        
        // Guardar en caché por 30 minutos
        if (!empty($videos)) {
            set_transient($cache_key, $videos, 30 * MINUTE_IN_SECONDS);
        }
        
        return $videos;
    }
}

new CV_Video_Gallery();

