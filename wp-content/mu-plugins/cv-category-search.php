<?php
/**
 * Plugin Name: CV - Categorizador Modal WCFM
 * Description: Busca en MÚLTIPLES fuentes (checklist + ocultas + select).
 * Version: 3.2.0
 * Author: Ciudad Virtual
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Category_Modal {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Usar wp_footer para WCFM (frontend)
        add_action('wp_footer', array($this, 'enqueue_scripts'), 999);
        add_action('wp_footer', array($this, 'render_modal'), 999);
        
        // Endpoint AJAX para recibir logs desde JavaScript
        add_action('wp_ajax_cv_category_search_log', array($this, 'ajax_log'));
        add_action('wp_ajax_nopriv_cv_category_search_log', array($this, 'ajax_log'));
    }
    
    /**
     * Recibe logs desde JavaScript y los guarda en archivo del servidor
     */
    public function ajax_log() {
        if (!isset($_POST['message']) || !isset($_POST['nonce'])) {
            wp_send_json_error('Missing parameters');
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'cv_category_search_log')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $message = sanitize_text_field($_POST['message']);
        $level = isset($_POST['level']) ? sanitize_text_field($_POST['level']) : 'INFO';
        $user_id = get_current_user_id();
        $user_login = $user_id ? get_userdata($user_id)->user_login : 'guest';
        
        // Guardar en archivo de log
        $log_file = WP_CONTENT_DIR . '/cv-category-search-debug.log';
        $timestamp = current_time('Y-m-d H:i:s');
        $log_entry = sprintf(
            "[%s] [%s] User: %s (ID: %d) - %s\n",
            $timestamp,
            $level,
            $user_login,
            $user_id,
            $message
        );
        
        // Escribir en el archivo (append)
        file_put_contents($log_file, $log_entry, FILE_APPEND);
        
        wp_send_json_success('Logged');
    }
    
    public function enqueue_scripts() {
        if (!$this->is_wcfm_products_page()) {
            return;
        }
        
        echo '<style>' . $this->get_css() . '</style>';
        echo '<script type="text/javascript">' . $this->get_js() . '</script>';
    }
    
    private function is_wcfm_products_page() {
        if (isset($_GET['page']) && $_GET['page'] === 'wcfm-products-manage') {
            return true;
        }
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'products-manage') !== false) {
            return true;
        }
        return false;
    }
    
    public function render_modal() {
        if (!$this->is_wcfm_products_page()) {
            return;
        }
        ?>
        <!-- MODAL DE CATEGORIZACIÓN -->
        <div id="cv-category-modal" style="display:none;">
            <div class="cv-modal-overlay"></div>
            <div class="cv-modal-container">
                <div class="cv-modal-header">
                    <h2>🏷️ Categorizar Producto</h2>
                    <button class="cv-modal-close" id="cv-modal-close">&times;</button>
                </div>
                
                <div class="cv-modal-body">
                    <!-- BUSCADOR -->
                    <div class="cv-search-section">
                        <h3>🔍 Buscar Categorías</h3>
                        <input type="text" id="cv-category-search-input" placeholder="Escribe 2 letras para buscar..." autocomplete="off">
                        <div id="cv-search-results"></div>
                        <div id="cv-search-help">💡 Escribe al menos 2 letras para buscar</div>
                    </div>
                    
                    <!-- CATEGORÍAS SELECCIONADAS -->
                    <div class="cv-selected-section">
                        <h3>✅ Categorías Seleccionadas (<span id="cv-selected-count">0</span>)</h3>
                        <div id="cv-selected-categories"></div>
                    </div>
                </div>
                
                <div class="cv-modal-footer">
                    <button class="cv-btn cv-btn-secondary" id="cv-modal-cancel">Cancelar</button>
                    <button class="cv-btn cv-btn-primary" id="cv-modal-save">Guardar y Cerrar</button>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function get_css() {
        return "
        /* OCULTAR SELECTOR ORIGINAL */
        .wcfm_product_manager_cats_checklist_fields {
            display: none !important;
        }
        
        /* BOTÓN CATEGORIZAR */
        .cv-categorize-button {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
            margin: 20px 0;
        }
        .cv-categorize-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .cv-categorize-button .cv-badge {
            display: inline-block;
            background: rgba(255,255,255,0.3);
            padding: 2px 8px;
            border-radius: 12px;
            margin-left: 8px;
            font-size: 13px;
        }
        
        /* MODAL */
        #cv-category-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 999999;
        }
        .cv-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
        }
        .cv-modal-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
        }
        .cv-modal-header {
            padding: 20px 30px;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }
        .cv-modal-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .cv-modal-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 32px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        .cv-modal-close:hover {
            background: rgba(255,255,255,0.3);
        }
        .cv-modal-body {
            padding: 30px;
            overflow-y: auto;
            flex: 1;
        }
        .cv-search-section {
            margin-bottom: 30px;
        }
        .cv-search-section h3 {
            margin: 0 0 15px 0;
            font-size: 18px;
            color: #333;
        }
        #cv-category-search-input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        #cv-category-search-input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        #cv-search-results {
            margin-top: 15px;
            max-height: 300px;
            overflow-y: auto;
        }
        .cv-search-result-item {
            padding: 12px 15px;
            margin: 8px 0;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cv-search-result-text {
            flex: 1;
            margin-right: 12px;
        }
        .cv-search-result-text mark {
            background: #fff3b0;
            color: inherit;
            padding: 0 2px;
            border-radius: 3px;
        }
        .cv-search-result-item:hover {
            background: #e7f3ff;
            border-color: #667eea;
            transform: translateX(5px);
        }
        .cv-search-result-item.selected {
            background: #d4edda;
            border-color: #28a745;
        }
        #cv-search-help {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 14px;
            color: #666;
            text-align: center;
        }
        .cv-selected-section {
            border-top: 2px solid #e9ecef;
            padding-top: 20px;
        }
        .cv-selected-section h3 {
            margin: 0 0 15px 0;
            font-size: 18px;
            color: #333;
        }
        #cv-selected-categories {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .cv-selected-tag {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .cv-selected-tag-remove {
            margin-left: 8px;
            background: rgba(255,255,255,0.3);
            border: none;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cv-selected-tag-remove:hover {
            background: rgba(255,255,255,0.5);
        }
        .cv-modal-footer {
            padding: 20px 30px;
            border-top: 2px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }
        .cv-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .cv-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .cv-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        .cv-btn-secondary {
            background: #6c757d;
            color: white;
        }
        .cv-btn-secondary:hover {
            background: #5a6268;
        }
        ";
    }
    
    private function get_js() {
        return "
        (function() {
            function isProductsPage() {
                var href = window.location.href || '';
                if (href.indexOf('products-manage') !== -1 || href.indexOf('wcfm-products') !== -1) {
                    return true;
                }
                if (document.querySelector('#product_cats_checklist') || document.querySelector('.wcfm_product_manager_cats_checklist_fields')) {
                    return true;
                }
                return false;
            }

            jQuery(document).ready(function($) {
                if (!isProductsPage()) {
                    console.log('⏭️ CV Category Modal: No es la página de gestión de productos, saliendo');
                    return;
                }

                if ($('#cv-open-modal').length) {
                    console.log('⛔ CV Category Modal: Botón ya existe, no se duplica');
                    return;
                }

                console.log('🚀 CV Category Modal v3.1.0 - Inicializando...');
                console.log('✅ jQuery ready - v3.1.0');
                console.log('📍 URL:', window.location.href);
                
                var allCategories = [];
                var selectedCategories = [];

		function normalizeString(str) {
			if (!str) return '';
			return str.toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
		}

		function escapeRegExp(string) {
			return string.replace(/[.*+?^\${}()|\[\]\\\/]/g, '\\$&');
		}

		function highlightQuery(text, query) {
			if (!text || !query || query.length < 2) {
				return text || '';
			}
			try {
				var regex = new RegExp(escapeRegExp(query), 'gi');
				return text.replace(regex, '<mark>$&</mark>');
			} catch (error) {
				return text;
			}
		}
                
                // Esperar a que el contenedor de categorías exista
                var initAttempts = 0;
                function init() {
                    initAttempts++;
                    var \$catContainer = $('.wcfm_product_manager_cats_checklist_fields').first();
                    
                    if (\$catContainer.length === 0) {
                        if (initAttempts < 50) {
                            setTimeout(init, 200);
                        } else {
                            console.error('❌ No se encontró el contenedor después de 50 intentos');
                        }
                        return;
                    }
                    
                    console.log('✅ Contenedor de categorías encontrado');
                    
                    // Ocultar el contenedor original (ya está oculto por CSS)
                    \$catContainer.hide();
                    
                    // Crear botón ANTES del contenedor
                    var buttonHtml = '<div style=\"margin: 20px 0;\">' +
                        '<button type=\"button\" class=\"cv-categorize-button\" id=\"cv-open-modal\">' +
                        '🏷️ Categorizar Producto <span class=\"cv-badge\" id=\"cv-cat-count\">0 categorías</span>' +
                        '</button>' +
                        '</div>';
                    
                    \$catContainer.before(buttonHtml);
                    console.log('✅ Botón Categorizar insertado');
                    
                    // Cargar categorías
                    loadCategories();
                    updateSelectedCount();
                }
                
                function loadCategories() {
                    allCategories = [];
                    selectedCategories = [];
                    
                    console.log('🔍 Buscando categorías desde MÚLTIPLES fuentes...');
                    var checklistCount = 0;
                    var selectCount = 0;
                    var hiddenCount = 0;
                    
                    // MÉTODO 1: Desde el checklist visible
                    $('#product_cats_checklist input[type=\"checkbox\"]').each(function() {
                        var \$checkbox = $(this);
                        var id = \$checkbox.val();
                        var name = \$checkbox.siblings('span').first().text().trim();
                        
                        if (id && name) {
                            // Construir path jerárquico completo
                            var path = name;
                            var \$parent = \$checkbox.closest('li').parent().closest('li');
                            var depth = 0;
                            
                            while (\$parent.length && depth < 5) {
                                var parentName = \$parent.find('> label > span').first().text().trim();
                                if (!parentName) {
                                    parentName = \$parent.find('> span > span').first().text().trim();
                                }
                                if (parentName && parentName.length > 0) {
                                    path = parentName + ' → ' + path;
                                }
                                \$parent = \$parent.parent().closest('li');
                                depth++;
                            }
                            
                            allCategories.push({
                                id: id,
                                name: name,
                                path: path,
                                element: \$checkbox
                            });
                            checklistCount++;
                            
                            if (\$checkbox.is(':checked')) {
                                selectedCategories.push({id: id, name: name, path: path});
                            }
                        }
                    });
                    
                    console.log('📦 Desde checklist visible:', checklistCount);
                    
                    // MÉTODO 2: Buscar en TODO el DOM por si hay categorías ocultas
                    $('input[type=\"checkbox\"][name*=\"product_cat\"]').each(function() {
                        var \$checkbox = $(this);
                        var id = \$checkbox.val();
                        var name = \$checkbox.next('span').text().trim() || \$checkbox.siblings('span').text().trim() || \$checkbox.parent().text().trim();
                        
                        // Evitar duplicados
                        if (id && name && !allCategories.some(function(c) { return c.id == id; })) {
                            allCategories.push({
                                id: id,
                                name: name,
                                path: name,
                                element: \$checkbox
                            });
                            hiddenCount++;
                        }
                    });
                    
                    console.log('📦 Categorías ocultas adicionales:', hiddenCount);
                    
                    // MÉTODO 3: Desde el select (si existe)
                    $('#product_cats option').each(function() {
                        var \$option = $(this);
                        var id = \$option.val();
                        var name = \$option.text().trim();
                        
                        if (id && name && !allCategories.some(function(c) { return c.id == id; })) {
                            allCategories.push({
                                id: id,
                                name: name,
                                path: name,
                                element: \$option
                            });
                            selectCount++;
                        }
                    });
                    
                    console.log('📦 Desde select:', selectCount);
                    
                    console.log('📋 Cargadas ' + allCategories.length + ' categorías (' + selectedCategories.length + ' seleccionadas)');
                    
                    // Debug: mostrar primeras 5 categorías con su path
                    console.log('🗂️ PRIMERAS 5 CATEGORÍAS:');
                    allCategories.slice(0, 5).forEach(function(cat, index) {
                        console.log('  ' + (index+1) + ')', 'ID:', cat.id, '| Nombre:', cat.name, '| Path:', cat.path);
                    });
                    
                    // BUSCAR ACADEMIA específicamente
                    var academiaCats = allCategories.filter(function(c) {
                        return (c.name || '').toUpperCase().indexOf('ACADEMIA') !== -1;
                    });
                    if (academiaCats.length > 0) {
                        console.log('🎓 ACADEMIA ENCONTRADA:', academiaCats.map(function(c) { return c.name; }));
                    } else {
                        console.log('⚠️ ACADEMIA NO está en las categorías cargadas');
                    }
                    
                    // Guardar en window para poder inspeccionarlas
                    window.cvAllCategories = allCategories;
                    console.log('💾 Todas las categorías guardadas en: window.cvAllCategories');
                    
                    updateSelectedCount();
                }
                
                function updateSelectedCount() {
                    var count = selectedCategories.length;
                    $('#cv-cat-count').text(count + ' categoría' + (count !== 1 ? 's' : ''));
                    $('#cv-selected-count').text(count);
                }
                
                function renderSelected() {
                    var \$container = $('#cv-selected-categories');
                    
                    if (selectedCategories.length === 0) {
                        \$container.html('<p style=\"color:#999;text-align:center;padding:20px;\">No hay categorías seleccionadas</p>');
                        return;
                    }
                    
                    var html = '';
                    selectedCategories.forEach(function(cat) {
                        var displayName = cat.path || cat.name;
                        html += '<div class=\"cv-selected-tag\" data-cat-id=\"' + cat.id + '\" title=\"' + displayName + '\">' +
                            displayName +
                            '<button class=\"cv-selected-tag-remove\" data-cat-id=\"' + cat.id + '\">×</button>' +
                            '</div>';
                    });
                    
                    \$container.html(html);
                }
                
                function searchCategories(query) {
                    var \$results = $('#cv-search-results');
                    var \$help = $('#cv-search-help');
                    
                    console.log('🔍 BUSCANDO:', query);
                    console.log('📋 Total categorías disponibles:', allCategories.length);
                    
                    if (query.length < 2) {
                        \$results.html('');
                        \$help.text('💡 Escribe al menos 2 letras para buscar');
                        return;
                    }
                    
                    var matches = [];
                    var normalizedQuery = normalizeString(query);
                    
                    console.log('🔎 Filtrando', allCategories.length, 'categorías. Query normalizada:', normalizedQuery);
                    
                    allCategories.forEach(function(cat) {
                        var pathSource = cat.path || cat.name || '';
                        var normalizedPath = normalizeString(pathSource);
                        var normalizedName = normalizeString(cat.name || '');
                        
                        var foundInPath = normalizedPath.indexOf(normalizedQuery) !== -1;
                        var foundInName = normalizedName.indexOf(normalizedQuery) !== -1;
                        
                        if (foundInPath || foundInName) {
                            matches.push(cat);
                            if (matches.length <= 5) {
                                console.log('✅ #' + matches.length + ':', cat.name, '(path:', cat.path + ')');
                            }
                        }
                    });
                    
                    console.log('📊 Total encontrados:', matches.length);
                    
                    if (matches.length === 0) {
                        \$results.html('<p style=\"text-align:center;color:#999;padding:20px;\">😞 No se encontraron categorías</p>');
                        \$help.text('');
                        return;
                    }
                    
                    var html = '';
                    matches.slice(0, 20).forEach(function(cat) {
                        var isSelected = selectedCategories.some(function(s) { return s.id == cat.id; });
                        var selectedClass = isSelected ? ' selected' : '';
                        var badge = isSelected ? '✓ Seleccionada' : 'Click para agregar';
                        var displayPath = cat.path || cat.name || '';
                        var highlightedPath = highlightQuery(displayPath, query);
                        var childLabel = highlightQuery(cat.name || '', query);
                        var parentLabel = '';
                        if (cat.path && cat.path !== cat.name) {
                            var segments = cat.path.split('→');
                            if (segments.length > 1) {
                                parentLabel = highlightQuery(segments.slice(0, -1).join(' → ').trim(), query);
                            }
                        }
                        
                        html += '<div class=\"cv-search-result-item' + selectedClass + '\" data-cat-id=\"' + cat.id + '\">' +
                            '<div class=\"cv-search-result-text\"><strong class=\"cv-search-path\">' + highlightedPath + '</strong>';
                        
                        if (parentLabel) {
                            html += '<small style=\"color:#666;display:block;margin-top:4px;\">' + parentLabel + '</small>';
                        } else if (cat.path && cat.path !== cat.name) {
                            html += '<small style=\"color:#666;display:block;margin-top:4px;\">' + childLabel + '</small>';
                        }
                        
                        html += '</div>' +
                            '<span style=\"font-size:12px;color:#666;white-space:nowrap;\">' + badge + '</span>' +
                            '</div>';
                    });
                    
                    \$results.html(html);
                    \$help.text('✅ ' + matches.length + ' encontrada(s)');
                }
                
                // Abrir modal
                $(document).on('click', '#cv-open-modal', function(e) {
                    e.preventDefault();
                    console.log('🔓 ABRIENDO MODAL...');
                    loadCategories();
                    renderSelected();
                    $('#cv-category-modal').fadeIn(300);
                    $('#cv-category-search-input').val('').focus();
                    $('#cv-search-results').html('');
                    console.log('✅ Modal abierto - Input:', $('#cv-category-search-input').length);
                });
                
                // Cerrar modal
                function closeModal() {
                    $('#cv-category-modal').fadeOut(300);
                }
                
                $(document).on('click', '#cv-modal-close, #cv-modal-cancel, .cv-modal-overlay', closeModal);
                
                // Buscar en tiempo real
                $(document).on('input', '#cv-category-search-input', function() {
                    var query = $(this).val();
                    console.log('⌨️ INPUT DETECTADO - Valor:', query);
                    searchCategories(query);
                });
                
                console.log('✅ Event listener de búsqueda instalado');
                
                // Click en resultado de búsqueda
                $(document).on('click', '.cv-search-result-item', function() {
                    var catId = $(this).data('cat-id');
                    var cat = allCategories.find(function(c) { return c.id == catId; });
                    
                    if (!cat) return;
                    
                    var index = selectedCategories.findIndex(function(s) { return s.id == catId; });
                    
                    if (index === -1) {
                        selectedCategories.push({id: cat.id, name: cat.name, path: cat.path});
                        $(this).addClass('selected');
                    } else {
                        selectedCategories.splice(index, 1);
                        $(this).removeClass('selected');
                    }
                    
                    updateSelectedCount();
                    renderSelected();
                });
                
                // Remover categoría seleccionada
                $(document).on('click', '.cv-selected-tag-remove', function(e) {
                    e.stopPropagation();
                    var catId = $(this).data('cat-id');
                    
                    selectedCategories = selectedCategories.filter(function(s) { return s.id != catId; });
                    updateSelectedCount();
                    renderSelected();
                    
                    // Actualizar resultados de búsqueda si están visibles
                    var query = $('#cv-category-search-input').val();
                    if (query.length >= 2) {
                        searchCategories(query);
                    }
                });
                
                // Guardar y cerrar
                $(document).on('click', '#cv-modal-save', function() {
                    // Aplicar selecciones a los checkboxes reales
                    $('#product_cats_checklist input[type=\"checkbox\"]').prop('checked', false);
                    
                    selectedCategories.forEach(function(cat) {
                        $('#product_cats_checklist input[value=\"' + cat.id + '\"]').prop('checked', true).trigger('change');
                    });
                    
                    console.log('✅ Guardadas ' + selectedCategories.length + ' categorías');
                    closeModal();
                });
                
                // Bloquear ENTER en el input
                $(document).on('keydown', '#cv-category-search-input', function(e) {
                    if (e.keyCode === 13) {
                        e.preventDefault();
                        return false;
                    }
                });
                
                // Iniciar
                setTimeout(init, 500);
            });
        })();
        ";
    }
}

// Inicializar
CV_Category_Modal::get_instance();
