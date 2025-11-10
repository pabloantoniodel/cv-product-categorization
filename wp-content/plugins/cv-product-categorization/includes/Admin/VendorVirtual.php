<?php
declare(strict_types=1);

namespace Cv\ProductCategorization\Admin;

final class VendorVirtual
{
    public const META_KEY = 'cv_vendor_virtual_agent';

    public static function init(): void
    {
        add_filter('wcfm_marketplace_settings_fields_general', [self::class, 'inject_settings_field'], 110, 2);
        add_action('wcfm_vendor_settings_update', [self::class, 'handle_settings_save'], 15, 2);
    }

    /**
     * @param array<string,mixed> $fields
     *
     * @return array<string,mixed>
     */
    public static function inject_settings_field(array $fields, int $vendorId): array
    {
        $isVirtual = self::is_virtual($vendorId);

        $fields['cv_virtual_vendor'] = [
            'label'       => __('Modalidad comercial', 'cv-product-categorization'),
            'type'        => 'checkbox',
            'name'        => 'cv_virtual_vendor',
            'value'       => 'yes',
            'dfvalue'     => $isVirtual ? 'yes' : '',
            'class'       => 'wcfm-checkbox wcfm_ele wcfm_half_ele',
            'label_class' => 'wcfm_title wcfm_half_title checkbox_title',
            'hints'       => '',
            'desc'        => __('Marca esta opción si actúas como agente comercial sin tienda física.', 'cv-product-categorization'),
        ];

        return $fields;
    }

    /**
     * @param array<string,mixed> $form
     */
    public static function handle_settings_save(int $vendorId, array $form): void
    {
        $isVirtual = isset($form['cv_virtual_vendor']) && $form['cv_virtual_vendor'] === 'yes';

        if ($isVirtual) {
            update_user_meta($vendorId, self::META_KEY, 'yes');
        } else {
            delete_user_meta($vendorId, self::META_KEY);
        }
    }

    public static function is_virtual(int $vendorId): bool
    {
        return (bool) get_user_meta($vendorId, self::META_KEY, true);
    }
}


