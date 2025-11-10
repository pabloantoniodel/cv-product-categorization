<?php
if (defined('WP_DEBUG') && WP_DEBUG) {
    echo '<!-- CV store_rows count: ' . (is_array($store_rows) ? count($store_rows) : 'NA') . ' / total: ' . (isset($store_rows_total) && is_array($store_rows_total) ? count($store_rows_total) : 'NA') . ' -->';
}
/**
 * Vista del listado personalizado de comercios.
 *
 * Variables disponibles:
 * @var array  $store_rows      Arreglo de datos enriquecidos por comercio.
 * @var string $search_term
 * @var array  $pagination_links
 * @var int    $total_count
 * @var int    $paged
 * @var int    $total_pages
 * @var int    $per_page
 * @var string $page_url
 * @var string $order_select
 * @var bool   $geo_active
 * @var array  $geo_params
 * @var array  $radius_config
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!-- CV Comercios plantilla v2025-11-10 -->
<div class="cv-comercios">
    <header class="cv-comercios__header">
        <h1 class="cv-comercios__title">Asociados CV</h1>
        <p class="cv-comercios__subtitle">
            Descubre los comercios y servicios destacados de Ciudad Virtual y encuentra el que necesitas.
        </p>
    </header>

    <form class="cv-comercios__filters" method="get" action="<?php echo esc_url($page_url); ?>">
        <div class="cv-comercios__field">
            <label for="cv_search" class="cv-comercios__label">Buscar</label>
            <input
                type="text"
                id="cv_search"
                name="cv_search"
                class="cv-comercios__input"
                value="<?php echo esc_attr($search_term); ?>"
                placeholder="Buscar por nombre, sector o palabra clave"
            />
        </div>

        <?php if ($geo_active && !empty($geo_params)) : ?>
            <?php
            $slider_value_raw = ($geo_params['radius_raw'] !== null) ? $geo_params['radius_raw'] : $geo_params['radius'];
            $slider_value = is_numeric($slider_value_raw) ? (float) $slider_value_raw : (float) $radius_config['default'];
            $slider_max   = is_numeric($radius_config['max']) ? (float) $radius_config['max'] : 100.0;
            ?>

            <div class="cv-comercios__field cv-comercios__field--radius">
                <label for="wcfmmp_radius_range" class="cv-comercios__label">Distancia</label>

                <div class="cv-comercios__radius-controls">
                    <div id="wcfm_radius_filter_container" class="wcfm_radius_filter_container">
                        <input
                            type="text"
                            id="wcfmmp_radius_addr"
                            name="wcfmmp_radius_addr"
                            class="wcfmmp-radius-addr"
                            placeholder="<?php esc_attr_e('Inserta tu dirección…', 'wc-multivendor-marketplace'); ?>"
                            value=""
                        />
                        <i class="wcfmmmp_locate_icon" style="background-image: url(<?php echo esc_url($radius_config['icon_url']); ?>)"></i>
                    </div>

                    <div class="wcfm_radius_slidecontainer">
                        <input
                            class="wcfmmp_radius_range"
                            name="wcfmmp_radius_range"
                            id="wcfmmp_radius_range"
                            type="range"
                            value="<?php echo esc_attr($slider_value); ?>"
                            min="0"
                            max="<?php echo esc_attr($slider_max); ?>"
                            step="1"
                        />
                        <span class="wcfmmp_radius_range_start">0</span>
                        <span class="wcfmmp_radius_range_cur">
                            <?php echo esc_html(number_format_i18n($slider_value, 2)); ?>
                            <?php echo esc_html($radius_config['unit_label']); ?>
                        </span>
                        <span class="wcfmmp_radius_range_end">
                            <?php echo esc_html(number_format_i18n($slider_max, 0)); ?>
                        </span>
                    </div>

                    <input type="hidden" id="wcfmmp_radius_lat" name="wcfmmp_radius_lat" value="<?php echo esc_attr($geo_params['lat_raw'] !== null ? $geo_params['lat_raw'] : $geo_params['lat']); ?>" />
                    <input type="hidden" id="wcfmmp_radius_lng" name="wcfmmp_radius_lng" value="<?php echo esc_attr($geo_params['lng_raw'] !== null ? $geo_params['lng_raw'] : $geo_params['lng']); ?>" />
                </div>
            </div>
        <?php endif; ?>

        <div class="cv-comercios__field">
            <label for="cv_order" class="cv-comercios__label">Ordenar por</label>
            <select id="cv_order" name="order" class="cv-comercios__select">
                <option value="newness_desc" <?php selected($order_select, 'newness_desc'); ?>>Más nuevos primero</option>
                <option value="newness_asc" <?php selected($order_select, 'newness_asc'); ?>>Más antiguos primero</option>
                <option value="alphabetical_asc" <?php selected($order_select, 'alphabetical_asc'); ?>>Nombre A → Z</option>
                <option value="alphabetical_desc" <?php selected($order_select, 'alphabetical_desc'); ?>>Nombre Z → A</option>
                <option value="rating_desc" <?php selected($order_select, 'rating_desc'); ?>>Mejor valoración</option>
                <?php if ($geo_active) : ?>
                    <option value="distance_asc" <?php selected($order_select, 'distance_asc'); ?>>Más cercanos</option>
                <?php endif; ?>
            </select>
        </div>

        <div class="cv-comercios__actions">
            <button type="submit" class="cv-comercios__submit">Aplicar filtros</button>
            <?php if ($search_term || $order_select !== 'newness_desc') : ?>
                <a class="cv-comercios__reset" href="<?php echo esc_url($page_url); ?>">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="cv-comercios__summary">
        <?php if ($total_count > 0) : ?>
            Mostrando
            <strong>
                <?php
                $from = ($paged - 1) * $per_page + 1;
                $to   = min($from + $per_page - 1, $total_count);
                echo esc_html(sprintf('%d–%d', $from, $to));
                ?>
            </strong>
            de <strong><?php echo esc_html($total_count); ?></strong> comercios.
            <?php if ($geo_active && !empty($geo_params['radius'])) : ?>
                <span class="cv-comercios__summary-geo">
                    (Radio: <?php echo esc_html(number_format_i18n((float) $geo_params['radius'], 2)); ?> <?php echo esc_html($radius_config['unit_label']); ?>)
                </span>
            <?php endif; ?>
        <?php else : ?>
            No se encontraron comercios con los filtros seleccionados.
        <?php endif; ?>
    </div>

    <div class="cv-comercios__grid">
        <?php if (!empty($store_rows)) : ?>
            <?php foreach ($store_rows as $store_row) :
                $store              = $store_row['store'];
                $store_name         = $store_row['name'];
                $store_url          = $store_row['url'];
                $store_avatar       = $store_row['avatar'];
                $store_banner       = $store_row['banner'];
                $store_excerpt      = $store_row['excerpt'];
                $store_address      = $store_row['address'];
                $store_phone        = $store_row['phone'];
                $store_phone_href   = $store_row['phone_href'];
                $store_email        = $store_row['email'];
                $store_rating       = $store_row['rating'];
                $store_review_count = $store_row['review_count'];
                $rating_percent     = $store_row['rating_percent'];
                $distance_display = $store_row['distance_display'];
                ?>
                <article class="cv-comercios__card">
                    <div class="cv-comercios__card-media<?php echo $store_banner ? ' has-banner' : ''; ?>"<?php if ($store_banner) : ?> style="background-image: url('<?php echo esc_url($store_banner); ?>');"<?php endif; ?>>
                        <div class="cv-comercios__card-avatar">
                            <?php if ($store_avatar) : ?>
                                <img src="<?php echo esc_url($store_avatar); ?>" alt="<?php echo esc_attr($store_name); ?>" loading="lazy" />
                            <?php else : ?>
                                <?php echo esc_html(mb_substr($store_name, 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="cv-comercios__card-body">
                        <h2 class="cv-comercios__card-title">
                            <a href="<?php echo esc_url($store_url); ?>">
                                <?php echo esc_html($store_name); ?>
                            </a>
                        </h2>

                        <div class="cv-comercios__card-rating">
                            <?php if ($store_review_count > 0) : ?>
                                <div class="cv-comercios__stars" aria-hidden="true">
                                    <span class="cv-comercios__stars-base">★★★★★</span>
                                    <span class="cv-comercios__stars-fill" style="width: <?php echo esc_attr($rating_percent); ?>%;">★★★★★</span>
                                </div>
                                <span class="cv-comercios__rating-value"><?php echo esc_html(number_format($store_rating, 1)); ?></span>
                                <span class="cv-comercios__rating-count">(<?php echo esc_html($store_review_count); ?>)</span>
                            <?php else : ?>
                                <span class="cv-comercios__rating-empty">Sin reseñas</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($distance_display) : ?>
                            <div class="cv-comercios__distance">
                                📍 A <?php echo esc_html($distance_display); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($store_excerpt) : ?>
                            <p class="cv-comercios__card-excerpt">
                                <?php echo wp_kses_post(wp_trim_words($store_excerpt, 26)); ?>
                            </p>
                        <?php endif; ?>

                        <ul class="cv-comercios__card-meta">
                            <?php if ($store_address) : ?>
                                <li>
                                    <span class="cv-comercios__card-meta-icon" aria-hidden="true">📍</span>
                                    <span><?php echo esc_html($store_address); ?></span>
                                </li>
                            <?php endif; ?>
                            <?php if ($store_phone) : ?>
                                <li>
                                    <span class="cv-comercios__card-meta-icon" aria-hidden="true">📞</span>
                                    <a href="tel:<?php echo esc_attr($store_phone_href); ?>"><?php echo esc_html($store_phone); ?></a>
                                </li>
                            <?php endif; ?>
                            <?php if ($store_email) :
                                $obfuscated_email = antispambot($store_email);
                                ?>
                                <li>
                                    <span class="cv-comercios__card-meta-icon" aria-hidden="true">✉️</span>
                                    <a href="mailto:<?php echo esc_attr($obfuscated_email); ?>"><?php echo esc_html($obfuscated_email); ?></a>
                                </li>
                            <?php endif; ?>
                        </ul>

                        <div class="cv-comercios__card-actions">
                            <a class="cv-comercios__card-button" href="<?php echo esc_url($store_url); ?>">
                                Ver tienda
                            </a>
                            <?php if ($store_email) :
                                $obfuscated_email = antispambot($store_email);
                                ?>
                                <a class="cv-comercios__card-action-secondary" href="mailto:<?php echo esc_attr($obfuscated_email); ?>">
                                    Escribir
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else : ?>
            <p class="cv-comercios__empty">
                No hay comercios disponibles en este momento. Ajusta los filtros o vuelve más tarde.
            </p>
        <?php endif; ?>
    </div>

    <?php if (!empty($pagination_links)) : ?>
        <nav class="cv-comercios__pagination" aria-label="Paginación de comercios">
            <ul class="cv-comercios__pagination-list">
                <?php foreach ($pagination_links as $link) : ?>
                    <li class="cv-comercios__pagination-item"><?php echo $link; ?></li>
                <?php endforeach; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('.cv-comercios__filters');
    if (!form) {
        return;
    }

    var orderSelect = document.getElementById('cv_order');
    if (orderSelect) {
        orderSelect.addEventListener('change', function () {
            form.submit();
        });
    }
});
</script>

