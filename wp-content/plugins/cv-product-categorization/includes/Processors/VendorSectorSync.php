<?php
declare(strict_types=1);

namespace Cv\ProductCategorization\Processors;

use Cv\ProductCategorization\Admin\VendorVirtual;

use WP_CLI;
use WP_Error;
use WP_Term;

final class VendorSectorSync
{
    private const META_KEY = 'cv_vendor_sector_terms';

    /** @var int|null */
    private $sectorRootId;

    /** @var array<int,bool> */
    private array $allowedSectorIds = [];

    /** @var array<string,int> */
    private array $sectorSlugMap = [];

    public function run(array $options = []): void
    {
        $this->bootstrapSectorData();
        if (!$this->sectorRootId || empty($this->allowedSectorIds)) {
            $this->log('⚠️ No se encontró la categoría "Sector" ni sus subcategorías. Abortando.');
            return;
        }

        $roles = $options['roles'] ?? ['wcfm_vendor', 'vendor', 'seller', 'shop_vendor'];
        $roles = is_array($roles) ? $roles : [$roles];

        $vendors = get_users([
            'role__in' => $roles,
            'fields'   => ['ID', 'user_login'],
        ]);

        if (empty($vendors)) {
            $this->log('⚠️ No se encontraron vendedores con roles válidos.');
            return;
        }

        $processed = 0;
        $assigned  = 0;
        $cleared   = 0;

        foreach ($vendors as $vendor) {
            $processed++;
            $sectorId = $this->resolveVendorSector((int) $vendor->ID);
            if ($sectorId) {
                update_user_meta((int) $vendor->ID, self::META_KEY, [$sectorId]);
                $assigned++;
                $this->log(sprintf('✅ %s => %s', $vendor->user_login, $this->termName($sectorId)));
            } else {
                delete_user_meta((int) $vendor->ID, self::META_KEY);
                $cleared++;
                $this->log(sprintf('➖ %s sin sector asignable', $vendor->user_login));
            }
        }

        $this->log('');
        $this->log('Resumen sincronización:');
        $this->log(sprintf('Vendedores procesados: %d', $processed));
        $this->log(sprintf('Sectores asignados: %d', $assigned));
        $this->log(sprintf('Sectores eliminados: %d', $cleared));
    }

    private function bootstrapSectorData(): void
    {
        $sector = get_term_by('slug', 'sector', 'product_cat');
        if (!$sector instanceof WP_Term) {
            return;
        }
        $this->sectorRootId = (int) $sector->term_id;

        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'child_of'   => $this->sectorRootId,
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            return;
        }

        foreach ($terms as $term) {
            if ($term instanceof WP_Term) {
                $termId = (int) $term->term_id;
                $this->allowedSectorIds[$termId]          = true;
                $this->sectorSlugMap[$term->slug] = $termId;
            }
        }
    }

    private function resolveVendorSector(int $vendorId): int
    {
        if (VendorVirtual::is_virtual($vendorId)) {
            $virtualId = $this->sectorIdFromSlug('agente-comercial');
            if ($virtualId) {
                return $virtualId;
            }
        }

        $products = get_posts([
            'post_type'      => 'product',
            'post_status'    => ['publish', 'pending', 'draft', 'future', 'private'],
            'fields'         => 'ids',
            'author'         => $vendorId,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'ASC',
        ]);

        if (empty($products)) {
            return 0;
        }

        foreach ($products as $productId) {
            $sectorId = $this->firstSectorFromProduct((int) $productId);
            if ($sectorId) {
                return $sectorId;
            }
        }

        return 0;
    }

    private function sectorIdFromSlug(string $slug): int
    {
        if (isset($this->sectorSlugMap[$slug])) {
            return $this->sectorSlugMap[$slug];
        }

        $term = get_term_by('slug', $slug, 'product_cat');
        if ($term instanceof WP_Term) {
            $termId = (int) $term->term_id;
            $this->sectorSlugMap[$slug]    = $termId;
            $this->allowedSectorIds[$termId] = true;
            return $termId;
        }

        return 0;
    }

    private function firstSectorFromProduct(int $productId): int
    {
        $terms = wp_get_post_terms($productId, 'product_cat');
        if (empty($terms) || $terms instanceof WP_Error) {
            return 0;
        }

        foreach ($terms as $term) {
            if (!$term instanceof WP_Term) {
                continue;
            }
            $termId = (int) $term->term_id;
            if (isset($this->allowedSectorIds[$termId])) {
                return $termId;
            }

            $ancestors = get_ancestors($termId, 'product_cat');
            foreach ($ancestors as $ancestorId) {
                if (isset($this->allowedSectorIds[(int) $ancestorId])) {
                    return (int) $ancestorId;
                }
            }
        }

        return 0;
    }

    private function termName(int $termId): string
    {
        $term = get_term($termId, 'product_cat');
        if ($term instanceof WP_Term) {
            return $term->name;
        }
        return (string) $termId;
    }

    private function log(string $message): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::log($message);
            return;
        }
        error_log('[VendorSectorSync] ' . $message);
    }
}
