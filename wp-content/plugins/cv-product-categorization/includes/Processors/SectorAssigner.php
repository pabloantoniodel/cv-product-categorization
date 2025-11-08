<?php
declare(strict_types=1);

namespace Cv\ProductCategorization\Processors;

use RuntimeException;
use WP_CLI;
use WP_Error;
use WP_Term;

/**
 * Adaptación a clase de la lógica histórica de `asignar-sector-productos.php`.
 */
final class SectorAssigner
{
    private const DEFAULT_KEYWORD_WEIGHT  = 3;
    private const DEFAULT_CATEGORY_WEIGHT = 2;
    private const INMO_SECTOR_SLUG        = 'inmobiliaria-sector';

    private int $sectorId;

    /** @var array<string,int> */
    private array $sectorTermMap = [];

    /** @var array<int> */
    private array $allInmobiliariaAllowedIds = [];

    /** @var array<int> */
    private array $forcedHerbolarioVendors = [1172, 1203];

    /** @var array<int,array<string,mixed>> */
    private array $rules = [];

    public function __construct()
    {
        $this->bootstrapTaxonomy();
        $this->rules = include __DIR__ . '/../data/sector-rules.php';
    }

    /**
     * Ejecuta la reasignación de sectores y categorías base.
     *
     * @param array<string,mixed> $options
     */
    public function run(array $options = []): void
    {
        $perPage = (int) ($options['per_page'] ?? 200);
        $page    = 1;

        $processed = 0;
        $assigned  = 0;

        $perRuleStats = array_fill_keys(array_column($this->rules, 'slug'), 0);
        if (!array_key_exists(self::INMO_SECTOR_SLUG, $perRuleStats)) {
            $perRuleStats[self::INMO_SECTOR_SLUG] = 0;
        }

        do {
            $products = get_posts([
                'post_type'      => 'product',
                'post_status'    => ['publish', 'pending', 'draft'],
                'fields'         => 'ids',
                'posts_per_page' => $perPage,
                'paged'          => $page,
            ]);

            if (empty($products)) {
                break;
            }

            foreach ($products as $productId) {
                $processed++;
                if ($this->processProduct((int) $productId, $perRuleStats)) {
                    $assigned++;
                }
            }

            $page++;
        } while (count($products) === $perPage);

        $this->log('');
        $this->log('Resumen:');
        $this->log(sprintf('Productos procesados: %d', $processed));
        $this->log(sprintf('Productos asignados: %d', $assigned));
        foreach ($perRuleStats as $slug => $count) {
            if ($count > 0) {
                $this->log(sprintf('  - %s: %d', $slug, $count));
            }
        }
    }

    /**
     * @param array<string,int> $perRuleStats
     */
    private function processProduct(int $productId, array &$perRuleStats): bool
    {
        $terms = wp_get_post_terms($productId, 'product_cat');
        if (is_wp_error($terms)) {
            $this->warn(sprintf('No se pudo obtener términos del producto #%d: %s', $productId, $terms->get_error_message()));
            return false;
        }

        $currentSlugs        = [];
        $currentSectorSlugs  = [];
        $hasInmobiliaria     = false;
        $hasNonSector        = false;
        $skipSlugs           = ['inmobiliaria', 'alquiler', 'venta', 'traspaso', 'alquileres', self::INMO_SECTOR_SLUG];
        $productTitle        = get_the_title($productId) ?: '';

        foreach ($terms as $term) {
            $currentSlugs[] = $term->slug;
            $ancestors      = get_ancestors($term->term_id, 'product_cat');
            $isSector       = ((int) $term->parent === $this->sectorId) || (!empty($ancestors) && in_array($this->sectorId, $ancestors, true));

            if ($isSector) {
                $currentSectorSlugs[] = $term->slug;
            } else {
                $hasNonSector = true;
            }

            if (in_array($term->slug, $skipSlugs, true)) {
                $hasInmobiliaria = true;
                continue;
            }

            if (!empty($ancestors)) {
                foreach ($ancestors as $ancestorId) {
                    $ancestor = get_term($ancestorId, 'product_cat');
                    if ($ancestor && in_array($ancestor->slug, $skipSlugs, true)) {
                        $hasInmobiliaria = true;
                        break 2;
                    }
                }
            }
        }

        $searchText = $this->searchableText($this->gatherTextForProduct($productId, $terms));
        $authorId   = (int) get_post_field('post_author', $productId);

        if ($hasInmobiliaria) {
            $this->handleInmobiliaria($productId, $productTitle, $currentSlugs, $currentSectorSlugs, $searchText, $hasNonSector);
            return true;
        }

        $bestRule    = null;
        $bestScore   = 0;
        $bestReasons = [];

        foreach ($this->rules as $rule) {
            $slug = $rule['slug'];

            if (!isset($this->sectorTermMap[$slug])) {
                continue;
            }

            if (in_array($slug, $currentSlugs, true)) {
                continue;
            }

            if ($slug === 'herbolario' && $this->isForcedHerbolario($productId, $authorId)) {
                $bestRule    = $rule;
                $bestScore   = PHP_INT_MAX;
                $bestReasons = ['vendor_forzado'];
                break;
            }

            [$score, $reasons, $matchedCategory] = $this->evaluateRule($rule, $currentSlugs, $searchText);

            if ($score === null) {
                continue;
            }

            if (!empty($rule['require_category']) && !$matchedCategory) {
                continue;
            }

            if ($score > $bestScore) {
                $bestRule    = $rule;
                $bestScore   = $score;
                $bestReasons = $reasons;
            }
        }

        if ($bestRule === null) {
            if (!$hasNonSector) {
                $this->warn(sprintf('Producto #%d – "%s" sin categoría fuera de Sector', $productId, $productTitle));
            }
            return false;
        }

        $assigned = $this->applyBestRule(
            $productId,
            $productTitle,
            $bestRule,
            $bestScore,
            $bestReasons,
            $currentSlugs,
            $searchText,
            $hasNonSector
        );

        if ($assigned) {
            $perRuleStats[$bestRule['slug']]++;
        }

        return $assigned;
    }

