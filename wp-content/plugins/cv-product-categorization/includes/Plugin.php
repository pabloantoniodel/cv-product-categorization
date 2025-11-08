<?php
declare(strict_types=1);

namespace Cv\ProductCategorization;

use WP_CLI;

final class Plugin
{
    public static function init(): void
    {
        if (\is_admin()) {
            Admin\Menu::init();
        }

        if (defined('WP_CLI') && WP_CLI) {
            self::register_cli_commands();
        }
    }

    private static function register_cli_commands(): void
    {
        if (class_exists(WP_CLI::class)) {
            WP_CLI::add_command(
                'cv-cat assign-sector',
                [new WpCli\SectorAssignerCommand(), '__invoke'],
                [
                    'shortdesc' => 'Asigna categorías Sector y principales a los productos según reglas inteligentes.',
                ]
            );
            WP_CLI::add_command(
                'cv-cat vaciar-otros',
                [new WpCli\OtrosCleanerCommand(), '__invoke'],
                [
                    'shortdesc' => 'Reubica productos fuera de "Otros productos y servicios" aplicando reglas específicas.',
                ]
            );
        }
    }
}

