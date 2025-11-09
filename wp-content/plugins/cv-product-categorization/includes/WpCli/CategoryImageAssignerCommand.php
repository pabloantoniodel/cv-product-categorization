<?php
declare(strict_types=1);

namespace Cv\ProductCategorization\WpCli;

use Cv\ProductCategorization\Processors\CategoryImageAssigner;
use WP_CLI;

final class CategoryImageAssignerCommand
{
    /**
     * Asigna imágenes libres a las categorías (por defecto, solo las principales) que no tengan miniatura.
     *
     * ## OPTIONS
     *
     * [--parents-only=<bool>]
     * : Limita el proceso a categorías padre (default: true).
     *
     * [--limit=<number>]
     * : Número máximo de categorías a actualizar (0 = sin límite).
     *
     * [--force]
     * : Reemplaza la imagen existente si ya hay miniatura asignada.
     *
     * [--dry-run]
     * : Simula la ejecución sin descargar imágenes.
     *
     * ## EXAMPLES
     *
     *     wp cv-cat assign-category-images
     *     wp cv-cat assign-category-images --parents-only=false --limit=10
     *     wp cv-cat assign-category-images --force
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $options = [
            'parents_only' => $this->parseBoolOption($assocArgs, 'parents-only', true),
            'limit'        => isset($assocArgs['limit']) ? (int) $assocArgs['limit'] : 0,
            'force'        => isset($assocArgs['force']),
            'dry_run'      => isset($assocArgs['dry-run']) || isset($assocArgs['dry_run']),
        ];

        $processor = new CategoryImageAssigner();
        $processor->run($options);

        WP_CLI::success('Proceso completado.');
    }

    private function parseBoolOption(array $assocArgs, string $key, bool $default): bool
    {
        if (!isset($assocArgs[$key])) {
            return $default;
        }

        $value = $assocArgs[$key];

        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower((string) $value);

        if ($value === 'false' || $value === '0') {
            return false;
        }

        if ($value === 'true' || $value === '1') {
            return true;
        }

        return $default;
    }
}