    /**
     * @param WP_Term[] $terms
     */
    private function gatherTextForProduct(int $productId, array $terms): string
    {
        $pieces = [];
        $pieces[] = get_the_title($productId) ?: '';
        $pieces[] = get_post_field('post_excerpt', $productId) ?: '';
        $pieces[] = get_post_field('post_content', $productId) ?: '';
        $pieces[] = (string) get_post_meta($productId, '_sku', true);
        $pieces[] = (string) get_post_meta($productId, '_vendor_id', true);

        foreach ($terms as $term) {
            $pieces[] = $term->name;
            $pieces[] = $term->slug;
        }

        return implode(' ', array_filter($pieces));
    }

    private function handleInmobiliaria(
        int $productId,
        string $productTitle,
        array $currentSlugs,
        array $currentSectorSlugs,
        string $searchText,
        bool &$hasNonSector
    ): void {
        $mainInmoId = $this->resolveCategoryId('inmobiliaria');
        if ($mainInmoId && !in_array('inmobiliaria', $currentSlugs, true)) {
            $this->addTermById($productId, $mainInmoId);
            $hasNonSector = true;
            $currentSlugs[] = 'inmobiliaria';
        } elseif (in_array('inmobiliaria', $currentSlugs, true)) {
            $hasNonSector = true;
        }

        $operationSlug = $this->detectInmobiliariaOperation($searchText);
        if ($operationSlug && !in_array($operationSlug, $currentSlugs, true)) {
            $operationId = $this->resolveCategoryId($operationSlug);
            if ($operationId) {
                $this->addTermById($productId, $operationId);
                $hasNonSector = true;
                $currentSlugs[] = $operationSlug;
            }
        }

        if (isset($this->sectorTermMap[self::INMO_SECTOR_SLUG]) && !in_array(self::INMO_SECTOR_SLUG, $currentSectorSlugs, true)) {
            $this->addTermById($productId, $this->sectorTermMap[self::INMO_SECTOR_SLUG]);
            $this->log(sprintf('✅ Producto #%d – "%s" asignado a %s [inmobiliaria]', $productId, $productTitle, self::INMO_SECTOR_SLUG));
        }

        if (!$hasNonSector) {
            $this->warn(sprintf('Producto #%d – "%s" sin categoría fuera de Sector', $productId, $productTitle));
        }

        if (!empty($this->allInmobiliariaAllowedIds)) {
            $this->cleanNonInmobiliariaTerms($productId, $productTitle);
        }
    }

    private function addTermById(int $productId, int $termId): void
    {
        $result = wp_add_object_terms($productId, $termId, 'product_cat');
        if (is_wp_error($result)) {
            $this->warn(sprintf('No se pudo añadir el término %d al producto #%d: %s', $termId, $productId, $result->get_error_message()));
        }
    }

    private function cleanNonInmobiliariaTerms(int $productId, string $productTitle): void
    {
        $terms = wp_get_post_terms($productId, 'product_cat');
        if (is_wp_error($terms)) {
            return;
        }

        $toRemove = [];

        foreach ($terms as $term) {
            $termId = (int) $term->term_id;
            if (in_array($termId, $this->allInmobiliariaAllowedIds, true)) {
                continue;
            }

            $ancestors   = get_ancestors($termId, 'product_cat');
            $keep        = false;

            foreach ($ancestors as $ancestorId) {
                if (in_array((int) $ancestorId, $this->allInmobiliariaAllowedIds, true)) {
                    $keep = true;
                    break;
                }
            }

            if (!$keep) {
                $toRemove[] = $termId;
            }
        }

        if (!empty($toRemove)) {
            $result = wp_remove_object_terms($productId, array_unique($toRemove), 'product_cat');
            if (!is_wp_error($result)) {
                $this->log(sprintf('♻️ Producto #%d – "%s" limpiado de categorías ajenas a Inmobiliaria', $productId, $productTitle));
            }
        }
    }

