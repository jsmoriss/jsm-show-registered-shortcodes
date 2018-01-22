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
 * Description: Show all registered shortcodes under a "Registered Shortcodes" toolbar menu item.
 * Requires PHP: 5.4
 * Requires At Least: 3.8
 * Tested Up To: 4.9.2
 * Version: 1.0.0
 *
 * Version Numbering: {major}.{minor}.{bugfix}[-{stage}.{level}]
 *
 *      {major}         Major structural code changes / re-writes or incompatible API changes.
 *      {minor}         New functionality was added or improved in a backwards-compatible manner.
 *      {bugfix}        Backwards-compatible bug fixes or small improvements.
 *      {stage}.{level} Pre-production release: dev < a (alpha) < b (beta) < rc (release candidate).
 *
 * Copyright 2016-2018 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'JSMShowRegisteredShortcodes' ) ) {

	class JSMShowRegisteredShortcodes {

		private static $instance;

		private $pagehook;
		private $cols = 2;

		private function __construct() {

			add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );

			if ( is_admin() ) {
				add_action( 'admin_init', array( __CLASS__, 'check_wp_version' ) );
			}

			add_action( 'admin_bar_menu', array( &$this, 'add_toolbar_menu' ), 5000 );
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
			$wp_min_version = 3.8;

			if ( version_compare( $wp_version, $wp_min_version, '<' ) ) {
				$plugin = plugin_basename( __FILE__ );
				if ( is_plugin_active( $plugin ) ) {
					if ( ! function_exists( 'deactivate_plugins' ) ) {
						require_once trailingslashit( ABSPATH ) . 'wp-admin/includes/plugin.php';
					}
					$plugin_data = get_plugin_data( __FILE__, false ); // $markup = false
					deactivate_plugins( $plugin, true ); // $silent = true
					wp_die( 
						'<p>' . sprintf( __( '%1$s requires %2$s version %3$s or higher and has been deactivated.',
							'jsm-show-registered-shortcodes' ), $plugin_data['Name'], 'WordPress', $wp_min_version ) . '</p>' . 
						'<p>' . sprintf( __( 'Please upgrade %1$s before trying to re-activate the %2$s plugin.',
							'jsm-show-registered-shortcodes' ), 'WordPress', $plugin_data['Name'] ) . '</p>'
					);
				}
			}
		}

		public function add_toolbar_menu( $wp_admin_bar ) {

			$parent_slug = 'jsm-show-registered-shortcodes';
			$parent_title = __( 'Registered Shortcodes', 'jsm-show-registered-shortcodes' );

			// add a parent item
			$args = array(
				'id' => $parent_slug,
				'title' => $parent_title,
			);

			$wp_admin_bar->add_node( $args );

			global $shortcode_tags;

			foreach( $shortcode_tags as $code => $callback ) {

				$item_name = $this->get_callback_name( $callback );
				$item_slug = sanitize_title( $code . $item_name );
				$item_title = '<span class="shortcode-name" style="font-weight:bold;">[' . $code . ']</span> ' .
					'<span class="function-name" style="font-weight:normal; font-style:italic;">' . $item_name . '</span>';

				// add a submenu item
				$args = array(
					'id' => $item_slug,
					'title' => $item_title,
					'parent' => $parent_slug,
				);

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
			return '';	// just in case
		}
	}

	JSMShowRegisteredShortcodes::get_instance();
}
