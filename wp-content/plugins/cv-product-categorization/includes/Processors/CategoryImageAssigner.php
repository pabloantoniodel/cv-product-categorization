<?php
declare(strict_types=1);

namespace Cv\ProductCategorization\Processors;

use WP_CLI;
use WP_Error;
use WP_Term;

final class CategoryImageAssigner
{
    private const API_ENDPOINT = 'https://api.openverse.engineering/v1/images/';

    private bool $dryRun = false;
    private bool $force = false;
    private bool $parentsOnly = true;
    private int $limit = 0;

    /** @var array<string,array<int,string>> */
    private array $queryOverrides = [
        'moda-calzado'                 => ['fashion storefront', 'shoe fashion store'],
        'zapateria-moda-calzado'       => ['shoe store exterior', 'shoe shop storefront'],
        'moda-mujer'                   => ['women fashion boutique', 'women clothing store'],
        'lenceria-moda-calzado'        => ['lingerie boutique', 'lingerie store interior'],
        'alimentacion'                 => ['grocery store', 'fresh produce market'],
        'alimentacion-restauracion'    => ['restaurant exterior', 'food market storefront'],
        'hogar-decoracion'             => ['home decor store', 'furniture showroom'],
        'juguetes'                     => ['toy store', 'kids toy shop'],
        'deportes'                     => ['sports shop exterior', 'sporting goods store'],
        'servicios-profesionales'      => ['business office meeting', 'professional services team'],
        'salud-bienestar'              => ['wellness spa exterior', 'wellness center'],
        'regalos-complementos'         => ['gift shop storefront', 'gift boutique exterior', 'gift store interior'],
        'regalos-y-complementos'       => ['gift shop storefront', 'gift boutique exterior', 'gift store interior'],
        'regalos-y-complementos-2'     => ['gift shop storefront', 'gift boutique exterior', 'gift store interior', 'gift store outside'],
        'inmobiliaria'                 => ['modern apartment building', 'real estate building exterior'],
        'mascotas'                     => ['pet store', 'pet shop interior'],
        'tecnologia'                   => ['electronics store', 'technology shop'],
        'sector'                       => ['city street businesses', 'retail businesses'],
    ];

    /** @var array<int,string> */
    private array $processed = [];

    /** @var array<int,string> */
    private array $assigned = [];

    /** @var array<int,string> */
    private array $skipped = [];

    /**
     * @param array<string,mixed> $options
     */
    public function run(array $options = []): void
    {
        $this->dryRun     = (bool) ($options['dry_run'] ?? false);
        $this->force      = (bool) ($options['force'] ?? false);
        $this->parentsOnly = (bool) ($options['parents_only'] ?? true);
        $this->limit      = max(0, (int) ($options['limit'] ?? 0));

        $terms = $this->getCandidateTerms();
        if ($terms instanceof WP_Error) {
            $this->log('⚠️ Error obteniendo categorías: ' . $terms->get_error_message());
            return;
        }

        if (empty($terms)) {
            $this->log('✅ No se encontraron categorías pendientes.');
            return;
        }

        $this->ensureMediaDependencies();

        foreach ($terms as $term) {
            if ($this->limit > 0 && count($this->assigned) >= $this->limit) {
                break;
            }
            $this->processed[$term->term_id] = $term->name;

            $currentThumb = (int) get_term_meta($term->term_id, 'thumbnail_id', true);
            if ($currentThumb > 0 && !$this->force) {
                $this->skipped[$term->term_id] = $term->name . ' (ya tiene imagen)';
                continue;
            }

            if ($this->dryRun) {
                $this->assigned[$term->term_id] = $term->name . ' (simulado)';
                $this->log("🔎 [Simulación] {$term->name} → se buscaría una imagen libre en Openverse.");
                continue;
            }

            $attachmentId = $this->assignImageToTerm($term);
            if ($attachmentId > 0) {
                $this->assigned[$term->term_id] = $term->name;
            } else {
                $this->skipped[$term->term_id] = $term->name . ' (sin resultados adecuados)';
            }
        }

        $this->renderSummary();
    }

    /**
     * @return array<int,WP_Term>|WP_Error
     */
    private function getCandidateTerms()
    {
        $args = [
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ];

        if ($this->parentsOnly) {
            $args['parent'] = 0;
        }

        /** @var array<int,WP_Term>|WP_Error $terms */
        $terms = get_terms($args);

        return $terms;
    }