    /**
     * @param array<string,mixed> $rule
     * @param array<int,string>   $currentSlugs
     * @param string              $searchText
     * @return array{?int, array<int,string>, bool}
     */
    private function evaluateRule(array $rule, array $currentSlugs, string $searchText): array
    {
        $slug            = $rule['slug'];
        $keywordWeight   = $rule['keyword_weight'] ?? self::DEFAULT_KEYWORD_WEIGHT;
        $categoryWeight  = $rule['category_weight'] ?? self::DEFAULT_CATEGORY_WEIGHT;
        $threshold       = $rule['threshold'] ?? 4;
        $score           = 0;
        $reasons         = [];
        $matchedCategory = false;

        if (!empty($rule['category_slugs'])) {
            foreach ($rule['category_slugs'] as $catSlug) {
                if (in_array($catSlug, $currentSlugs, true)) {
                    $score += $categoryWeight;
                    $reasons[] = 'cat:' . $catSlug;
                    $matchedCategory = true;
                }
            }
        }

        $keywordHits = 0;
        if (!empty($rule['keywords'])) {
            foreach ($rule['keywords'] as $keyword) {
                $keywordClean = $this->searchableText($keyword);
                if ($keywordClean === '  ') {
                    continue;
                }
                if (strpos($searchText, $keywordClean) !== false) {
                    $score += $keywordWeight;
                    $keywordHits++;
                    $reasons[] = 'kw:' . trim($keyword);
                }
            }
        }

        $requiredHits = $rule['min_keyword_hits'] ?? 0;
        if ($keywordHits < $requiredHits) {
            return [null, [], false];
        }

        if (!empty($rule['must_keywords'])) {
            $mustOk = false;
            foreach ($rule['must_keywords'] as $mustKeyword) {
                $mustClean = $this->searchableText($mustKeyword);
                if ($mustClean !== '  ' && strpos($searchText, $mustClean) !== false) {
                    $mustOk = true;
                    break;
                }
            }
            if (!$mustOk) {
                return [null, [], false];
            }
        }

        if (!empty($rule['exclude_keywords'])) {
            foreach ($rule['exclude_keywords'] as $badKeyword) {
                $badClean = $this->searchableText($badKeyword);
                if ($badClean !== '  ' && strpos($searchText, $badClean) !== false) {
                    return [null, [], false];
                }
            }
        }

        if ($slug === self::INMO_SECTOR_SLUG) {
            $operation = $this->detectInmobiliariaOperation($searchText);
            if ($operation === null) {
                return [null, [], false];
            }
            $reasons[] = 'op:' . $operation;
            $score += 5;
        }

        if ($score < $threshold) {
            return [null, [], false];
        }

        return [$score, $reasons, $matchedCategory];
    }

    /**
     * @param array<string,mixed> $bestRule
     * @param array<int,string>   $currentSlugs
     */
    private function applyBestRule(
        int $productId,
        string $productTitle,
        array $bestRule,
        int $bestScore,
        array $bestReasons,
        array &$currentSlugs,
        string $searchText,
        bool &$hasNonSector
    ): bool {
        $sectorTermId = $this->sectorTermMap[$bestRule['slug']] ?? null;
        if (!$sectorTermId) {
            return false;
        }

        $result = wp_add_object_terms($productId, $sectorTermId, 'product_cat');
        if (is_wp_error($result)) {
            $this->warn(sprintf('No se pudo asignar %s al producto #%d: %s', $bestRule['slug'], $productId, $result->get_error_message()));
            return false;
        }

        $this->log(sprintf(
            '✅ Producto #%d – "%s" asignado a %s (puntuación %d) [%s]',
            $productId,
            $productTitle,
            $bestRule['slug'],
            $bestScore,
            implode(', ', $bestReasons)
        ));

        $baseAssigned = [];
        if (!empty($bestRule['category_slugs'])) {
            foreach ($bestRule['category_slugs'] as $catSlug) {
                $catId = $this->resolveCategoryId($catSlug);
                if (!$catId) {
                    continue;
                }
                if (!in_array($catSlug, $currentSlugs, true)) {
                    $res = wp_add_object_terms($productId, $catId, 'product_cat');
                    if (!is_wp_error($res)) {
                        $baseAssigned[] = $catSlug;
                        $currentSlugs[] = $catSlug;
                        $hasNonSector = true;
                    }
                } else {
                    $hasNonSector = true;
                }
            }
        }

        if (!empty($baseAssigned)) {
            $this->log('   → Añadidas categorías base: ' . implode(', ', $baseAssigned));
        }

        if ($bestRule['slug'] === self::INMO_SECTOR_SLUG) {
            $operationSlug = $this->detectInmobiliariaOperation($searchText);
            if ($operationSlug && !in_array($operationSlug, $currentSlugs, true)) {
                $operationId = $this->resolveCategoryId($operationSlug);
                if ($operationId) {
                    $opRes = wp_add_object_terms($productId, $operationId, 'product_cat');
                    if (!is_wp_error($opRes)) {
                        $currentSlugs[] = $operationSlug;
                        $hasNonSector = true;
                        $this->log('   → Añadida categoría de operación: ' . $operationSlug);
                    }
                }
            }
        }

        if (!$hasNonSector) {
            $this->warn(sprintf('Producto #%d – "%s" sin categoría fuera de Sector', $productId, $productTitle));
        }

        return true;
    }

