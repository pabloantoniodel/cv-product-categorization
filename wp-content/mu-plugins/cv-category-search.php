<?php
/**
 * Plugin Name: CV - Categorizador Modal WCFM
 * Description: Busca en MÚLTIPLES fuentes (checklist + ocultas + select).
 * Version: 3.3.4
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
        // Cargar siempre el script, la verificación se hace en JavaScript
        echo '<style>' . $this->get_css() . '</style>';
        echo '<script type="text/javascript">' . $this->get_js() . '</script>';
    }
    
    private function is_wcfm_products_page() {
        // Verificar por GET parameter
        if (isset($_GET['page']) && $_GET['page'] === 'wcfm-products-manage') {
            return true;
        }
        // Verificar por URL
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
            if (strpos($uri, 'products-manage') !== false || strpos($uri, 'store-manager') !== false) {
                return true;
            }
        }
        // Verificar por script name
        if (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], 'products-manage') !== false) {
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
            <div class="cv-modal-container" role="dialog" aria-modal="true" aria-labelledby="cv-modal-title">
                <header class="cv-modal-bar">
                    <div class="cv-modal-title">
                        <h2 id="cv-modal-title">🏷️ Categorizar Producto</h2>
                        <div class="cv-product-name" id="cv-product-name"></div>
                        <div class="cv-modal-counter">
                            <span id="cv-selected-count">0</span> seleccionada(s)
                            <div class="cv-summary-list" id="cv-summary-list"></div>
                        </div>
                    </div>
                    <div class="cv-modal-actions">
                        <input type="text" id="cv-category-search-input" placeholder="Buscar categoría..." autocomplete="off" aria-label="Buscar categoría">
                        <button type="button" class="cv-btn cv-btn-ghost" id="cv-clear-search">Limpiar</button>
                        <button type="button" class="cv-modal-close" id="cv-modal-close" aria-label="Cerrar modal">✕</button>
                    </div>
                </header>

                <div class="cv-modal-content">
                    <section class="cv-tree-pane" aria-label="Listado de categorías">
                        <div class="cv-tree-scroll" id="cv-tree-root"></div>
                    </section>
                    <aside class="cv-selected-pane" aria-label="Categorías seleccionadas">
                        <h3>Seleccionadas</h3>
                        <div id="cv-selected-categories" class="cv-selected-list"></div>
                    </aside>
                </div>

                <footer class="cv-modal-footer">
                    <button type="button" class="cv-btn cv-btn-tertiary" id="cv-modal-clear">Quitar todo</button>
                    <div class="cv-modal-footer-actions">
                        <button type="button" class="cv-btn cv-btn-secondary" id="cv-modal-cancel">Cancelar</button>
                        <button type="button" class="cv-btn cv-btn-primary" id="cv-modal-save">Guardar y cerrar</button>
                    </div>
                </footer>
            </div>
        </div>
        <?php
    }
    
    private function get_css() {
        return <<<'CSS'
        /* Ocultar selector original */
        .wcfm_product_manager_cats_checklist_fields {
            display: none !important;
        }

        /* Botón principal */
        .cv-categorize-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 26px;
            background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 12px 30px rgba(79, 70, 229, 0.25);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            margin: 18px 0;
        }
        .cv-categorize-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 36px rgba(79, 70, 229, 0.35);
        }
        .cv-categorize-button:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.35);
        }
        .cv-categorize-button .cv-badge {
            display: inline-block;
            padding: 3px 10px;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 999px;
            font-size: 13px;
            font-weight: 500;
        }

        /* Modal a pantalla completa */
        #cv-category-modal {
            position: fixed;
            inset: 0;
            z-index: 999999;
        }
        .cv-modal-overlay {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(6px);
        }
        .cv-modal-container {
            position: absolute;
            inset: 0;
            background: #fff;
            display: flex;
            flex-direction: column;
            box-shadow: 0 35px 70px rgba(15, 23, 42, 0.4);
        }

        .cv-modal-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 28px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            gap: 24px;
        }
        .cv-modal-title {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .cv-modal-title h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
        }
        .cv-product-name {
            font-size: 15px;
            font-weight: 500;
            color: #334155;
        }
        .cv-modal-counter {
            font-size: 14px;
            color: #475569;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }
        .cv-summary-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .cv-summary-chip {
            display: inline-flex;
            align-items: center;
            background: rgba(99, 102, 241, 0.12);
            color: #312e81;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 13px;
            font-weight: 500;
            gap: 6px;
        }
        .cv-summary-chip button {
            border: none;
            background: transparent;
            color: #4338ca;
            cursor: pointer;
            font-size: 13px;
            line-height: 1;
        }
        .cv-summary-chip button:hover {
            color: #1d4ed8;
        }
        .cv-modal-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        #cv-category-search-input {
            min-width: 260px;
            padding: 10px 14px 10px 40px;
            border: 1px solid #cbd5f5;
            border-radius: 8px;
            font-size: 15px;
            background: #fff url('data:image/svg+xml,%3Csvg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"%237185f8\"%3E%3Cpath stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.8\" d=\"M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.75 3.75a7.5 7.5 0 0012.9 12.9z\"/%3E%3C/svg%3E') no-repeat 12px center;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        #cv-category-search-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .cv-modal-close {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: none;
            background: #e2e8f0;
            color: #1e293b;
            font-size: 20px;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
        }
        .cv-modal-close:hover {
            background: #cbd5f5;
            transform: rotate(90deg);
        }

        .cv-modal-content {
            flex: 1;
            display: flex;
            overflow: hidden;
            background: #fff;
        }
        .cv-tree-pane {
            flex: 2.3;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #e2e8f0;
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.9) 0%, #fff 100%);
        }
        .cv-tree-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 18px 28px 28px;
            position: relative;
        }

        .cv-tree-list {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }
        .cv-tree-item {
            position: relative;
            margin: 3px 0;
        }
        .cv-tree-item > ul {
            margin-left: 28px;
            border-left: 1px dashed #dbeafe;
            padding-left: 18px;
        }
        .cv-tree-item.collapsed > ul {
            display: none;
        }

        .cv-tree-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s ease, box-shadow 0.15s ease;
            color: #1f2937;
        }
        .cv-tree-item.depth-0 > .cv-tree-label {
            font-weight: 600;
            padding-left: 12px;
        }
        .cv-tree-item.depth-1 > .cv-tree-label {
            padding-left: 40px;
        }
        .cv-tree-item.depth-2 > .cv-tree-label {
            padding-left: 68px;
        }
        .cv-tree-item.depth-3 > .cv-tree-label {
            padding-left: 96px;
        }
        .cv-tree-item.depth-4 > .cv-tree-label {
            padding-left: 124px;
        }
        .cv-tree-item.depth-5 > .cv-tree-label {
            padding-left: 152px;
        }
        .cv-tree-toggle {
            background: none;
            border: none;
            font-size: 16px;
            color: #6366f1;
            cursor: pointer;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.2s ease, color 0.2s ease;
            font-weight: 700;
        }
        .cv-tree-toggle:hover {
            background: rgba(99, 102, 241, 0.1);
            color: #4338ca;
        }
        .cv-tree-item.collapsed > .cv-tree-label > .cv-tree-toggle {
            transform: rotate(-90deg);
        }
        .cv-tree-toggle-spacer {
            display: inline-block;
            width: 24px;
            height: 24px;
        }

        .cv-tree-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #6366f1;
            cursor: pointer;
        }
        .cv-tree-text {
            flex: 1;
            font-size: 15px;
            line-height: 1.3;
        }
        .cv-tree-item.is-selected > .cv-tree-label {
            background: rgba(99, 102, 241, 0.12);
            box-shadow: inset 0 0 0 1px rgba(99, 102, 241, 0.4);
        }
        .cv-tree-item.match > .cv-tree-label {
            background: rgba(165, 180, 252, 0.22);
            box-shadow: inset 0 0 0 1px rgba(99, 102, 241, 0.55);
        }

        .cv-selected-pane {
            flex: 1;
            padding: 24px;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .cv-selected-pane h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }
        .cv-selected-list {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .cv-selected-chip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 14px;
            background: #eef2ff;
            color: #312e81;
            border-radius: 10px;
            font-size: 14px;
            box-shadow: 0 1px 3px rgba(30, 64, 175, 0.12);
        }
        .cv-selected-chip button {
            border: none;
            background: rgba(99, 102, 241, 0.15);
            color: #4338ca;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .cv-selected-chip button:hover {
            background: rgba(79, 70, 229, 0.3);
        }

        .cv-empty-selection {
            margin-top: 40px;
            text-align: center;
            color: #94a3b8;
            font-size: 14px;
        }

        .cv-modal-footer {
            padding: 18px 28px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }
        .cv-modal-footer-actions {
            display: flex;
            gap: 12px;
        }

        .cv-btn {
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            padding: 10px 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }
        .cv-btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            color: #fff;
            box-shadow: 0 10px 24px rgba(79, 70, 229, 0.3);
        }
        .cv-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 30px rgba(79, 70, 229, 0.36);
        }
        .cv-btn-secondary {
            background: #e2e8f0;
            color: #1f2937;
        }
        .cv-btn-secondary:hover {
            background: #cbd5f5;
        }
        .cv-btn-tertiary {
            background: transparent;
            color: #e11d48;
            border: 1px solid rgba(225, 29, 72, 0.3);
        }
        .cv-btn-tertiary:hover {
            background: rgba(225, 29, 72, 0.08);
        }
        .cv-btn-ghost {
            background: transparent;
            color: #475569;
            border: 1px solid rgba(148, 163, 184, 0.4);
            padding: 10px 18px;
        }
        .cv-btn-ghost:hover {
            background: rgba(226, 232, 240, 0.6);
        }

        /* Scrollbars */
        .cv-tree-scroll::-webkit-scrollbar,
        .cv-selected-list::-webkit-scrollbar {
            width: 8px;
        }
        .cv-tree-scroll::-webkit-scrollbar-thumb,
        .cv-selected-list::-webkit-scrollbar-thumb {
            background-color: rgba(148, 163, 184, 0.5);
            border-radius: 999px;
        }

        @media (max-width: 980px) {
            .cv-modal-content {
                flex-direction: column;
            }
            .cv-selected-pane {
                border-top: 1px solid #e2e8f0;
                border-left: none;
            }
        }
