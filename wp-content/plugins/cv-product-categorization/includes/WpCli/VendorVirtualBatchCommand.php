<?php
declare(strict_types=1);

namespace Cv\ProductCategorization\WpCli;

use Cv\ProductCategorization\Processors\VendorVirtualBatch;
use WP_CLI;

final class VendorVirtualBatchCommand
{
    /**
     * Marca a los vendedores recientes como agentes comerciales sin tienda física.
     *
     * ## OPTIONS
     *
     * [--since=<date>]
     * : Fecha de corte (formato Y-m-d). Por defecto, 30 días atrás.
     *
     * [--days=<days>]
     * : Días hacia atrás para buscar vendedores (si no se proporciona --since). Por defecto 30.
     *
     * [--roles=<roles>]
     * : Lista de roles separada por coma. Por defecto wcfm_vendor,vendor,seller,shop_vendor.
     *
     * ## EXAMPLES
     *
     *     wp cv-cat mark-virtual-agents
     *     wp cv-cat mark-virtual-agents --days=45
     *     wp cv-cat mark-virtual-agents --since=2025-01-01
     *     wp cv-cat mark-virtual-agents --roles=wcfm_vendor,shop_vendor
     *
     * @param array<int,string> $args
     * @param array<string,mixed> $assocArgs
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $processor = new VendorVirtualBatch();
        $processor->run($assocArgs);

        WP_CLI::success('Proceso completado.');
    }
}


