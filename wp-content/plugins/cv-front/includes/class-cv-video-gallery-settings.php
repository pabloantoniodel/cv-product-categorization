<?php
/**
 * CV Video Gallery Settings
 *
 * Gestión completa de videos y shorts con títulos
 *
 * @package CV_Front
 * @since 2.5.5
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Video_Gallery_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_post_cv_save_videos', array($this, 'save_videos'));
        add_action('admin_post_cv_delete_video', array($this, 'delete_video'));
    }
    
    /**
     * Añadir página de configuración
     */
    public function add_settings_page() {
        add_options_page(
            'Gestión de Videos',
            'Galería de Videos',
            'manage_options',
            'cv-video-gallery-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Guardar videos
     */
    public function save_videos() {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos');
        }
        
        check_admin_referer('cv_save_videos');
        
        $videos = get_option('cv_video_gallery_custom_videos', array());
        
        // Añadir nuevo video
        if (isset($_POST['add_video']) && !empty($_POST['video_id'])) {
            $videos[] = array(
                'id' => sanitize_text_field($_POST['video_id']),
                'title' => sanitize_text_field($_POST['video_title']),
                'type' => sanitize_text_field($_POST['video_type'])
            );
        }
        
        // Actualizar videos existentes
        if (isset($_POST['update_videos']) && !empty($_POST['videos'])) {
            $videos = array();
            foreach ($_POST['videos'] as $video_data) {
                $videos[] = array(
                    'id' => sanitize_text_field($video_data['id']),
                    'title' => sanitize_text_field($video_data['title']),
                    'type' => sanitize_text_field($video_data['type'])
                );
            }
        }
        
        update_option('cv_video_gallery_custom_videos', $videos);
        
        // Limpiar caché
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cv_video_gallery_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_cv_video_gallery_%'");
        
        wp_redirect(add_query_arg('updated', 'true', wp_get_referer()));
        exit;
    }
    
    /**
     * Eliminar video
     */
    public function delete_video() {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos');
        }
        
        check_admin_referer('cv_delete_video_' . $_GET['video_id']);
        
        $videos = get_option('cv_video_gallery_custom_videos', array());
        $video_id = sanitize_text_field($_GET['video_id']);
        
        $videos = array_filter($videos, function($video) use ($video_id) {
            return $video['id'] !== $video_id;
        });
        
        update_option('cv_video_gallery_custom_videos', array_values($videos));
        
        // Limpiar caché
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cv_video_gallery_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_cv_video_gallery_%'");
        
        wp_redirect(add_query_arg('deleted', 'true', wp_get_referer()));
        exit;
    }
    
    /**
     * Renderizar página de configuración
     */
    public function render_settings_page() {
        $videos = get_option('cv_video_gallery_custom_videos', array());
        ?>
        <div class="wrap">
            <h1 style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                📺 Gestión de Galería de Videos
            </h1>
            
            <?php if (isset($_GET['updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>✅ Videos guardados correctamente.</strong> La caché se ha limpiado automáticamente.</p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>🗑️ Video eliminado correctamente.</strong></p>
                </div>
            <?php endif; ?>
            
            <!-- Formulario para añadir nuevo video -->
            <div style="background: #fff; padding: 25px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h2 style="margin-top: 0; color: #667eea;">➕ Añadir Nuevo Video/Short</h2>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('cv_save_videos'); ?>
                    <input type="hidden" name="action" value="cv_save_videos">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="video_id">ID del Video/Short</label></th>
                            <td>
                                <input type="text" id="video_id" name="video_id" class="regular-text" required placeholder="q_3JKEEaZe8">
                                <p class="description">El ID que aparece en la URL: youtube.com/watch?v=<strong>ID</strong> o youtube.com/shorts/<strong>ID</strong></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="video_title">Título/Descripción</label></th>
                            <td>
                                <input type="text" id="video_title" name="video_title" class="large-text" required placeholder="Tutorial: Cómo crear tu tarjeta">
                                <p class="description">El título que se mostrará en la galería</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="video_type">Tipo</label></th>
                            <td>
                                <select id="video_type" name="video_type">
                                    <option value="video">📹 Video</option>
                                    <option value="short">🩳 Short</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" name="add_video" class="button button-primary button-large">
                            ➕ Añadir Video
                        </button>
                    </p>
                </form>
            </div>
            
            <!-- Lista de videos actuales -->
            <div style="background: #fff; padding: 25px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h2 style="margin-top: 0; color: #667eea;">📋 Videos Configurados (<?php echo count($videos); ?>)</h2>
                
                <?php if (empty($videos)): ?>
                    <p style="color: #999; font-style: italic; text-align: center; padding: 40px;">
                        No hay videos configurados. Añade tu primer video arriba. 👆
                    </p>
                <?php else: ?>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <?php wp_nonce_field('cv_save_videos'); ?>
                        <input type="hidden" name="action" value="cv_save_videos">
                        
                        <div style="display: grid; gap: 15px;">
                            <?php foreach ($videos as $index => $video): ?>
                                <div style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 20px; border-radius: 8px; display: grid; grid-template-columns: 150px 1fr auto; gap: 20px; align-items: center;">
                                    <!-- Miniatura -->
                                    <div>
                                        <img src="https://i.ytimg.com/vi/<?php echo esc_attr($video['id']); ?>/hqdefault.jpg" 
                                             style="width: 100%; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                                    </div>
                                    
                                    <!-- Campos editables -->
                                    <div>
                                        <div style="margin-bottom: 10px;">
                                            <label style="font-weight: bold; display: block; margin-bottom: 5px;">
                                                <?php echo $video['type'] === 'short' ? '🩳' : '📹'; ?> Tipo:
                                            </label>
                                            <select name="videos[<?php echo $index; ?>][type]" style="width: 120px;">
                                                <option value="video" <?php selected($video['type'], 'video'); ?>>📹 Video</option>
                                                <option value="short" <?php selected($video['type'], 'short'); ?>>🩳 Short</option>
                                            </select>
                                        </div>
                                        
                                        <div style="margin-bottom: 10px;">
                                            <label style="font-weight: bold; display: block; margin-bottom: 5px;">ID:</label>
                                            <input type="text" name="videos[<?php echo $index; ?>][id]" 
                                                   value="<?php echo esc_attr($video['id']); ?>" 
                                                   class="regular-text" readonly style="background: #e9ecef;">
                                        </div>
                                        
                                        <div>
                                            <label style="font-weight: bold; display: block; margin-bottom: 5px;">Título:</label>
                                            <input type="text" name="videos[<?php echo $index; ?>][title]" 
                                                   value="<?php echo esc_attr($video['title']); ?>" 
                                                   class="large-text" required>
                                        </div>
                                    </div>
                                    
                                    <!-- Botón eliminar -->
                                    <div style="text-align: center;">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=cv_delete_video&video_id=' . urlencode($video['id'])), 'cv_delete_video_' . $video['id']); ?>" 
                                           class="button button-secondary" 
                                           onclick="return confirm('¿Eliminar este video?');"
                                           style="background: #dc3545; color: white; border: none; height: 40px; padding: 0 20px;">
                                            🗑️ Eliminar
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <p class="submit" style="margin-top: 20px;">
                            <button type="submit" name="update_videos" class="button button-primary button-large">
                                💾 Guardar Todos los Cambios
                            </button>
                        </p>
                    </form>
                <?php endif; ?>
            </div>
            
            <div style="background: #f0f9ff; border: 1px solid #667eea; padding: 20px; margin-top: 30px; border-radius: 8px;">
                <h3 style="margin-top: 0; color: #667eea;">💡 Instrucciones:</h3>
                <ul style="line-height: 1.8;">
                    <li><strong>Añadir:</strong> Usa el formulario de arriba para añadir nuevos videos o shorts</li>
                    <li><strong>Editar:</strong> Modifica el título de cualquier video y guarda los cambios</li>
                    <li><strong>Eliminar:</strong> Haz clic en el botón rojo "🗑️ Eliminar"</li>
                    <li><strong>Orden:</strong> Los videos aparecen en el orden que los añadiste</li>
                </ul>
            </div>
        </div>
        <?php
    }
}

new CV_Video_Gallery_Settings();

