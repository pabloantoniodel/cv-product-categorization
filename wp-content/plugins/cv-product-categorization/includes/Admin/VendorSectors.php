<?php
declare(strict_types=1);

namespace Cv\ProductCategorization\Admin;

use WP_Term;

final class VendorSectors
{
    private const META_KEY = 'cv_vendor_sector_terms';
    private static array $pageData = [];

    public static function init(): void
    {
        add_filter('wcfm_marketplace_settings_fields_general', [self::class, 'inject_settings_field'], 99, 2);
        add_action('wcfm_vendor_settings_update', [self::class, 'handle_settings_save'], 10, 2);
        add_action('wp_footer', [self::class, 'render_assets'], 900);
        add_action('admin_footer', [self::class, 'render_assets'], 900);
    }

    /**
     * @param array<string,mixed> $fields
     * @param int                  $vendorId
     *
     * @return array<string,mixed>
     */
    public static function inject_settings_field(array $fields, int $vendorId): array
    {
        $sector = self::get_sector_term();
        if (!$sector) {
            return $fields;
        }

        $terms = self::get_sector_descendants($sector->term_id);
        if (empty($terms)) {
            return $fields;
        }

        $selected = self::get_selected_terms($vendorId);
        self::$pageData = [
            'sector'   => [
                'id'   => $sector->term_id,
                'name' => $sector->name,
            ],
            'terms'    => self::build_terms_payload($terms, $sector->term_id),
            'selected' => $selected,
        ];

        $fields['cv_sector_categories'] = [
            'label'       => __('Sectores comerciales', 'cv-product-categorization'),
            'type'        => 'html',
            'class'       => 'wcfm_ele wcfm_full_ele',
            'label_class' => 'wcfm_title wcfm_full_title',
            'value'       => self::render_field_html($terms, $selected),
        ];

        return $fields;
    }

    /**
     * @param int   $vendorId
     * @param array<string,mixed> $formData
     */
    public static function handle_settings_save(int $vendorId, array $formData): void
    {
        $raw = $formData['vendor_sector_categories'] ?? '';
        $values = array_filter(array_map('trim', explode(',', (string) $raw)), static function (string $id): bool {
            return $id !== '';
        });

        if (empty($values)) {
            delete_user_meta($vendorId, self::META_KEY);
            return;
        }

        $sector = self::get_sector_term();
        if (!$sector) {
            delete_user_meta($vendorId, self::META_KEY);
            return;
        }

        $allowedIds = array_map(static function (WP_Term $term): int {
            return (int) $term->term_id;
        }, self::get_sector_descendants($sector->term_id));
        $allowedMap = array_fill_keys(array_map('strval', $allowedIds), true);

        $clean = [];
        foreach ($values as $value) {
            if (isset($allowedMap[$value])) {
                $clean[] = (int) $value;
            }
        }

        if (!empty($clean)) {
            update_user_meta($vendorId, self::META_KEY, array_values(array_unique($clean)));
        } else {
            delete_user_meta($vendorId, self::META_KEY);
        }
    }

    public static function render_assets(): void
    {
        if (empty(self::$pageData) || !self::is_vendor_settings_page()) {
            return;
        }

        $data = self::$pageData;
        self::$pageData = [];

        echo '<script type="text/javascript">window.CV_VENDOR_SECTOR_DATA = ' . wp_json_encode($data) . ';</script>';
        echo '<style>' . self::get_modal_css() . '</style>';
        echo self::get_modal_markup();
        echo '<script type="text/javascript">' . self::get_modal_js() . '</script>';
    }

    /**
     * @return array<int>
     */
    private static function get_selected_terms(int $vendorId): array
    {
        $raw = get_user_meta($vendorId, self::META_KEY, true);
        if (!is_array($raw)) {
            return [];
        }
        return array_values(array_filter(array_map('intval', $raw), static function (int $value): bool {
            return $value > 0;
        }));
    }

    private static function get_sector_term(): ?WP_Term
    {
        static $cached = null;
        if ($cached === null) {
            $cached = get_term_by('slug', 'sector', 'product_cat');
            if (!$cached || $cached instanceof WP_Term === false) {
                $cached = null;
            }
        }
        return $cached instanceof WP_Term ? $cached : null;
    }

