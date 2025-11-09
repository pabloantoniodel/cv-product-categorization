<?php
declare(strict_types=1);

namespace Cv\ProductCategorization;

use Cv\ProductCategorization\Admin\Menu;
use Cv\ProductCategorization\Admin\VendorSectors;
use WP_CLI;

final class Plugin
{
    public static function init(): void
    {
        VendorSectors::init();

        if (\is_admin()) {
            Menu::init();
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
            WP_CLI::add_command(
                'cv-cat sync-vendor-sectors',
                [new WpCli\VendorSectorSyncCommand(), '__invoke'],
                [
                    'shortdesc' => 'Sincroniza el sector principal de cada vendedor según sus productos publicados.',
                ]
            );
            WP_CLI::add_command(
                'cv-cat assign-category-images',
                [new WpCli\CategoryImageAssignerCommand(), '__invoke'],
                [
                    'shortdesc' => 'Asigna imágenes libres a las categorías que no tienen miniatura (fuente Openverse).',
                ]
            );
        }
    }
}

