<?php
/**
 * Plugin Name: JSM's Show Registered Shortcodes
 * Text Domain: jsm-show-registered-shortcodes
 * Domain Path: /languages
 * Plugin URI: https://surniaulula.com/extend/plugins/jsm-show-registered-shortcodes/
 * Assets URI: https://jsmoriss.github.io/jsm-show-registered-shortcodes/assets/
 * Author: JS Morisset
 * Author URI: https://surniaulula.com/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Description: Simple and lightweight plugin to show all registered shortcodes under a "Registered Shortcodes" toolbar menu item.
 * Requires PHP: 5.5
 * Requires At Least: 4.0
 * Tested Up To: 5.3.2
 * Version: 1.0.0
 *
 * Version Numbering: {major}.{minor}.{bugfix}[-{stage}.{level}]
 *
 *      {major}         Major structural code changes / re-writes or incompatible API changes.
 *      {minor}         New functionality was added or improved in a backwards-compatible manner.
 *      {bugfix}        Backwards-compatible bug fixes or small improvements.
 *      {stage}.{level} Pre-production release: dev < a (alpha) < b (beta) < rc (release candidate).
 *
 * Copyright 2016-2019 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'JSMShowRegisteredShortcodes' ) ) {

	class JSMShowRegisteredShortcodes {

		private static $instance;
		private static $wp_min_version = '4.0';

		private function __construct() {

			add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
			add_action( 'admin_bar_init', array( $this, 'add_admin_bar_css' ) );
			add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 5000 );

			if ( is_admin() ) {
				add_action( 'admin_init', array( __CLASS__, 'check_wp_version' ) );
			}
		}
	
		public static function &get_instance() {

			if ( ! isset( self::$instance ) ) {
				self::$instance = new self;
			}

			return self::$instance;
		}
	
		public static function load_textdomain() {
			load_plugin_textdomain( 'jsm-show-registered-shortcodes', false, 'jsm-show-registered-shortcodes/languages/' );
		}

		public static function check_wp_version() {

			global $wp_version;

			if ( version_compare( $wp_version, self::$wp_min_version, '<' ) ) {

				$plugin = plugin_basename( __FILE__ );

				if ( is_plugin_active( $plugin ) ) {

					if ( ! function_exists( 'deactivate_plugins' ) ) {
						require_once trailingslashit( ABSPATH ) . 'wp-admin/includes/plugin.php';
					}

					$plugin_data = get_plugin_data( __FILE__, $markup = false );

					deactivate_plugins( $plugin, $silent = true );

					wp_die( 
						'<p>' . sprintf( __( '%1$s requires %2$s version %3$s or higher and has been deactivated.',
							'jsm-show-registered-shortcodes' ), $plugin_data['Name'], 'WordPress', self::$wp_min_version ) . '</p>' . 
						'<p>' . sprintf( __( 'Please upgrade %1$s before trying to re-activate the %2$s plugin.',
							'jsm-show-registered-shortcodes' ), 'WordPress', $plugin_data['Name'] ) . '</p>'
					);
				}
			}
		}

		public function add_admin_bar_css() {
			$custom_style_css = '
				#wp-admin-bar-jsm-show-registered-shortcodes ul {
					max-height:90vh;	/* css3 90% of viewport height */
					overflow-y:scroll;
				}
				#wp-admin-bar-jsm-show-registered-shortcodes span.shortcode-name {
					font-weight:bold;
				}
				#wp-admin-bar-jsm-show-registered-shortcodes span.function-name {
					font-weight:normal;
					font-style:italic;
				}
			';
			wp_add_inline_style( 'admin-bar', $custom_style_css );
		}

		public function add_admin_bar_menu( $wp_admin_bar ) {

			global $shortcode_tags;

			$parent_slug = 'jsm-show-registered-shortcodes';

			// translators: %d is the total shortcode count 
			$parent_title = sprintf( __( 'Registered Shortcodes (%d)', 'jsm-show-registered-shortcodes' ), count( $shortcode_tags ) );

			/**
			 * Add the parent item.
			 */
			$args = array(
				'id' => $parent_slug,
				'title' => $parent_title,
			);

			$wp_admin_bar->add_node( $args );

			$sorted_items = array();

			foreach ( $shortcode_tags as $code => $callback ) {

				$item_name = $this->get_callback_name( $callback );
				$item_slug = sanitize_title( $code . '-' . $item_name );
				$item_title = '<span class="shortcode-name">[' . $code . ']</span> ' .
					'<span class="function-name">' . $item_name . '</span>';

				$sorted_items[ $item_slug ] = array(
					'id'     => $item_slug,
					'title'  => $item_title,
					'parent' => $parent_slug,
				);
			}

			ksort( $sorted_items );

			// add submenu items
			foreach ( $sorted_items as $item_slug => $args ) {
				$wp_admin_bar->add_node( $args );
			}
		}

		private function get_callback_name( $callback ) {
			if ( is_string( $callback ) ) {
				return $callback;
			} elseif ( is_array( $callback ) ) {
				if ( is_string( $callback[0] ) ) {	// static method
					return $callback[0] . ':: ' . $callback[1];
				} elseif ( is_object( $callback[0] ) ) {
					return get_class( $callback[0] ) . '->' . $callback[1];
				}
			}
			return '';	// Just in case.
		}
	}

	JSMShowRegisteredShortcodes::get_instance();
}