    /**
     * @return array<int, WP_Term>
     */
    private static function get_sector_descendants(int $sectorId): array
    {
        static $cache = [];
        if (isset($cache[$sectorId])) {
            return $cache[$sectorId];
        }

        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'child_of'   => $sectorId,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            $cache[$sectorId] = [];
            return [];
        }

        $cache[$sectorId] = array_map(static function ($term): WP_Term {
            return $term;
        }, $terms);

        return $cache[$sectorId];
    }

    /**
     * @param array<int, WP_Term> $terms
     * @param array<int>          $selected
     */
    private static function render_field_html(array $terms, array $selected): string
    {
        $selectedNames = [];
        $map = [];
        foreach ($terms as $term) {
            $map[(int) $term->term_id] = $term;
        }
        foreach ($selected as $termId) {
            if (isset($map[$termId])) {
                $selectedNames[] = esc_html($map[$termId]->name);
            }
        }

        $value = implode(',', array_map('intval', $selected));

        ob_start();
        ?>
        <div id="cv-sector-field" class="cv-sector-field">
            <input type="hidden" id="cv-sector-input" name="vendor_sector_categories" value="<?php echo esc_attr($value); ?>" />
            <button type="button" class="cv-categorize-button" id="cv-sector-open">
                <span><?php esc_html_e('Seleccionar sectores comerciales', 'cv-product-categorization'); ?></span>
                <span class="cv-badge" id="cv-sector-count"></span>
            </button>
            <div class="cv-selected-pane cv-selected-pane-inline" id="cv-sector-selected"></div>
            <p class="description">
                <?php esc_html_e('Indica los sectores comerciales que definen tu tienda. Se mostrarán también en tus productos como referencia adicional.', 'cv-product-categorization'); ?>
            </p>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * @param array<int, WP_Term> $terms
     * @return array<int, array<string,mixed>>
     */
    private static function build_terms_payload(array $terms, int $sectorId): array
    {
        return array_map(static function (WP_Term $term) use ($sectorId): array {
            return [
                'id'     => (int) $term->term_id,
                'name'   => $term->name,
                'parent' => $term->parent ? (int) $term->parent : $sectorId,
                'slug'   => $term->slug,
            ];
        }, $terms);
    }

    private static function is_vendor_settings_page(): bool
    {
        $page = isset($_GET['page']) ? sanitize_text_field((string) $_GET['page']) : '';

        if (is_admin()) {
            if ($page === 'wcfm-settings' || $page === 'wcfm-vendors-manage') {
                return true;
            }
            return false;
        }

        if ($page === 'wcfm-settings') {
            return true;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if ($uri && (strpos($uri, 'wcfm-settings') !== false || strpos($uri, 'store-manager/settings') !== false)) {
            return true;
        }

        return false;
    }

    private static function get_modal_css(): string
    {
        return <<<'CSS'
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

.cv-selected-pane-inline {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  background: #f8fafc;
  padding: 16px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
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
  margin-top: 8px;
  text-align: center;
  color: #94a3b8;
  font-size: 14px;
  width: 100%;
}

#cv-sector-modal {
  position: fixed;
  inset: 0;
  z-index: 999999;
  display: none;
}
#cv-sector-modal.active {
  display: block;
}
#cv-sector-modal .cv-modal-overlay {
  position: absolute;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(6px);
}
#cv-sector-modal .cv-modal-container {
  position: absolute;
  inset: 0;
  background: #fff;
  display: flex;
  flex-direction: column;
}

.cv-modal-bar {
  padding: 22px 28px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 18px;
}
.cv-modal-title {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.cv-modal-title h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
  color: #1e293b;
}
.cv-modal-counter {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #475569;
  font-size: 14px;
}
.cv-modal-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}
.cv-modal-actions input[type="text"] {
  padding: 10px 14px;
  border-radius: 8px;
  border: 1px solid #cbd5f5;
  font-size: 15px;
  min-width: 240px;
}
.cv-modal-actions button {
  border: none;
  border-radius: 8px;
  padding: 10px 16px;
  background: #e2e8f0;
  cursor: pointer;
}

