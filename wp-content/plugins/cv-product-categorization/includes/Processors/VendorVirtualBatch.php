<?php
declare(strict_types=1);

namespace Cv\ProductCategorization\Processors;

use Cv\ProductCategorization\Admin\VendorVirtual;
use WP_CLI;
use WP_Error;
use WP_User;

final class VendorVirtualBatch
{
    /** @var string[] */
    private array $roles;

    private string $sinceDate;

    private int $termId;

    /**
     * @param array<string,mixed> $options
     */
    public function run(array $options = []): void
    {
        $this->roles     = $this->resolveRoles($options['roles'] ?? null);
        $this->sinceDate = $this->resolveSinceDate($options);
        $this->termId    = $this->resolveSectorTermId();

        $vendors = $this->getTargetVendors();
        $total   = count($vendors);

        if ($total === 0) {
            $this->log(sprintf('✅ No se encontraron vendedores nuevos desde %s.', $this->sinceDate));
            return;
        }

        $this->log(sprintf('🔄 Procesando %d vendedor(es) registrados desde %s...', $total, $this->sinceDate));

        $updated = 0;

        foreach ($vendors as $vendor) {
            if (!$vendor instanceof WP_User) {
                continue;
            }

            $this->markVendorAsVirtual((int) $vendor->ID);
            $this->assignSector((int) $vendor->ID);

            $this->log(sprintf(
                '  • %s (ID %d, registrado %s)',
                $vendor->user_login,
                $vendor->ID,
                $vendor->user_registered
            ));

            $updated++;
        }

        $this->log(sprintf('✅ Marcados %d vendedor(es) como agentes comerciales sin tienda.', $updated));
    }

    /**
     * @param mixed $raw
     *
     * @return string[]
     */
    private function resolveRoles($raw): array
    {
        if (is_string($raw) && $raw !== '') {
            return array_filter(array_map('trim', explode(',', $raw)));
        }

        if (is_array($raw) && !empty($raw)) {
            return array_values(array_filter(array_map('strval', $raw)));
        }

        return ['wcfm_vendor', 'vendor', 'seller', 'shop_vendor'];
    }

    /**
     * @param array<string,mixed> $options
     */
    private function resolveSinceDate(array $options): string
    {
        if (!empty($options['since']) && is_string($options['since'])) {
            $timestamp = strtotime($options['since']);
            if ($timestamp !== false) {
                return gmdate('Y-m-d', $timestamp);
            }
        }

        $days = isset($options['days']) ? (int) $options['days'] : 30;
        if ($days <= 0) {
            $days = 30;
        }

        return gmdate('Y-m-d', strtotime(sprintf('-%d days', $days)));
    }

    private function resolveSectorTermId(): int
    {
        $term = get_term_by('slug', 'agente-comercial', 'product_cat');
        if (!$term || $term instanceof WP_Error) {
            throw new \RuntimeException('No se encontró la categoría de sector "Agente Comercial" (slug agente-comercial).');
        }

        return (int) $term->term_id;
    }

    /**
     * @return array<int,WP_User>
     */
    private function getTargetVendors(): array
    {
        $query = new \WP_User_Query([
            'role__in'    => $this->roles,
            'fields'      => 'all_with_meta',
            'number'      => -1,
            'orderby'     => 'user_registered',
            'order'       => 'ASC',
            'date_query'  => [
                [
                    'column'    => 'user_registered',
                    'after'     => $this->sinceDate,
                    'inclusive' => true,
                ],
            ],
        ]);

        $results = $query->get_results();
        if (!is_array($results)) {
            return [];
        }

        return $results;
    }

    private function markVendorAsVirtual(int $vendorId): void
    {
        update_user_meta($vendorId, VendorVirtual::META_KEY, 'yes');
    }

    private function assignSector(int $vendorId): void
    {
        $current = get_user_meta($vendorId, 'cv_vendor_sector_terms', true);
        if (!is_array($current)) {
            $current = [];
        }

        $current = array_map('intval', $current);

        if (!in_array($this->termId, $current, true)) {
            $current[] = $this->termId;
        }

        update_user_meta($vendorId, 'cv_vendor_sector_terms', array_values(array_unique(array_filter($current))));
    }

    private function log(string $message): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::log($message);
            return;
        }

        error_log('[VendorVirtualBatch] ' . $message);
    }
}


