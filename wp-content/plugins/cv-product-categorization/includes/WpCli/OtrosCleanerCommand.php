<?php
declare(strict_types=1);

namespace Cv\ProductCategorization\WpCli;

use Cv\ProductCategorization\Processors\OtrosCleaner;
use WP_CLI;
use WP_CLI_Command;

final class OtrosCleanerCommand extends WP_CLI_Command
{
    /**
     * Vacía la categoría "Otros productos y servicios" reasignando productos.
     *
     * ## OPTIONS
     *
     * [--offset=<number>]
     * : Offset inicial (default: 0).
     *
     * [--limit=<number>]
     * : Número de productos a procesar en el lote (default: 200).
     *
     * [--apply]
     * : Si se especifica, aplica los cambios; de lo contrario es modo prueba.
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $offset = isset($assocArgs['offset']) ? (int) $assocArgs['offset'] : 0;
        $limit  = isset($assocArgs['limit']) ? (int) $assocArgs['limit'] : 200;
        $apply  = isset($assocArgs['apply']) ? (bool) $assocArgs['apply'] : false;

        $cleaner = new OtrosCleaner($apply);
        $cleaner->run($offset, $limit);

        WP_CLI::success('Proceso de vaciado de Otros finalizado.');
    }
}

