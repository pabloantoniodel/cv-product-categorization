<?php

namespace FloatingButton\Publish;

defined( 'ABSPATH' ) || exit;

use FloatingButton\WOWP_Plugin;

class EnqueueStyle {

	/**
	 * @var mixed
	 */
	private $id;
	private $param;

	public function __construct( $id, $param ) {
		$this->id    = $id;
		$this->param = maybe_unserialize( $param );
	}

	public function init(): void {
		$param   = $this->param;
		$slug    = WOWP_Plugin::SLUG;
		$version = WOWP_Plugin::info( 'version' );
		$assets          = WOWP_Plugin::url() . 'public/assets/';
		$assets          = apply_filters( WOWP_Plugin::PREFIX . '_frontend_assets', $assets );

		$pre_suffix = ! WOWP_Plugin::DEVMODE ? '.min' : '';

		$url_style = $assets . 'css/style' . $pre_suffix . '.css';
		wp_enqueue_style( $slug, $url_style, null, $version );

		$inline_style = $this->inline();
		wp_add_inline_style( $slug, $inline_style );

		if ( empty( $param['fontawesome'] ) ) {
			$url_icons = WOWP_Plugin::url() . 'vendors/fontawesome/css/all' . $pre_suffix . '.css';
			wp_enqueue_style( $slug . '-fontawesome', $url_icons, null, '7.1' );
		}

		if ( ! empty( $param['button_animation'] ) ) {
			$url_animation = $assets . 'css/animation' . $pre_suffix . '.css';
			wp_enqueue_style( $slug . '-animation', $url_animation, null, $version );
		}

	}

	public function inline(): string {
		$css = $this->main_btn();
		$css .= $this->size();
		$css .= $this->tooltip_size();
		$css .= $this->main_btn_anim();
		$css .= $this->offset();
		$css .= $this->items();
		$css .= $this->small_screen();
		$css .= $this->large_screen();
		$css .= $this->extra_style();

		return trim( preg_replace( '~\s+~s', ' ', $css ) );
	}


	public function items(): string {
		$param = $this->param;
		$css   = '';
		$menus = [ 'menu_1' => 'flBtn-first', 'menu_2' => 'flBtn-second' ];
		foreach ( $menus as $key => $class ) {
			$count = isset( $param[ $key ]['item_type'] ) ? count( $param[ $key ]['item_type'] ) : 0;
			for ( $i = 0; $i < $count; $i ++ ) {
				$item = $i + 1;
				$css  .= '#floatBtn-' . absint( $this->id ) . ' .' . esc_attr( $class ) . ' li:nth-child(' . absint( $item ) . ') {';
				$css  .= '--flbtn-color: ' . esc_attr( $param[ $key ]['icon_color'][ $i ] ) . ';';
				$css  .= '--flbtn-h-color: ' . esc_attr( $param[ $key ]['icon_hcolor'][ $i ] ) . ';';
				$css  .= '--flbtn-bg: ' . esc_attr( $param[ $key ]['button_color'][ $i ] ) . ';';
				$css  .= '--flbtn-h-bg: ' . esc_attr( $param[ $key ]['button_hcolor'][ $i ] ) . ';';
				$css  .= '}';
			}
		}

		return $css;
	}

	public function main_btn(): string {
		$param = $this->param;

		return ' 
		#floatBtn-' . absint( $this->id ) . ' > a,
		#floatBtn-' . absint( $this->id ) . ' > .flBtn-label {
			--flbtn-bg: ' . esc_attr( $param['button_color'] ) . ';
			--flbtn-color: ' . esc_attr( $param['icon_color'] ) . ';
			--flbtn-h-color: ' . esc_attr( $param['icon_hcolor'] ) . ';
			--flbtn-h-bg: ' . esc_attr( $param['button_hcolor'] ) . ';
		}
		#floatBtn-' . absint( $this->id ) . ' [data-tooltip] {
			--flbtn-tooltip-bg: ' . esc_attr( $param['tooltip_background'] ) . ';
			--flbtn-tooltip-color: ' . esc_attr( $param['tooltip_color'] ) . ';
		}';
	}

	public function main_btn_anim(): string {
		$param = $this->param;
		$css   = '';
		if ( ! empty(  $param['btn_anim_count'] ) ) {
			$css .= '
				#floatBtn-' . absint( $this->id ) . '.flBtn-animated {
					animation-iteration-count: ' . absint( $param['btn_anim_count'] ) . ';
				}
			';
		}

		if ( ! empty(  $param['btn_anim_delay'] ) ) {
			$css .= '
				#floatBtn-' . absint( $this->id ) . '.flBtn-animated {
					animation-delay: ' . absint( $param['btn_anim_delay'] ) . 'ms;
				}
			';
		}

		return $css;

	}

	public function size(): string {
		$param = $this->param;

		if ( $param['size'] !== 'flBtn-custom' ) {
			return '';
		}

		$css = '
		#floatBtn-' . absint( $this->id ) . ' {
			--flbtn-size: ' . absint( $param['ul_size'] ) . 'px;
			--flbtn-box: ' . absint( $param['ul_box'] ) . 'px;
			--flbtn-label-size: ' . absint( $param['label_size'] ) . 'px;
			--flbtn-label-box: ' . absint( $param['label_box'] ) . 'px;
		}';


		return $css;
	}

	public function tooltip_size(): string {
		$param = $this->param;
		$css = '';
		if ( ! empty( $param['tooltip_size_check'] ) && $param['tooltip_size_check'] === 'custom' ) {
			$css = '#floatBtn-' . absint( $this->id ) . ' {
				--flbtn-tooltip-size: ' . absint( $param['tooltip_size'] ) . 'px;
				--flbtn-tooltip-ul-size: ' . absint( $param['tooltip_ul_size'] ) . 'px;
			}';
		}
		return $css;
	}

	public function offset(): string {
		$param    = $this->param;
		$v_offset = ! empty( $param['v_offset'] ) ? $param['v_offset'] . 'px' : '0';
		$h_offset = ! empty( $param['h_offset'] ) ? $param['h_offset'] . 'px' : '0';
		$css      = '';
		if ( ! empty( $v_offset ) || ! empty( $h_offset ) ) {
			$css .= '#floatBtn-' . absint( $this->id ) . ' {
			--flbtn-v-offset: ' . esc_attr( $v_offset ) . ';
            --flbtn-h-offset: ' . esc_attr( $h_offset ) . ';
		}';

		}

		return $css;
	}

	public function small_screen(): string {
		if ( empty( $this->param['include_mobile'] ) ) {
			return '';
		}
		$screen = ! empty( $this->param['screen'] ) ? $this->param['screen'] : 480;

		return '
			@media only screen and (max-width: ' . esc_attr( $screen ) . 'px){
				#floatBtn-' . absint( $this->id ) . ' {
					display:none;
				}
			}';
	}

	public function large_screen(): string {
		if ( empty( $this->param['include_more_screen'] ) ) {
			return '';
		}
		$screen = ! empty( $this->param['screen_more'] ) ? $this->param['screen_more'] : 1200;

		return '
			@media only screen and (min-width: ' . esc_attr( $screen ) . 'px){
				#floatBtn-' . absint( $this->id ) . ' {
					display:none;
				}
			}';
	}

	public function extra_style() {
		if ( empty( $this->param['extra_style'] ) ) {
			return '';
		}

		return $this->param['extra_style'];

	}

}