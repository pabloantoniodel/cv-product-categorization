<?php

namespace FloatingButton\Publish;

defined( 'ABSPATH' ) || exit;

use FloatingButton\WOWP_Plugin;

class EnqueueScript {

	/**
	 * @var mixed
	 */
	private $id;
	private $param;

	public function __construct( $id, $param ) {
		$this->id    = $id;
		$this->param = maybe_unserialize( $param );
	}

	/**
	 * @throws \JsonException
	 */
	public function init(): void {

		$slug    = WOWP_Plugin::SLUG;
		$version = WOWP_Plugin::info( 'version' );
		$assets          = WOWP_Plugin::url() . 'public/assets/';
		$assets          = apply_filters( WOWP_Plugin::PREFIX . '_frontend_assets', $assets );

		$pre_suffix = ! WOWP_Plugin::DEVMODE ? '.min' : '';

		$url_script = $assets . 'js/script' . $pre_suffix . '.js';
		wp_enqueue_script( $slug, $url_script, array( 'jquery' ), $version, true );

		$inline_script = $this->inline();
		wp_add_inline_script( $slug, $inline_script, 'before' );

		do_action( WOWP_Plugin::PREFIX . '_enqueue_script' );

	}

	/**
	 * @throws \JsonException
	 */
	public function inline(): string {
		$param = $this->param;

		$arg = [
			'element' => 'floatBtn-' . absint( $this->id ),
		];

		if ( ! empty( $param['showAfterPosition'] ) ) {
			$arg['showAfterPosition'] = (int) $param['showAfterPosition'];
		}

		if ( ! empty( $param['hideAfterPosition'] ) ) {
			$arg['hideAfterPosition'] = (int) $param['hideAfterPosition'];
		}

		if ( ! empty( $param['showAfterTimer'] ) ) {
			$arg['showAfterTimer'] = (int) $param['showAfterTimer'];
		}

		if ( ! empty( $param['hideAfterTimer'] ) ) {
			$arg['hideAfterTimer'] = (int) $param['hideAfterTimer'];
		}

		if ( ! empty( $param['uncheckedBtn'] ) ) {
			$arg['uncheckedBtn'] = true;
		}

		if ( ! empty( $param['uncheckedSubBtn'] ) ) {
			$arg['uncheckedSubBtn'] = true;
		}

		if ( ! empty( $param['hideBtns'] ) ) {
			$arg['hideBtns'] = true;
		}

		if ( ! empty( $param['touch'] ) ) {
			$arg['touch'] = true;
		}

		if ( is_singular() ) {
			$arg['pageId'] = get_the_ID();
		}

		return 'var FloatingButton_' . absint( $this->id ) . ' = ' . json_encode( $arg, JSON_THROW_ON_ERROR );

	}


}