<?php

if (!defined('ABSPATH')) {
    exit;
}

class CV_WCFM_Script_Override
{
    public function __construct()
    {
        add_action('plugins_loaded', array($this, 'override_library_paths'), 50);
    }

    public function override_library_paths(): void
    {
        if (!isset($GLOBALS['WCFMmp']) || !is_object($GLOBALS['WCFMmp'])) {
            return;
        }

        $wcfmmp = $GLOBALS['WCFMmp'];
        if (!isset($wcfmmp->library) || !is_object($wcfmmp->library)) {
            return;
        }

        if (property_exists($wcfmmp->library, 'js_lib_url') && property_exists($wcfmmp->library, 'js_lib_url_min')) {
            $wcfmmp->library->js_lib_url_min = $wcfmmp->library->js_lib_url;
        }
    }
}

