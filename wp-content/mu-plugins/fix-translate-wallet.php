<?php
/**
 * Fix para función translate_wallet_html faltante
 * Plugin Name: Fix Translate Wallet
 * Description: Corrige el error fatal de translate_wallet_html
 */

// Crear la función faltante para evitar el fatal error
if (!function_exists('translate_wallet_html')) {
    function translate_wallet_html($html) {
        // Simplemente devolver el HTML sin modificar
        // La traducción se hace con JavaScript en translate-wallet.js
        return $html;
    }
}