.cv-modal-content {
  flex: 1;
  display: flex;
  overflow: hidden;
}
.cv-tree-pane {
  flex: 2;
  padding: 28px;
  border-right: 1px solid #e2e8f0;
}
.cv-selected-pane-modal {
  flex: 1;
  padding: 24px;
  background: #f8fafc;
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.cv-selected-pane-modal h3 {
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

.cv-tree-scroll {
  max-height: 100%;
  overflow-y: auto;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 10px 0;
}
.cv-tree-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 20px;
}
.cv-tree-item.is-selected > .cv-tree-label {
  background: rgba(99, 102, 241, 0.12);
  box-shadow: inset 0 0 0 1px rgba(99, 102, 241, 0.4);
}
.cv-tree-item.match > .cv-tree-label {
  background: rgba(165, 180, 252, 0.22);
  box-shadow: inset 0 0 0 1px rgba(99, 102, 241, 0.55);
}
.cv-tree-label {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  padding: 10px 14px;
  border-radius: 10px;
  transition: background 0.2s ease;
  cursor: pointer;
}
.cv-tree-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  border: 1px solid #cbd5f5;
  border-radius: 4px;
}
.cv-tree-toggle {
  border: none;
  background: transparent;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #475569;
  cursor: pointer;
}
.cv-tree-toggle:hover {
  background: rgba(148, 163, 184, 0.2);
}
.cv-tree-toggle:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.35);
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
  .cv-selected-pane-modal {
    border-top: 1px solid #e2e8f0;
    border-left: none;
  }
}
CSS;
    }

    private static function get_modal_markup(): string
    {
        return <<<'HTML'
<div id="cv-sector-modal">
  <div class="cv-modal-overlay" data-role="close"></div>
  <div class="cv-modal-container" role="dialog" aria-modal="true" aria-labelledby="cv-sector-modal-title">
    <header class="cv-modal-bar">
      <div class="cv-modal-title">
        <h2 id="cv-sector-modal-title">🏷️ Seleccionar sectores comerciales</h2>
        <div class="cv-modal-counter">
          <span id="cv-sector-selected-count">0</span> seleccionada(s)
        </div>
      </div>
      <div class="cv-modal-actions">
        <input type="text" id="cv-sector-search" placeholder="Buscar sector..." autocomplete="off" aria-label="Buscar sector" />
        <button type="button" id="cv-sector-clear-search">Limpiar</button>
        <button type="button" class="cv-modal-close" id="cv-sector-close" aria-label="Cerrar">✕</button>
      </div>
    </header>
    <div class="cv-modal-content">
      <section class="cv-tree-pane" aria-label="Listado de sectores">
        <div class="cv-tree-scroll" id="cv-sector-tree"></div>
      </section>
      <aside class="cv-selected-pane-modal" aria-label="Sectores seleccionados">
        <h3>Seleccionadas</h3>
        <div id="cv-sector-selected-modal" class="cv-selected-list"></div>
      </aside>
    </div>
    <footer class="cv-modal-footer">
      <button type="button" class="cv-btn cv-btn-tertiary" id="cv-sector-clear-all">Quitar todo</button>
      <div class="cv-modal-footer-actions">
        <button type="button" class="cv-btn cv-btn-secondary" id="cv-sector-cancel">Cancelar</button>
        <button type="button" class="cv-btn cv-btn-primary" id="cv-sector-save">Guardar y cerrar</button>
      </div>
    </footer>
  </div>
</div>
HTML;
    }

    private static function get_modal_js(): string
    {
        return <<<'JS'
(function($){
  const data = window.CV_VENDOR_SECTOR_DATA || null;
  if(!data || !Array.isArray(data.terms)) {
    return;
  }

  const state = {
    nodes: new Map(),
    roots: [],
    selected: new Set((data.selected || []).map(id => String(id))),
    sectorId: String(data.sector.id),
    initialized: false,
    snapshot: new Set()
  };

  function normalize(str){
    return (str || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  }

  function buildTree(){
    data.terms.forEach(term => {
      const id = String(term.id);
      const parent = term.parent ? String(term.parent) : state.sectorId;
      const node = {
        id,
        name: term.name,
        parent,
        children: [],
        element: null,
        checkbox: null,
        toggle: null,
        normalized: normalize(term.name),
        expanded: false
      };
      state.nodes.set(id, node);
    });

    state.nodes.forEach(node => {
      if(node.parent && state.nodes.has(node.parent)) {
        state.nodes.get(node.parent).children.push(node);
      } else {
        state.roots.push(node);
      }
    });

    state.roots.sort((a,b) => a.name.localeCompare(b.name, 'es', { sensitivity: 'base' }));
    state.nodes.forEach(node => {
      node.children.sort((a,b) => a.name.localeCompare(b.name, 'es', { sensitivity: 'base' }));
    });
  }

  function createTreeItem(node, depth){
    const item = document.createElement('div');
    item.className = 'cv-tree-item';
    item.setAttribute('data-id', node.id);

    if(node.children.length){
      const toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.className = 'cv-tree-toggle';
      toggle.textContent = '▸';
      toggle.addEventListener('click', function(event){
        event.stopPropagation();
        node.expanded = !node.expanded;
        updateNodeExpansion(node);
      });
      node.toggle = toggle;
      item.appendChild(toggle);
    } else {
      const spacer = document.createElement('span');
      spacer.className = 'cv-tree-toggle';
      spacer.textContent = '';
      item.appendChild(spacer);
    }

    const label = document.createElement('label');
    label.className = 'cv-tree-label';
    label.style.paddingLeft = (depth * 20 + 10) + 'px';

    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.value = node.id;
    checkbox.checked = state.selected.has(node.id);
    checkbox.addEventListener('change', function(event){
      event.stopPropagation();
      toggleSelection(node.id, checkbox.checked);
    });

    const text = document.createElement('span');
    text.className = 'cv-tree-text';
    text.textContent = node.name;

    label.appendChild(checkbox);
    label.appendChild(text);
    label.addEventListener('click', function(){
      checkbox.checked = !checkbox.checked;
      toggleSelection(node.id, checkbox.checked);
    });

    node.checkbox = checkbox;
    node.element = item;

    item.appendChild(label);

    return item;
  }

  function setDescendantsVisibility(node, visible){
    node.children.forEach(child => {
      if(child.element){
        child.element.style.display = visible ? '' : 'none';
      }
      if(!visible){
        child.expanded = false;
        if(child.toggle){
          child.toggle.textContent = '▸';
        }
      }
      setDescendantsVisibility(child, visible && child.expanded);
    });
  }

  function updateNodeExpansion(node){
    if(!node.element) return;
    if(node.children.length === 0) return;
    node.element.classList.toggle('expanded', node.expanded);
    if(node.toggle){
      node.toggle.textContent = node.expanded ? '▾' : '▸';
    }
    setDescendantsVisibility(node, node.expanded);
  }

  function expandAncestors(node){
    let current = node;
    while(current && state.nodes.has(current.parent)){
      const parent = state.nodes.get(current.parent);
      if(parent && !parent.expanded){
        parent.expanded = true;
        updateNodeExpansion(parent);
      }
      current = parent;
    }
  }

  function toggleSelection(id, isChecked){
    if(isChecked){
      state.selected.add(String(id));
    } else {
      state.selected.delete(String(id));
    }
    syncSelectionUI();
  }

  function syncSelectionUI(){
    state.nodes.forEach(node => {
      const isSelected = state.selected.has(node.id);
      if(node.checkbox){
        node.checkbox.checked = isSelected;
      }
      if(node.element){
        node.element.classList.toggle('is-selected', isSelected);
      }
    });
    renderSelectedCollections();
  }

  function renderSelectedCollections(){
    const ids = Array.from(state.selected);
    const hidden = document.getElementById('cv-sector-input');
    const count = document.getElementById('cv-sector-count');
    const inline = document.getElementById('cv-sector-selected');
    const modalList = document.getElementById('cv-sector-selected-modal');
    const modalCount = document.getElementById('cv-sector-selected-count');

    if(hidden){
      hidden.value = ids.join(',');
    }
    if(count){
      count.textContent = ids.length ? ids.length + ' seleccionada(s)' : 'Sin seleccionar';
    }
    if(modalCount){
      modalCount.textContent = ids.length;
    }

    const chipsInline = createChips(ids);
    const chipsModal = createChips(ids, true);

    if(inline){
      inline.innerHTML = '';
      if(chipsInline.length){
        chipsInline.forEach(chip => inline.appendChild(chip));
      } else {
        const empty = document.createElement('div');
        empty.className = 'cv-empty-selection';
        empty.textContent = 'Sin sectores seleccionados todavía';
        inline.appendChild(empty);
      }
    }

    if(modalList){
      modalList.innerHTML = '';
      if(chipsModal.length){
        chipsModal.forEach(chip => modalList.appendChild(chip));
      } else {
        const empty = document.createElement('div');
        empty.className = 'cv-empty-selection';
        empty.textContent = 'Selecciona uno o más sectores para continuar';
        modalList.appendChild(empty);
      }
    }
  }

  function createChips(ids, removable){
    return ids
      .map(id => state.nodes.get(String(id)))
      .filter(Boolean)
      .sort((a,b) => a.name.localeCompare(b.name, 'es', { sensitivity: 'base' }))
      .map(node => {
        const chip = document.createElement('div');
        chip.className = 'cv-selected-chip';
        chip.textContent = node.name;
        if(removable){
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.setAttribute('aria-label', 'Quitar ' + node.name);
          btn.textContent = '✕';
          btn.addEventListener('click', function(){
            state.selected.delete(node.id);
            syncSelectionUI();
          });
          chip.appendChild(btn);
        }
        return chip;
      });
  }

  function filterTree(query){
    const normalized = normalize(query);
    if(!normalized){
      state.nodes.forEach(node => {
        if(node.element){
          node.element.style.display = '';
          node.element.classList.remove('match');
        }
      });
      return;
    }

    const matchCache = new Map();

    function nodeMatches(node){
      if(matchCache.has(node.id)){
        return matchCache.get(node.id);
      }
      const direct = node.normalized.indexOf(normalized) !== -1;
      if(direct){
        matchCache.set(node.id, true);
        return true;
      }
      const anyChild = node.children.some(child => nodeMatches(child));
      matchCache.set(node.id, anyChild);
      return anyChild;
    }

    state.nodes.forEach(node => {
      const matches = nodeMatches(node);
      if(node.element){
        node.element.style.display = matches ? '' : 'none';
        node.element.classList.toggle('match', node.normalized.indexOf(normalized) !== -1);
      }
      if(matches && node.normalized.indexOf(normalized) !== -1){
        expandAncestors(node);
      }
    });
  }

  function openModal(){
    const modal = document.getElementById('cv-sector-modal');
    if(!modal) return;
    state.snapshot = new Set(state.selected);
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    const search = document.getElementById('cv-sector-search');
    if(search){
      search.value = '';
      filterTree('');
      search.focus();
    }
  }

  function closeModal(){
    const modal = document.getElementById('cv-sector-modal');
    if(!modal) return;
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }

  function cancelModal(){
    state.selected = new Set(state.snapshot);
    syncSelectionUI();
    closeModal();
  }

  function renderNode(node, depth, container){
    const item = createTreeItem(node, depth);
    container.appendChild(item);
    if(depth === 0){
      node.expanded = true;
      updateNodeExpansion(node);
    }
    node.children.forEach(child => renderNode(child, depth + 1, container));
  }

  function initialise(){
    if(state.initialized) return;
    buildTree();
    const treeRoot = document.getElementById('cv-sector-tree');
    if(treeRoot){
      state.roots.forEach(node => renderNode(node, 0, treeRoot));
    }
    syncSelectionUI();
    bindEvents();
    state.initialized = true;
  }

  function bindEvents(){
    $('#cv-sector-open').on('click', function(event){
      event.preventDefault();
      openModal();
    });
    $('#cv-sector-save').on('click', function(){
      closeModal();
    });
    $('#cv-sector-cancel, #cv-sector-close, #cv-sector-modal .cv-modal-overlay').on('click', function(){
      cancelModal();
    });
    $('#cv-sector-clear-all').on('click', function(){
      state.selected.clear();
      syncSelectionUI();
    });
    $('#cv-sector-clear-search').on('click', function(){
      const search = document.getElementById('cv-sector-search');
      if(search){
        search.value = '';
      }
      filterTree('');
    });
    $('#cv-sector-search').on('input', function(){
      filterTree(this.value || '');
    });

    const observer = new MutationObserver(function(){
      const field = document.getElementById('cv-sector-field');
      if(field && !field.dataset.initialized){
        field.dataset.initialized = '1';
        syncSelectionUI();
      }
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }

  function initWhenReady(){
    const field = document.getElementById('cv-sector-field');
    if(field){
      initialise();
    } else {
      setTimeout(initWhenReady, 400);
    }
  }

  $(document).ready(initWhenReady);
})(jQuery);
JS;
    }
}