    private function isForcedHerbolario(int $productId, int $authorId): bool
    {
        $vendorMeta = (int) get_post_meta($productId, '_vendor_id', true);
        return in_array($vendorMeta, $this->forcedHerbolarioVendors, true)
            || in_array($authorId, $this->forcedHerbolarioVendors, true);
    }

    private function bootstrapTaxonomy(): void
    {
        $sector = get_term_by('slug', 'sector', 'product_cat');
        if (!$sector || is_wp_error($sector)) {
            throw new RuntimeException("No se encontró la categoría padre 'Sector'.");
        }

        $this->sectorId = (int) $sector->term_id;

        $children = get_terms([
            'taxonomy'   => 'product_cat',
            'parent'     => $this->sectorId,
            'hide_empty' => false,
        ]);

        if (empty($children) || is_wp_error($children)) {
            throw new RuntimeException("No hay subcategorías bajo 'Sector'.");
        }

        foreach ($children as $child) {
            $this->sectorTermMap[$child->slug] = (int) $child->term_id;
        }

        $mainInmoId   = $this->resolveCategoryId('inmobiliaria') ?? 0;
        $sectorInmoId = $this->sectorTermMap[self::INMO_SECTOR_SLUG] ?? 0;

        $allowed = [];

        if ($mainInmoId > 0) {
            $allowed = array_map('intval', get_term_children($mainInmoId, 'product_cat'));
            $allowed[] = $mainInmoId;
        }

        if ($sectorInmoId > 0) {
            $sectorChildren = array_map('intval', get_term_children($sectorInmoId, 'product_cat'));
            $sectorChildren[] = $sectorInmoId;
            $allowed = array_merge($allowed, $sectorChildren);
        }

        $this->allInmobiliariaAllowedIds = array_values(array_unique($allowed));
    }

    private function resolveCategoryId(string $slug): ?int
    {
        static $cache = [];
        if (array_key_exists($slug, $cache)) {
            return $cache[$slug];
        }

        $term = get_term_by('slug', $slug, 'product_cat');
        if ($term && !is_wp_error($term)) {
            $cache[$slug] = (int) $term->term_id;
        } else {
            $cache[$slug] = null;
        }

        return $cache[$slug];
    }

    private function detectableStrings(): void
    {
        if (!function_exists('remove_accents')) {
            require_once ABSPATH . 'wp-includes/formatting.php';
        }
    }

    private function normalizeText(string $text): string
    {
        $this->detectableStrings();
        return strtolower(remove_accents($text));
    }

    private function searchableText(string $text): string
    {
        $normalized = $this->normalizeText($text);
        $clean      = preg_replace('/[^a-z0-9]+/', ' ', $normalized);
        $clean      = trim(preg_replace('/\s+/', ' ', $clean ?? ''));
        return ' ' . $clean . ' ';
    }

    private function detectInmobiliariaOperation(string $searchText): ?string
    {
        if (strpos($searchText, ' traspas') !== false || strpos($searchText, 'traspaso') !== false) {
            return 'traspaso';
        }
        if (strpos($searchText, ' alquiler') !== false || strpos($searchText, ' alquil') !== false || strpos($searchText, ' arrendamient') !== false) {
            return 'alquiler';
        }
        if (strpos($searchText, ' venta ') !== false || strpos($searchText, ' se vende') !== false || strpos($searchText, ' vende ') !== false || strpos($searchText, ' en venta') !== false) {
            return 'venta';
        }

        return null;
    }

    private function log(string $message): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::log($message);
            return;
        }

        echo $message . PHP_EOL;
    }

    private function warn(string $message): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::warning($message);
            return;
        }

        fwrite(STDERR, $message . PHP_EOL);
    }
}

