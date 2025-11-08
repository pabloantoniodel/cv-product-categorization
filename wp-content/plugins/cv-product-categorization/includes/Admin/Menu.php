<?php
declare(strict_types=1);

namespace Cv\ProductCategorization\Admin;

use Cv\ProductCategorization\Processors\OtrosCleaner;
use Cv\ProductCategorization\Processors\SectorAssigner;

final class Menu
{
    private const NOTICE_TRANSIENT = 'cv_cat_toolkit_notice';
    private const NOTICE_TTL       = 60;

    public static function init(): void
    {
        \add_action('admin_menu', [self::class, 'register_menu']);
        \add_action('admin_post_cv_cat_assign_sector', [self::class, 'handle_assign_sector']);
        \add_action('admin_post_cv_cat_vaciar_otros', [self::class, 'handle_vaciar_otros']);
        \add_action('admin_notices', [self::class, 'maybe_render_notice']);
    }

    public static function register_menu(): void
    {
        \add_menu_page(
            \__('CV Categorización', 'cv-product-categorization'),
            \__('CV Categorización', 'cv-product-categorization'),
            'manage_options',
            'cv-cat-toolkit',
            [self::class, 'render_main_page'],
            'dashicons-category',
            56
        );
    }

    public static function render_main_page(): void
    {
        if (!\current_user_can('manage_options')) {
            \wp_die(\esc_html__('No tienes permisos suficientes para acceder a esta página.', 'cv-product-categorization'));
        }

        $assign_url = \admin_url('admin-post.php');
        $vaciar_url = \admin_url('admin-post.php');
        $per_page   = isset($_POST['per_page']) ? (int) $_POST['per_page'] : 200;
        $offset     = isset($_POST['offset']) ? (int) $_POST['offset'] : 0;
        $limit      = isset($_POST['limit']) ? (int) $_POST['limit'] : 200;
        $apply      = !empty($_POST['apply']);
        ?>
        <div class="wrap">
            <h1><?php \esc_html_e('Herramientas de Categorización', 'cv-product-categorization'); ?></h1>
            <p><?php \esc_html_e('Ejecuta las rutinas inteligentes para mantener las categorías de WooCommerce organizadas.', 'cv-product-categorization'); ?></p>

            <hr />

            <h2><?php \esc_html_e('Asignar categorías Sector (extra) y principales', 'cv-product-categorization'); ?></h2>
            <p><?php \esc_html_e('Analiza todos los productos y asegura que cada ficha tenga una categoría “Sector” adicional junto a su categoría principal. Útil tras nuevas altas masivas.', 'cv-product-categorization'); ?></p>
            <form method="post" action="<?php echo \esc_url($assign_url); ?>">
                <?php \wp_nonce_field('cv_cat_assign_sector'); ?>
                <input type="hidden" name="action" value="cv_cat_assign_sector" />
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php \esc_html_e('Productos por lote', 'cv-product-categorization'); ?></th>
                        <td>
                            <input name="per_page" type="number" min="1" max="500" value="<?php echo \esc_attr($per_page); ?>" class="small-text" />
                            <p class="description"><?php \esc_html_e('Controla cuántos productos se procesan por iteración para evitar timeouts.', 'cv-product-categorization'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php \submit_button(\__('Ejecutar asignación de sectores', 'cv-product-categorization')); ?>
            </form>

            <hr />

            <h2><?php \esc_html_e('Vaciar “Otros productos y servicios”', 'cv-product-categorization'); ?></h2>
            <p><?php \esc_html_e('Revisa los productos que quedan en la categoría genérica y los mueve automáticamente a categorías concretas (máximo 5 por ficha).', 'cv-product-categorization'); ?></p>
            <form method="post" action="<?php echo \esc_url($vaciar_url); ?>">
                <?php \wp_nonce_field('cv_cat_vaciar_otros'); ?>
                <input type="hidden" name="action" value="cv_cat_vaciar_otros" />
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php \esc_html_e('Offset inicial', 'cv-product-categorization'); ?></th>
                        <td>
                            <input name="offset" type="number" min="0" value="<?php echo \esc_attr($offset); ?>" class="small-text" />
                            <p class="description"><?php \esc_html_e('Permite continuar donde se quedó un proceso anterior.', 'cv-product-categorization'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php \esc_html_e('Límite por ejecución', 'cv-product-categorization'); ?></th>
                        <td>
                            <input name="limit" type="number" min="1" max="500" value="<?php echo \esc_attr($limit); ?>" class="small-text" />
                            <p class="description"><?php \esc_html_e('Número de productos dentro de “Otros” que se procesarán en esta pasada.', 'cv-product-categorization'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php \esc_html_e('Aplicar cambios', 'cv-product-categorization'); ?></th>
                        <td>
                            <label>
                                <input name="apply" type="checkbox" value="1" <?php \checked($apply); ?> />
                                <?php \esc_html_e('Marca esta casilla para guardar los cambios. Si la dejas desmarcada, se ejecutará en modo prueba (solo logs).', 'cv-product-categorization'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php \submit_button(\__('Vaciar categoría “Otros”', 'cv-product-categorization'), 'primary', 'submit', false); ?>
            </form>
        </div>
        <?php
    }

    public static function handle_assign_sector(): void
    {
        self::check_permissions();
        \check_admin_referer('cv_cat_assign_sector');

        $perPage = isset($_POST['per_page']) ? (int) $_POST['per_page'] : 200;
        $perPage = $perPage > 0 ? $perPage : 200;

        $assigner = new SectorAssigner();
        $output   = self::run_with_buffer(static function () use ($assigner, $perPage) {
            $assigner->run([
                'per_page' => $perPage,
            ]);
        });

        self::store_notice(
            __('Asignación de sectores completada.', 'cv-product-categorization'),
            $output
        );

        \wp_safe_redirect(self::admin_page_url());
        exit;
    }

    public static function handle_vaciar_otros(): void
    {
        self::check_permissions();
        \check_admin_referer('cv_cat_vaciar_otros');

        $offset = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;
        $limit  = isset($_POST['limit']) ? max(1, (int) $_POST['limit']) : 200;
        $apply  = !empty($_POST['apply']);

        $cleaner = new OtrosCleaner($apply);
        $output  = self::run_with_buffer(static function () use ($cleaner, $offset, $limit) {
            $cleaner->run($offset, $limit);
        });

        $message = $apply
            ? __('Vaciado ejecutado y aplicado correctamente.', 'cv-product-categorization')
            : __('Vaciar “Otros” se ejecutó en modo prueba (sin cambios guardados).', 'cv-product-categorization');

        self::store_notice($message, $output, $apply ? 'success' : 'warning');

        \wp_safe_redirect(self::admin_page_url());
        exit;
    }

    public static function maybe_render_notice(): void
    {
        $notice = \get_transient(self::NOTICE_TRANSIENT);
        if (!$notice || empty($_GET['page']) || $_GET['page'] !== 'cv-cat-toolkit') {
            return;
        }

        \delete_transient(self::NOTICE_TRANSIENT);

        $type    = $notice['type'] ?? 'success';
        $message = $notice['message'] ?? '';
        $output  = $notice['output'] ?? '';

        printf('<div class="notice notice-%1$s is-dismissible">', \esc_attr($type));
        if ($message) {
            printf('<p><strong>%s</strong></p>', \esc_html($message));
        }
        if ($output) {
            printf('<pre style="max-height:300px; overflow:auto; background:#f6f7f7; padding:12px;">%s</pre>', \esc_html($output));
        }
        echo '</div>';
    }

    private static function check_permissions(): void
    {
        if (!\current_user_can('manage_options')) {
            \wp_die(\esc_html__('No tienes permisos suficientes para ejecutar esta acción.', 'cv-product-categorization'));
        }
    }

    private static function run_with_buffer(callable $callback): string
    {
        ob_start();
        try {
            $callback();
        } finally {
            $output = ob_get_clean();
        }

        $output = trim((string) $output);
        if (strlen($output) > 4000) {
            $output = substr($output, -4000);
        }

        return $output;
    }

    private static function store_notice(string $message, string $output = '', string $type = 'success'): void
    {
        \set_transient(
            self::NOTICE_TRANSIENT,
            [
                'message' => $message,
                'output'  => $output,
                'type'    => $type,
            ],
            self::NOTICE_TTL
        );
    }

    private static function admin_page_url(): string
    {
        return \admin_url('admin.php?page=cv-cat-toolkit');
    }
}
