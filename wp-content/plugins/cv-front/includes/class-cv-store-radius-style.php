<?php
/**
 * Estilos modernizados para el control de radio en la página de comercios.
 *
 * @package CV_Front
 * @since 3.2.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Store_Radius_Style
{
    public function __construct()
    {
        add_filter('body_class', [$this, 'append_body_class']);
        add_action('wp_head', [$this, 'print_styles'], 80);
    }

    /**
     * Añade una clase al body cuando estamos en el directorio de comercios.
     *
     * @param string[] $classes
     * @return string[]
     */
    public function append_body_class(array $classes): array
    {
        if ($this->is_store_directory()) {
            $classes[] = 'cv-store-directory';
        }

        return $classes;
    }

    /**
     * Imprime estilos personalizados para el slider de distancia.
     */
    public function print_styles(): void
    {
        if (!$this->is_store_directory()) {
            return;
        }

        ?>
        <style>
            .cv-store-directory .wcfm_radius_slidecontainer {
                position: relative;
                padding: 18px 20px 26px;
                background: linear-gradient(135deg, rgba(99, 102, 241, 0.12), rgba(59, 130, 246, 0.08));
                border: 1px solid rgba(99, 102, 241, 0.2);
                border-radius: 14px;
                box-shadow: 0 15px 35px -20px rgba(79, 70, 229, 0.45);
            }

            .cv-store-directory .wcfm_radius_slidecontainer .wcfmmp_radius_range_start,
            .cv-store-directory .wcfm_radius_slidecontainer .wcfmmp_radius_range_end {
                color: #0f172a;
                font-weight: 600;
                font-size: 13px;
                background: rgba(255, 255, 255, 0.85);
                padding: 4px 10px;
                border-radius: 999px;
                box-shadow: 0 6px 18px -12px rgba(15, 23, 42, 0.55);
                position: absolute;
                top: 18px;
            }

            .cv-store-directory .wcfm_radius_slidecontainer .wcfmmp_radius_range_start {
                left: 18px;
            }

            .cv-store-directory .wcfm_radius_slidecontainer .wcfmmp_radius_range_end {
                right: 18px;
            }

            .cv-store-directory .wcfm_radius_slidecontainer .wcfmmp_radius_range_cur {
                color: #0f172a;
                background: #ffffff;
                font-weight: 700;
                font-size: 14px;
                padding: 6px 16px;
                border-radius: 999px;
                box-shadow: 0 14px 35px -20px rgba(15, 23, 42, 0.65);
                transform: translateX(-50%);
                margin-top: 14px;
                border: 1px solid rgba(148, 163, 184, 0.35);
                letter-spacing: 0.02em;
                min-width: 72px;
                text-align: center;
                z-index: 5;
            }

            .cv-store-directory .wcfm_radius_slidecontainer input.wcfmmp_radius_range {
                --track-bg: rgba(79, 70, 229, 0.2);
                --track-fill: linear-gradient(135deg, #6366f1 10%, #4338ca 90%);
                --thumb-size: 22px;
                width: 100%;
                accent-color: #6366f1;
            }

            .cv-store-directory .wcfm_radius_slidecontainer input.wcfmmp_radius_range::-webkit-slider-runnable-track {
                height: 6px;
                background: var(--track-bg);
                border-radius: 999px;
            }

            .cv-store-directory .wcfm_radius_slidecontainer input.wcfmmp_radius_range::-webkit-slider-thumb {
                width: var(--thumb-size);
                height: var(--thumb-size);
                background: var(--track-fill);
                border-radius: 50%;
                box-shadow: 0 8px 18px -6px rgba(99, 102, 241, 0.6);
                margin-top: calc(-0.5 * (var(--thumb-size) - 6px));
                border: 2px solid #fff;
            }

            .cv-store-directory .wcfm_radius_slidecontainer input.wcfmmp_radius_range::-moz-range-track {
                height: 6px;
                background: var(--track-bg);
                border-radius: 999px;
            }

            .cv-store-directory .wcfm_radius_slidecontainer input.wcfmmp_radius_range::-moz-range-thumb {
                width: var(--thumb-size);
                height: var(--thumb-size);
                background: var(--track-fill);
                border-radius: 50%;
                box-shadow: 0 8px 18px -6px rgba(99, 102, 241, 0.6);
                border: 2px solid #fff;
            }
        </style>
        <?php
    }

    private function is_store_directory(): bool
    {
        if (function_exists('wcfmmp_is_stores_list_page') && wcfmmp_is_stores_list_page()) {
            return true;
        }

        if (is_page()) {
            $page = get_queried_object();
            if ($page instanceof \WP_Post) {
                $slug = $page->post_name;
                if ($slug === 'comercios') {
                    return true;
                }
            }
        }

        return false;
    }
}


