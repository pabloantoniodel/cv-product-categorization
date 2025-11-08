<?php
declare(strict_types=1);

namespace Cv\ProductCategorization\WpCli;

use Cv\ProductCategorization\Processors\SectorAssigner;
use WP_CLI;
use WP_CLI_Command;

final class SectorAssignerCommand extends WP_CLI_Command
{
    /**
     * Ejecuta la asignación inteligente de sectores y categorías base.
     *
     * ## OPTIONS
     *
     * [--per-page=<number>]
     * : Número de productos a procesar por lote (default: 200).
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $perPage = isset($assocArgs['per-page']) ? (int) $assocArgs['per-page'] : 200;

        $assigner = new SectorAssigner();
        $assigner->run([
            'per_page' => $perPage,
        ]);

        WP_CLI::success('Asignación de sectores completada.');
    }
}

