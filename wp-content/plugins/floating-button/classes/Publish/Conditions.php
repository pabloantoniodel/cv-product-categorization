<?php

/**
 * Class Conditions
 *
 * Provides methods to check conditions for displaying item
 *
 * @package    WowPlugin
 * @subpackage Publish
 * @author     Dmytro Lobov <dev@wow-company.com>, Wow-Company
 * @copyright  2024 Dmytro Lobov
 * @license    GPL-2.0+
 */

namespace FloatingButton\Publish;

use FloatingButton\WOWP_Plugin;
use FloatingButton\WOWP_Public;

defined( 'ABSPATH' ) || exit;

class Conditions {

	public static function init( $result ): bool {
		$param = ! empty( $result->param ) ? maybe_unserialize( $result->param ) : [];

		$check = [
			'status'         => self::status( $result->status ),
			'mode'           => self::mode( $result->mode ),
		];

		$check = apply_filters( WOWP_Plugin::PREFIX . '_conditions', $check, $param );

		if ( in_array( false, $check, true ) ) {
			return false;
		}

		return true;

	}

	private static function status( $status ): bool {
		return empty( $status );
	}

	private static function mode( $mode ): bool {
		return empty( $mode ) || current_user_can( 'manage_options' );
	}

}