CSS;
    }
    
    private function get_js() {
        return <<<'JS'
        (function() {
            function isProductsPage() {
                var href = window.location.href || '';
                if (href.indexOf('products-manage') !== -1 || href.indexOf('wcfm-products') !== -1 || href.indexOf('store-manager') !== -1) {
                    return true;
                }
                if (document.querySelector('#product_cats_checklist') || document.querySelector('.wcfm_product_manager_cats_checklist_fields')) {
                    return true;
                }
                return false;
            }

            jQuery(document).ready(function($) {
                var shouldAutoOpen = false;
                try {
                    var params = new URLSearchParams(window.location.search || '');
                    shouldAutoOpen = params.get('cv_open_cat') === '1';
                } catch (err) {
                    shouldAutoOpen = (window.location.search || '').indexOf('cv_open_cat=1') !== -1;
                }

                if (!isProductsPage()) {
                    return;
                }
                if ($('#cv-open-modal').length) {
                    return;
                }

                var allCategories = [];
                var selectedCategories = [];
                var treeNodesById = {};
                var treeRoots = [];
                var suppressTreeChange = false;
                var selectedSet = new Set();
                var initAttempts = 0;
                var categoryIndex = {};

                function updateProductName() {
                    var name = $('#pro_title').val()
                        || $('input[name="pro_title"]').val()
                        || $('input.wcfm_product_title').val()
                        || $('input[name="post_title"]').val()
                        || $('input#title').val()
                        || '';
                    name = $.trim(name || '');
                    var $target = $('#cv-product-name');
                    if ($target.length) {
                        if (name) {
                            $target.text('Producto: ' + name);
                        } else {
                            $target.text('');
                        }
                    }
                }

                async function fetchAllProductCategories() {
                    var results = [];
                    var perPage = 100;
                    var page = 1;
                    var baseUrl = '';

                    if (window.wpApiSettings && window.wpApiSettings.root) {
                        baseUrl = window.wpApiSettings.root;
                    } else {
                        baseUrl = window.location.origin + '/wp-json/';
                    }

                    var endpoint = 'wp/v2/product_cat';
                    if (baseUrl.slice(-1) !== '/') {
                        baseUrl += '/';
                    }

                    try {
                        while (true) {
                            var url = baseUrl + endpoint + '?per_page=' + perPage + '&page=' + page + '&orderby=name&order=asc&_fields=id,name,parent';
                            var response = await fetch(url, { credentials: 'same-origin' });
                            if (!response.ok) {
                                break;
                            }
                            var data = await response.json();
                            if (!Array.isArray(data) || !data.length) {
                                break;
                            }
                            results = results.concat(data);
                            if (data.length < perPage) {
                                break;
                            }
                            page += 1;
                        }
                    } catch (error) {
                        console.warn('CV Category Modal: No se pudo obtener product_cat vía REST.', error);
                    }

                    return results;
                }

                function normalizeString(str) {
                    if (!str) {
                        return '';
                    }
                    return str.toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                }

                function getParentNameFromPath(path) {
                    if (!path) {
                        return null;
                    }
                    var parts = path.split('→').map(function(p) { return p.trim(); }).filter(function(p) { return p.length > 0; });
                    if (parts.length > 1) {
                        return parts[parts.length - 2];
                    }
                    return null;
                }

                function extractLeafName(label) {
                    if (!label) {
                        return '';
                    }
                    var normalized = label.replace(/&nbsp;/g, ' ');
                    if (window.DOMParser) {
                        try {
                            var parser = new DOMParser();
                            var doc = parser.parseFromString('<!doctype html><body>' + normalized + '</body>', 'text/html');
                            normalized = (doc && doc.body) ? doc.body.textContent : normalized;
                        } catch (error) {
                            normalized = normalized.replace(/&gt;/g, '>').replace(/&raquo;/g, '»').replace(/&rsaquo;/g, '›');
                        }
                    } else {
                        normalized = normalized.replace(/&gt;/g, '>').replace(/&raquo;/g, '»').replace(/&rsaquo;/g, '›');
                    }
                    normalized = normalized.split('\n')[0];
                    normalized = normalized.replace(/\s+/g, ' ').trim();
                    var separators = ['→', '›', '»', '>', '/', '|'];
                    separators.forEach(function(sep) {
                        if (normalized.indexOf(sep) !== -1) {
                            var parts = normalized.split(sep);
                            normalized = parts[parts.length - 1].trim();
                        }
                    });
                    return normalized;
                }

                function getMetaFromCheckbox($checkbox) {
                    var meta = { name: '', path: '', parentId: null, parentName: null };
                    if (!$checkbox || !$checkbox.length) {
                        return meta;
                    }

                    var rawName = '';
                    var $label = $checkbox.closest('label');
                    if ($label.length) {
                        rawName = $label.clone().children().remove().end().text().trim();
                    }
                    if (!rawName) {
                        rawName = $checkbox.siblings('span').first().text().trim();
                    }
                    if (!rawName) {
                        rawName = $checkbox.parent().text().trim();
                    }
                    var cleanName = extractLeafName(rawName);
                    meta.name = cleanName;

                    var pathParts = [];
                    if (cleanName) {
                        pathParts.push(cleanName);
                    }

                    var $currentLi = $checkbox.closest('li');
                    var depth = 0;
                    while ($currentLi.length > 0 && depth < 12) {
                        var $parentLi = $currentLi.parent().closest('li');
                        if (!$parentLi.length) {
                            break;
                        }

                        var parentName = '';
                        var $parentLabel = $parentLi.find('> label').first();
                        if ($parentLabel.length) {
                            parentName = $parentLabel.clone().children().remove().end().text().trim();
                        }
                        if (!parentName) {
                            parentName = $parentLi.find('> span > span').first().text().trim();
                        }
                        parentName = extractLeafName(parentName);
                        if (parentName) {
                            pathParts.unshift(parentName);
                            if (meta.parentId === null) {
                                var $parentCheckbox = $parentLi.find('> label > input[type="checkbox"]').first();
                                if ($parentCheckbox.length) {
                                    meta.parentId = $parentCheckbox.val();
                                    meta.parentName = parentName;
                                }
                            }
                        }
                        $currentLi = $parentLi;
                        depth++;
                    }

                    if (pathParts.length) {
                        meta.path = pathParts.join(' → ');
                    } else if (name) {
                        meta.path = name;
                    }

                    if (!meta.parentName) {
                        meta.parentName = extractLeafName(getParentNameFromPath(meta.path));
                    }

                    return meta;
                }

                function findCategoryById(catId) {
                    var idStr = String(catId);
                    return allCategories.find(function(cat) {
                        return String(cat.id) === idStr;
                    });
                }

                function rebuildSelectedSet() {
                    selectedSet = new Set(selectedCategories.map(function(cat) { return String(cat.id); }));
                }

                function addToSelectedFromCat(cat) {
                    if (!cat) {
                        return;
                    }
                    var idStr = String(cat.id);
                    if (selectedCategories.some(function(item) { return String(item.id) === idStr; })) {
                        return;
                    }
                    var parentName = '';
                    if (cat.parentId) {
                        var parentCat = categoryIndex[String(cat.parentId)];
                        if (parentCat) {
                            parentName = parentCat.name || '';
                        } else if (cat.parentName) {
                            parentName = cat.parentName;
                        }
                    }
                    selectedCategories.push({
                        id: cat.id,
                        name: cat.name,
                        path: cat.path || cat.name,
                        parentId: cat.parentId || null,
                        parentName: parentName
                    });
                }

                function removeSelectedCategory(catId) {
                    var idStr = String(catId);
                    selectedCategories = selectedCategories.filter(function(item) {
                        return String(item.id) !== idStr;
                    });
                }

                function ensureParentSelected(cat) {
                    if (!cat || !cat.parentId) {
                        return;
                    }
                    var parentCat = findCategoryById(cat.parentId);
                    if (parentCat) {
                        addToSelectedFromCat(parentCat);
                        ensureParentSelected(parentCat);
                    }
                }

                function collectDescendants(catId, bucket) {
                    var node = treeNodesById[String(catId)];
                    if (!node || !node.children) {
                        return;
                    }
                    node.children.forEach(function(child) {
                        bucket.push(child.id);
                        collectDescendants(child.id, bucket);
                    });
                }

                function removeCategoryAndDescendants(catId) {
                    removeSelectedCategory(catId);
                    var descendants = [];
                    collectDescendants(catId, descendants);
                    descendants.forEach(function(id) {
                        removeSelectedCategory(id);
                    });
                }

                function updateSelectedCount() {
                    var count = selectedCategories.length;
                    var suffix = (count === 1) ? '' : 's';
                    $('#cv-cat-count').text(count + ' categoría' + suffix);
                    $('#cv-selected-count').text(count);
                }

                function renderSelected() {
                    var $container = $('#cv-selected-categories');
                    if (!$container.length) {
                        renderSummary();
                        return;
                    }
                    if (!selectedCategories.length) {
                        $container.html('<div class="cv-empty-selection">No hay categorías seleccionadas</div>');
                        renderSummary();
                        return;
                    }
                    var html = selectedCategories.map(function(cat) {
                        var title = (cat.path || cat.name || '').replace(/"/g, '&quot;');
                        var label = cat.name || '';
                        if (cat.parentName && cat.parentName !== cat.name) {
                            label += ' · ' + cat.parentName;
                        }
                        return '<div class="cv-selected-chip" data-cat-id="' + cat.id + '" title="' + title + '">' +
                            '<span>' + label + '</span>' +
                            '<button type="button" data-cat-id="' + cat.id + '" aria-label="Quitar categoría">✕</button>' +
                            '</div>';
                    }).join('');
                    $container.html(html);
                    renderSummary();
                }

                function renderSummary() {
                    var $summary = $('#cv-summary-list');
                    if (!$summary.length) {
                        return;
                    }
                    if (!selectedCategories.length) {
                        $summary.html('');
                        return;
                    }
                    var chips = selectedCategories.map(function(cat) {
                        var label = cat.name || '';
                        return '<span class="cv-summary-chip" data-cat-id="' + cat.id + '">' +
                            '<span>' + label + '</span>' +
                            '<button type="button" data-cat-id="' + cat.id + '" aria-label="Quitar categoría">' +
                            '×' +
                            '</button>' +
                            '</span>';
                    }).join('');
                    $summary.html(chips);
                }

                async function loadCategories() {
                    allCategories = [];
                    selectedCategories = [];
                    selectedSet = new Set();
                    categoryIndex = {};

                    var categoriesData = await fetchAllProductCategories();
                    var catMap = {};
                    var orderCounter = 0;

                    categoriesData.forEach(function(item) {
                        if (!item || typeof item.id === 'undefined') {
                            return;
                        }
                        var idStr = String(item.id);
                        catMap[idStr] = {
                            id: item.id,
                            name: item.name || '',
                            parentId: item.parent ? item.parent : null,
                            element: null,
                            order: orderCounter++,
                            parentName: '',
                            path: ''
                        };
                    });

                    $('#product_cats_checklist input[type="checkbox"]').each(function() {
                        var $checkbox = $(this);
                        var idStr = String($checkbox.val());
                        if (!idStr) {
                            return;
                        }
                        var meta = getMetaFromCheckbox($checkbox);
                        if (!catMap[idStr]) {
                            catMap[idStr] = {
                                id: parseInt(idStr, 10),
                                name: extractLeafName($checkbox.closest('label').clone().children().remove().end().text().trim() || $checkbox.parent().text().trim() || ('Categoría ' + idStr)),
                                parentId: meta.parentId || null,
                                element: $checkbox,
                                order: orderCounter++,
                                parentName: '',
                                path: ''
                            };
                        } else {
                            catMap[idStr].element = $checkbox;
                            if (meta.parentId && !catMap[idStr].parentId) {
                                catMap[idStr].parentId = meta.parentId;
                            }
                        }
                    });

                    Object.keys(catMap).forEach(function(idStr) {
                        var cat = catMap[idStr];
                        if (cat.parentId && catMap[String(cat.parentId)]) {
                            cat.parentName = catMap[String(cat.parentId)].name;
                        }
                    });

                    function resolvePath(cat) {
                        if (cat._path) {
                            return cat._path;
                        }
                        if (cat.parentId && catMap[String(cat.parentId)]) {
                            cat._path = resolvePath(catMap[String(cat.parentId)]) + ' → ' + cat.name;
                        } else {
                            cat._path = cat.name;
                        }
                        return cat._path;
                    }

                    Object.keys(catMap).forEach(function(idStr) {
                        var cat = catMap[idStr];
                        cat.path = resolvePath(cat);
                    });

                    var orderedCats = Object.values(catMap).sort(function(a, b) {
                        return a.order - b.order;
                    });

                    allCategories = orderedCats.map(function(cat, index) {
                        cat.order = index;
                        categoryIndex[String(cat.id)] = {
                            id: cat.id,
                            name: cat.name,
                            parentId: cat.parentId
                        };
                        return {
                            id: cat.id,
                            name: cat.name,
                            path: cat.path,
                            parentId: cat.parentId,
                            parentName: cat.parentName,
                            element: cat.element,
                            order: cat.order
                        };
                    });

                    orderedCats.forEach(function(cat) {
                        if (cat.element && cat.element.is(':checked')) {
                            var catData = allCategories.find(function(item) { return item.id === cat.id; });
                            if (catData) {
                                addToSelectedFromCat(catData);
                            }
                        }
                    });

                    selectedCategories.slice().forEach(function(cat) {
                        ensureParentSelected(cat);
                    });
                    rebuildSelectedSet();
                }

                function buildTreeData() {
                    treeNodesById = {};
                    treeRoots = [];
                    allCategories.forEach(function(cat) {
                        treeNodesById[String(cat.id)] = {
                            id: cat.id,
                            name: cat.name,
                            path: cat.path,
                            parentId: cat.parentId,
                            parentName: cat.parentName,
                            order: cat.order,
                            children: [],
                            searchText: normalizeString(cat.name + ' ' + (cat.path || '')),
                            $li: null,
                            $checkbox: null
                        };
                    });

                    Object.keys(treeNodesById).forEach(function(id) {
                        var node = treeNodesById[id];
                        if (node.parentId && treeNodesById[String(node.parentId)]) {
                            treeNodesById[String(node.parentId)].children.push(node);
                        } else {
                            treeRoots.push(node);
                        }
                    });

                    Object.keys(treeNodesById).forEach(function(id) {
                        treeNodesById[id].children.sort(function(a, b) {
                            return a.order - b.order;
                        });
                    });
                    treeRoots.sort(function(a, b) { return a.order - b.order; });
                }

                function createTreeList(nodes, depth) {
                    var $ul = $('<ul class="cv-tree-list depth-' + depth + '"></ul>');
                    nodes.forEach(function(node) {
                        var $li = $('<li class="cv-tree-item depth-' + depth + '" data-cat-id="' + node.id + '"></li>');
                        var $label = $('<div class="cv-tree-label"></div>');
                        if (node.children.length) {
                            $li.addClass('has-children');
                            var $toggle = $('<button type="button" class="cv-tree-toggle" aria-label="Alternar subcategorías">+</button>');
                            $label.append($toggle);
                            $li.addClass('collapsed');
                        } else {
                            $label.append('<span class="cv-tree-toggle-spacer" aria-hidden="true"></span>');
                        }
                        var $checkbox = $('<input type="checkbox" class="cv-tree-checkbox" data-cat-id="' + node.id + '">');
                        var $text = $('<span class="cv-tree-text"></span>').text(node.name);
                        $label.append($checkbox).append($text);
                        $li.append($label);
                        node.$li = $li;
                        node.$checkbox = $checkbox;
                        node.$text = $text;
                        if (node.children.length) {
                            $li.append(createTreeList(node.children, depth + 1));
                        }
                        $ul.append($li);
                    });
                    return $ul;
                }

                function syncTreeFromSelection() {
                    suppressTreeChange = true;
                    rebuildSelectedSet();
                    Object.keys(treeNodesById).forEach(function(id) {
                        var node = treeNodesById[id];
                        var isSelected = selectedSet.has(id);
                        if (node.$checkbox) {
                            node.$checkbox.prop('checked', isSelected);
                        }
                        if (node.$li) {
                            node.$li.toggleClass('is-selected', isSelected);
                        }
                        if (isSelected) {
                            expandAncestors(node);
                        }
                    });
                    suppressTreeChange = false;
                    refreshToggleIcons();
                }

                function expandAncestors(node) {
                    var current = node;
                    while (current && current.parentId) {
                        var parentNode = treeNodesById[String(current.parentId)];
                        if (!parentNode) {
                            break;
                        }
                        if (parentNode.$li) {
                            parentNode.$li.removeClass('collapsed');
                        }
                        current = parentNode;
                    }
                }

                function setToggleIcon(node) {
                    if (node.$li) {
                        var $toggle = node.$li.find('> .cv-tree-label > .cv-tree-toggle');
                        if ($toggle.length) {
                            $toggle.text(node.$li.hasClass('collapsed') ? '+' : '−');
                        }
                    }
                    if (node.children && node.children.length) {
                        node.children.forEach(setToggleIcon);
                    }
                }

                function refreshToggleIcons() {
                    treeRoots.forEach(setToggleIcon);
                }

                function filterNode(node, normalizedQuery, hasQuery) {
                    var matches = !hasQuery || node.searchText.indexOf(normalizedQuery) !== -1;
                    var childMatch = false;
                    node.children.forEach(function(child) {
                        if (filterNode(child, normalizedQuery, hasQuery)) {
                            childMatch = true;
                        }
                    });
                    var shouldShow = matches || childMatch;
                    if (node.$li) {
                        node.$li.toggle(shouldShow);
                        node.$li.toggleClass('match', hasQuery && matches);
                        if (hasQuery && shouldShow) {
                            node.$li.removeClass('collapsed');
                        }
                        if (!hasQuery) {
                            node.$li.removeClass('match');
                        }
                        if (hasQuery && matches) {
                            expandAncestors(node);
                        }
                    }
                    return shouldShow;
                }

                function applyFilter(query) {
                    var normalized = normalizeString(query);
                    var hasQuery = normalized.length > 0;
                    treeRoots.forEach(function(node) {
                        filterNode(node, normalized, hasQuery);
                    });
                    refreshToggleIcons();
                }

                function renderTree() {
                    var $root = $('#cv-tree-root');
                    if (!$root.length) {
                        return;
                    }
                    $root.empty();
                    if (!treeRoots.length) {
                        $root.html('<div class="cv-empty-selection">No se encontraron categorías.</div>');
                        return;
                    }
                    $root.append(createTreeList(treeRoots, 0));
                    syncTreeFromSelection();
                }

                async function openModal() {
                    await loadCategories();
                    buildTreeData();
                    renderTree();
                    renderSelected();
                    updateSelectedCount();
                    renderSummary();
                    updateProductName();
                    $('#cv-category-modal').fadeIn(200);
                    $('#cv-category-search-input').val('').trigger('focus');
                    applyFilter('');
                }

                function closeModal() {
                    $('#cv-category-modal').fadeOut(200);
                }

                function init() {
                    initAttempts++;
                    var $catContainer = $('.wcfm_product_manager_cats_checklist_fields').first();
                    if (!$catContainer.length) {
                        if (initAttempts < 40) {
                            setTimeout(init, 200);
                        }
                        return;
                    }
                    $catContainer.hide();
                    var buttonHtml = '<div style="margin: 18px 0;">' +
                        '<button type="button" class="cv-categorize-button" id="cv-open-modal">' +
                        '🏷️ Categorizar Producto <span class="cv-badge" id="cv-cat-count">0 categorías</span>' +
                        '</button>' +
                        '</div>';
                    $catContainer.before(buttonHtml);
                    loadCategories().then(function() {
                        updateSelectedCount();
                        renderSummary();
                        if (shouldAutoOpen && !window.cvCategoryModalAutoOpened) {
                            window.cvCategoryModalAutoOpened = true;
                            setTimeout(function() {
                                openModal();
                            }, 250);
                        }
                    });
                }

                setTimeout(init, 300);

                $(document).on('click', '#cv-open-modal', async function(e) {
                    e.preventDefault();
                    await openModal();
                });

                $(document).on('click', '#cv-modal-close, #cv-modal-cancel, .cv-modal-overlay', function() {
                    closeModal();
                });

                $(document).on('click', '#cv-modal-save', function() {
                    $('#product_cats_checklist input[type="checkbox"]').prop('checked', false);
                    selectedCategories.forEach(function(cat) {
                        $('#product_cats_checklist input[value="' + cat.id + '"]').prop('checked', true).trigger('change');
                    });
                    closeModal();
                });

                $(document).on('click', '#cv-modal-clear', function() {
                    selectedCategories = [];
                    rebuildSelectedSet();
                    syncTreeFromSelection();
                    renderSelected();
                    updateSelectedCount();
                });

                $(document).on('input', '#cv-category-search-input', function() {
                    applyFilter($(this).val());
                });

                $(document).on('click', '#cv-clear-search', function() {
                    $('#cv-category-search-input').val('');
                    applyFilter('');
                    $('#cv-category-search-input').trigger('focus');
                });

                $(document).on('keydown', '#cv-category-search-input', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        return false;
                    }
                });

                $(document).on('change', '.cv-tree-checkbox', function() {
                    if (suppressTreeChange) {
                        return;
                    }
                    var catId = $(this).data('cat-id');
                    var cat = findCategoryById(catId);
                    if (!cat) {
                        return;
                    }
                    if ($(this).is(':checked')) {
                        addToSelectedFromCat(cat);
                        ensureParentSelected(cat);
                    } else {
                        removeCategoryAndDescendants(catId);
                    }
                    rebuildSelectedSet();
                    syncTreeFromSelection();
                    renderSelected();
                    updateSelectedCount();
                });

                $(document).on('click', '.cv-tree-toggle', function(e) {
                    e.stopPropagation();
                    var $item = $(this).closest('.cv-tree-item');
                    $item.toggleClass('collapsed');
                    refreshToggleIcons();
                });

                $(document).on('click', '.cv-tree-label', function(e) {
                    if ($(e.target).is('.cv-tree-checkbox') || $(e.target).closest('.cv-tree-toggle').length) {
                        return;
                    }
                    var $item = $(this).closest('.cv-tree-item');
                    if ($item.hasClass('has-children')) {
                        $item.toggleClass('collapsed');
                        refreshToggleIcons();
                    } else {
                        var checkbox = $(this).find('.cv-tree-checkbox').get(0);
                        if (checkbox) {
                            checkbox.checked = !checkbox.checked;
                            $(checkbox).trigger('change');
                        }
                    }
                });

                $(document).on('click', '.cv-selected-chip button', function() {
                    var catId = $(this).data('cat-id');
                    removeCategoryAndDescendants(catId);
                    rebuildSelectedSet();
                    syncTreeFromSelection();
                    renderSelected();
                    updateSelectedCount();
                });

                $(document).on('click', '.cv-summary-chip button', function() {
                    var catId = $(this).data('cat-id');
                    removeCategoryAndDescendants(catId);
                    rebuildSelectedSet();
                    syncTreeFromSelection();
                    renderSelected();
                    updateSelectedCount();
                });
            });
        })();
JS;
    }
}

// Inicializar
CV_Category_Modal::get_instance();
