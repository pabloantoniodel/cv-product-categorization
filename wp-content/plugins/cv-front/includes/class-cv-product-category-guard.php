<?php
/**
 * Evitar que se guarden productos sin categoría y avisar al usuario.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CV_Product_Category_Guard {

    public function __construct() {
        add_action( 'save_post_product', [ $this, 'enforce_category_requirement' ], 10, 3 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_script' ] );
    }

    /**
     * Impide guardar un producto sin al menos una categoría.
     */
    public function enforce_category_requirement( $post_id, $post, $update ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        if ( empty( $_POST ) ) {
            return;
        }

        $terms = wp_get_post_terms( $post_id, 'product_cat', [ 'fields' => 'ids' ] );

        if ( empty( $terms ) ) {
            $message  = '<h1>' . esc_html__( 'Se requiere al menos una categoría', 'cv-front' ) . '</h1>';
            $message .= '<p>' . esc_html__( 'Debes asignar al menos una categoría de producto antes de guardar los cambios.', 'cv-front' ) . '</p>';
            $edit_link = get_edit_post_link( $post_id, 'raw' );
            if ( $edit_link ) {
                $message .= '<p><a href="' . esc_url( $edit_link ) . '">' . esc_html__( 'Volver al producto', 'cv-front' ) . '</a></p>';
            }

            wp_die( $message, esc_html__( 'Categoría obligatoria', 'cv-front' ), [ 'back_link' => true ] );
        }
    }

    /**
     * Inyecta un aviso en el editor para evitar enviar el formulario sin categorías marcadas.
     */
    public function enqueue_admin_script( $hook ) {
        $screen = get_current_screen();

        if ( ! $screen || 'product' !== $screen->post_type ) {
            return;
        }

        $handle = 'cv-product-category-guard';
        wp_register_script( $handle, '', [], false, true );

        $script = <<<'JS'
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('post');
            if (!form) {
                return;
            }

            form.addEventListener('submit', function (event) {
                var checked = document.querySelectorAll('#product_catchecklist input[type="checkbox"]:checked');

                if (!checked.length) {
                    event.preventDefault();
                    event.stopPropagation();
                    alert('Debes seleccionar al menos una categoría de producto antes de guardar.');

                    var box = document.getElementById('product_catdiv');
                    if (box) {
                        box.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            }, true);
        });
JS;
        wp_add_inline_script( $handle, $script );
        wp_enqueue_script( $handle );
    }
}
