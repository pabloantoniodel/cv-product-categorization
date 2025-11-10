<?php
/**
 * Plugin Name: CV AWS Prefix Terms
 * Description: Añade prefijos derivados al índice de Advanced Woo Search para mejorar coincidencias parciales.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('aws_extracted_terms', static function ($terms, $source) {
    if (empty($terms) || !is_array($terms)) {
        return $terms;
    }

    $extra_terms   = [];
    $min_word_len  = 4; // Longitud mínima del término original para generar prefijos.
    $min_prefix    = 3;
    $max_prefix    = 6; // Evita generar demasiados prefijos por palabra.

    foreach ($terms as $term => $weight) {
        if (!is_string($term)) {
            continue;
        }

        $clean_term = trim($term);
        if ($clean_term === '') {
            continue;
        }

        // Ignorar términos con caracteres no alfanuméricos relevantes o puramente numéricos.
        if (ctype_digit($clean_term) || preg_match('/[^a-z0-9]/', $clean_term)) {
            continue;
        }

        $length = mb_strlen($clean_term, 'UTF-8');
        if ($length < $min_word_len) {
            continue;
        }

        $base_weight = max(1, (int) $weight);

        for ($prefix_len = $min_prefix; $prefix_len <= min($length - 1, $max_prefix); $prefix_len++) {
            $prefix = mb_substr($clean_term, 0, $prefix_len, 'UTF-8');

            if ($prefix === '' || isset($terms[$prefix])) {
                continue;
            }

            if (!isset($extra_terms[$prefix]) || $extra_terms[$prefix] < $base_weight) {
                $extra_terms[$prefix] = $base_weight;
            }
        }
    }

    if (!empty($extra_terms)) {
        $terms = array_merge($terms, $extra_terms);
    }

    return $terms;
}, 20, 2);

