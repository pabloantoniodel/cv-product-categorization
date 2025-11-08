<?php
declare(strict_types=1);

namespace Cv\ProductCategorization\WpCli;

use Cv\ProductCategorization\Processors\VendorSectorSync;
use WP_CLI;

final class VendorSectorSyncCommand
{
    /**
     * Ejecuta la sincronización de sectores de vendedores.
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $processor = new VendorSectorSync();
        $processor->run([
            'roles' => $assocArgs['roles'] ?? ['wcfm_vendor', 'vendor', 'seller', 'shop_vendor'],
        ]);
        WP_CLI::success('Sincronización de sectores de vendedores completada.');
    }
}
