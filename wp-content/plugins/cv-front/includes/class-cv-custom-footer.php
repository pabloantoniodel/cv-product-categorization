<?php
/**
 * Footer moderno para Ciudad Virtual.
 *
 * @package CV_Front
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Custom_Footer {
    public function __construct() {
        add_action('init', array($this, 'setup_hooks'), 20);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function setup_hooks() {
        remove_action('shopper_footer', 'shopper_footer_widgets', 10);
        remove_action('shopper_footer', 'shopper_credit', 20);
        remove_action('shopper_footer', 'shopper_footer_menu', 30);

        add_action('shopper_footer', array($this, 'render_modern_footer'), 10);
    }

    public function enqueue_assets() {
        $style_path = CV_FRONT_PLUGIN_DIR . 'assets/css/cv-footer.css';
        $style_url  = CV_FRONT_PLUGIN_URL . 'assets/css/cv-footer.css';

        if (file_exists($style_path)) {
            wp_enqueue_style(
                'cv-footer-styles',
                $style_url,
                array(),
                filemtime($style_path)
            );
        }
    }

    public function render_modern_footer() {
        $blog_name = get_bloginfo('name');
        $tagline   = get_bloginfo('description');

        $primary_menu   = $this->get_menu_markup('primary', 'cv-footer__menu');
        $secondary_menu = $this->get_menu_markup('secondary', 'cv-footer__menu');
        $footer_menu    = $this->get_menu_markup('footer', 'cv-footer__menu cv-footer__menu--inline');
        $social_menu    = $this->get_menu_markup('social', 'cv-footer__social');

        $account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : '';
        if (empty($account_url) || is_wp_error($account_url)) {
            $account_url = home_url('/my-account/');
        }
        $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');

        $about_url       = esc_url(home_url('/condiciones-generales-de-uso-y-contratacion/'));
        $contact_url     = esc_url(home_url('/contacto/'));
        $vendors_url     = esc_url(home_url('/asociados/'));
        $market_url      = esc_url(home_url('/market/'));
        $news_url        = esc_url(home_url('/noticias/'));
        $privacy_url     = esc_url(home_url('/politica-de-privacidad/'));
        $terms_url       = $about_url;
        $tutorial_url    = esc_url(home_url('/tutorial/'));
        $qr_ticket_url   = esc_url(home_url('/qr-ticket/'));
        $newsletter_url  = esc_url(home_url('/contacto/?motivo=suscripcion'));

        ?>
        <div class="cv-footer">
            <div class="cv-footer__inner">
                <div class="cv-footer__brand">
                    <?php if (has_custom_logo()) : ?>
                        <div class="cv-footer__logo"><?php echo get_custom_logo(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                    <?php else : ?>
                        <span class="cv-footer__brand-name"><?php echo esc_html($blog_name); ?></span>
                    <?php endif; ?>
                    <?php if ($tagline) : ?>
                        <p class="cv-footer__tagline"><?php echo esc_html($tagline); ?></p>
                    <?php endif; ?>
                    <div class="cv-footer__cta-list">
                        <a class="cv-footer__cta-button" href="<?php echo esc_url($vendors_url); ?>">
                            Explorar Comercios
                        </a>
                        <a class="cv-footer__cta-link" href="<?php echo esc_url($contact_url); ?>">
                            ¿Necesitas ayuda? Contáctanos
                        </a>
                    </div>
                </div>

                <div class="cv-footer__columns">
                    <?php if (!empty($primary_menu)) : ?>
                        <div class="cv-footer__column">
                            <h4 class="cv-footer__heading">Navega</h4>
                            <?php echo $primary_menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($secondary_menu)) : ?>
                        <div class="cv-footer__column">
                            <h4 class="cv-footer__heading">Destacados</h4>
                            <?php echo $secondary_menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    <?php else : ?>
                        <div class="cv-footer__column">
                            <h4 class="cv-footer__heading">Destacados</h4>
                            <ul class="cv-footer__menu">
                                <li><a href="<?php echo esc_url($market_url); ?>">Market</a></li>
                                <li><a href="<?php echo esc_url($news_url); ?>">Noticias</a></li>
                                <li><a href="<?php echo esc_url($tutorial_url); ?>">Tutorial</a></li>
                                <li><a href="<?php echo esc_url($qr_ticket_url); ?>">QR Ticket</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="cv-footer__column">
                        <h4 class="cv-footer__heading">Cuenta</h4>
                        <ul class="cv-footer__menu">
                            <li><a href="<?php echo esc_url($account_url); ?>">Mi cuenta</a></li>
                            <li><a href="<?php echo esc_url($cart_url); ?>">Carrito</a></li>
                            <li><a href="<?php echo esc_url(home_url('/shop/')); ?>">Productos</a></li>
                            <li><a href="<?php echo esc_url($news_url); ?>">Noticias</a></li>
                        </ul>
                        <div class="cv-footer__extra-links">
                            <a href="<?php echo esc_url($about_url); ?>">Acerca de</a>
                            <a href="<?php echo esc_url($contact_url); ?>">Contacto</a>
                        </div>
                    </div>

                    <div class="cv-footer__column cv-footer__column--social">
                        <h4 class="cv-footer__heading">Síguenos</h4>
                        <?php if (!empty($social_menu)) : ?>
                            <?php echo $social_menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php else : ?>
                            <p class="cv-footer__social-placeholder">
                                Encuéntranos en redes sociales y mantente al día con novedades y promociones.
                            </p>
                        <?php endif; ?>
                        <div class="cv-footer__newsletter">
                            <p>Recibe novedades y promociones en tu correo.</p>
                            <a class="cv-footer__cta-button cv-footer__cta-button--outline" href="<?php echo esc_url($newsletter_url); ?>">
                                Suscribirme
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cv-footer__bottom">
                <div class="cv-footer__legal">
                    <span>© <?php echo esc_html(date_i18n('Y')); ?> <?php echo esc_html($blog_name); ?>. Todos los derechos reservados.</span>
                </div>
                <div class="cv-footer__legal-links">
                    <a href="<?php echo esc_url($terms_url); ?>">Términos y condiciones</a>
                    <span aria-hidden="true">•</span>
                    <a href="<?php echo esc_url($privacy_url); ?>">Política de privacidad</a>
                    <?php if (!empty($footer_menu)) : ?>
                        <span aria-hidden="true">•</span>
                        <?php echo $footer_menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function get_menu_markup($location, $class = '') {
        if (!has_nav_menu($location)) {
            return '';
        }

        return wp_nav_menu(
            array(
                'theme_location'  => $location,
                'container'       => '',
                'menu_class'      => $class,
                'depth'           => 1,
                'echo'            => false,
                'fallback_cb'     => '__return_empty_string',
                'link_before'     => '<span>',
                'link_after'      => '</span>',
            )
        );
    }
}

new CV_Custom_Footer();