    private function assignImageToTerm(WP_Term $term): int
    {
        foreach ($this->buildQueries($term) as $query) {
            $image = $this->searchOpenverse($query);
            if ($image === null) {
                continue;
            }

            $attachmentId = $this->importImage($term, $image);
            if ($attachmentId > 0) {
                $this->log(sprintf('✅ %s → imagen "%s" (%s)', $term->name, $image['title'] ?? 'Sin título', $image['source'] ?? 'Openverse'));
                return $attachmentId;
            }
        }

        $this->log(sprintf('⚠️ %s → sin resultados adecuados en Openverse.', $term->name));
        return 0;
    }

    /**
     * @param array<string,mixed> $image
     */
    private function importImage(WP_Term $term, array $image): int
    {
        $url = $image['url'] ?? $image['thumbnail'] ?? null;
        if (!$url) {
            return 0;
        }

        $tmp = download_url($url);
        if ($tmp instanceof WP_Error) {
            $this->log(sprintf('⚠️ Error descargando %s: %s', $term->name, $tmp->get_error_message()));
            return 0;
        }

        $fileArray = [
            'name'     => sanitize_title($term->slug) . '-' . basename(parse_url($url, PHP_URL_PATH) ?? 'categoria.jpg'),
            'tmp_name' => $tmp,
        ];

        $attachmentId = media_handle_sideload($fileArray, 0, $image['title'] ?? $term->name);

        if ($attachmentId instanceof WP_Error) {
            $this->log(sprintf('⚠️ Error importando imagen para %s: %s', $term->name, $attachmentId->get_error_message()));
            @unlink($tmp);
            return 0;
        }

        update_term_meta($term->term_id, 'thumbnail_id', $attachmentId);
        update_post_meta($attachmentId, '_wp_attachment_image_alt', $term->name);

        return (int) $attachmentId;
    }

    /**
     * @return array<int,string|null>
     */
    private function searchOpenverse(string $query): ?array
    {
        $url = add_query_arg(
            [
                'q'           => $query,
                'license'     => 'cc0,pdm',
                'page_size'   => 5,
                'format'      => 'json',
                'order_by'    => 'relevance',
                'content_filter' => 'high',
            ],
            self::API_ENDPOINT
        );

        $response = wp_remote_get(
            $url,
            [
                'timeout'    => 20,
                'user-agent' => 'CV-Category-Image-Assigner/1.0 (+https://ciudadvirtual.store)',
            ]
        );

        if ($response instanceof WP_Error) {
            $this->log('⚠️ Error en la petición a Openverse: ' . $response->get_error_message());
            return null;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            $this->log('⚠️ Petición Openverse no exitosa (HTTP ' . $code . ')');
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        if (!$body) {
            return null;
        }

        $data = json_decode($body, true);
        if (!is_array($data) || empty($data['results']) || !is_array($data['results'])) {
            return null;
        }

        foreach ($data['results'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (empty($item['url'])) {
                continue;
            }

            return [
                'url'     => $item['url'],
                'title'   => $item['title'] ?? null,
                'source'  => $item['source'] ?? null,
                'license' => $item['license'] ?? null,
            ];
        }

        return null;
    }

    /**
     * @return array<int,string>
     */
    private function buildQueries(WP_Term $term): array
    {
        $queries = [];

        $name = trim($term->name);
        if ($name !== '') {
            $queries[] = $name;
            $queries[] = $name . ' tienda';
        }

        $slug = str_replace('-', ' ', $term->slug);
        if ($slug !== '' && strcasecmp($slug, $name) !== 0) {
            $queries[] = $slug;
        }

        if (isset($this->queryOverrides[$term->slug])) {
            $queries = array_merge($queries, $this->queryOverrides[$term->slug]);
        }

        $queries[] = $name . ' comercio local';

        return array_values(array_unique(array_filter($queries)));
    }

    private function ensureMediaDependencies(): void
    {
        if (!function_exists('media_handle_sideload')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }
    }

    private function renderSummary(): void
    {
        $this->log('--- Resumen ---');
        $this->log(sprintf('Procesadas: %d', count($this->processed)));
        $this->log(sprintf('Asignadas: %d', count($this->assigned)));
        $this->log(sprintf('Omitidas: %d', count($this->skipped)));

        if (!empty($this->assigned)) {
            $this->log('Imágenes asignadas:');
            foreach ($this->assigned as $termId => $label) {
                $this->log('  • ' . $label);
            }
        }

        if (!empty($this->skipped)) {
            $this->log('Categorías sin cambios:');
            foreach ($this->skipped as $termId => $reason) {
                $this->log('  • ' . $reason);
            }
        }
    }

    private function log(string $message): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::log($message);
            return;
        }

        error_log('[CategoryImageAssigner] ' . $message);
    }
}


