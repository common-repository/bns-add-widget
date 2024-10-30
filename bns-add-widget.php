<?php
/*
Plugin Name: BNS Add Widget
Plugin URI: http://buynowshop.com/plugins/bns-add-widget
Description: Add a widget area to the footer of any theme.
Version: 1.0
Author: Edward Caissie
Author URI: http://edwardcaissie.com/
Text Domain: bns-add-widget
License: GNU General Public License v2
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

/**
 * BNS Add Widget plugin
 *
 * Add a widget area to the footer of any theme. Works just like the widget
 * areas commonly created with code in the functions.php template file.
 *
 * @package        BNS_Add_Widget
 * @link           http://buynowshop.com/plugins/bns-add-widget/
 * @link           https://github.com/Cais/bns-add-widget/
 * @link           https://wordpress.org/plugins/bns-add-widget/
 * @version        1.0
 * @author         Edward Caissie <edward.caissie@gmail.com>
 * @copyright      Copyright (c) 2010-2016, Edward Caissie
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2, as published by the
 * Free Software Foundation.
 *
 * You may NOT assume that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to:
 *
 *      Free Software Foundation, Inc.
 *      51 Franklin St, Fifth Floor
 *      Boston, MA  02110-1301  USA
 *
 * The license for this software can also likely be found here:
 * http://www.gnu.org/licenses/gpl-2.0.html
 */
class BNS_Add_Widget {

	private static $instance = null;

	/**
	 * Create Instance
	 *
	 * Creates a single instance of the class
	 *
	 * @package BNS_Add_Widget
	 * @since   2.0
	 * @date    June 7, 2015
	 *
	 * @return null|BNS_Add_Widget
	 */
	public static function create_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	/**
	 * Constructor
	 * This is where the go-go juice is squeezed out of the code
	 *
	 * @package    BNS_Add_Widget
	 *
	 * @uses       WP_CONTENT_DIR
	 * @uses       add_action
	 * @uses       add_filter
	 * @uses       content_url
	 * @uses       load_plugin_textdomain
	 * @uses       plugin_basename
	 * @uses       register_activation_hook
	 */
	function __construct() {

		register_activation_hook( __FILE__, array( $this, 'install' ) );

		load_plugin_textdomain( 'bns-add-widget' );

		/** Define location for BNS plugin customizations */
		if ( ! defined( 'BNS_CUSTOM_PATH' ) ) {
			define( 'BNS_CUSTOM_PATH', WP_CONTENT_DIR . '/bns-customs/' );
		}
		if ( ! defined( 'BNS_CUSTOM_URL' ) ) {
			define( 'BNS_CUSTOM_URL', content_url( '/bns-customs/' ) );
		}

		/** Enqueue Scripts and Styles */
		add_action(
			'wp_enqueue_scripts', array(
				$this,
				'scripts_and_styles'
			)
		);

		/** Add Widget Definition */
		add_action( 'init', array( $this, 'BNS_Add_Widget_Definition' ) );

		/** Hook into footer */
		add_action( 'wp_footer', array( $this, 'BNS_Add_Widget_Hook' ) );

		/** Add Plugin Row Meta details */
		add_filter( 'plugin_row_meta', array( $this, 'plugin_meta' ), 10, 2 );

		/** Add plugin update message */
		add_action( 'in_plugin_update_message-' . plugin_basename( __FILE__ ), array( $this, 'update_message' ) );

	}


	/**
	 * Check installed WordPress version for compatibility
	 *
	 * @package     BNS_Add_Widget
	 * @since       1.0
	 * @date        February 21, 2016
	 *
	 * @uses        BNS_Add_Widget::plugin_data
	 * @uses        __
	 * @uses        apply_filters
	 * @uses        deactivate_plugins
	 * @uses        get_bloginfo
	 */
	function install() {

		/** @var float $version_required - see "Requires at least" from `readme.txt` */
		$version_required = apply_filters( 'bns_add_widget_requires_at_least_version', '2.7' );

		$plugin_data = $this->plugin_data();

		/** @var string $exit_message - build an explanation message */
		$exit_message = sprintf( __( '%1$s requires WordPress version %2$s or later.', 'bns-add-widget' ), $plugin_data['Name'], $version_required );
		$exit_message .= '<br />';
		$exit_message .= sprintf( '<a href="http://codex.wordpress.org/Upgrading_WordPress" target="_blank">%1$s</a>', __( 'Please Update!', 'bns-add-widget' ) );

		/** Conditional check of current WordPress version */
		if ( version_compare( get_bloginfo( 'version' ), floatval( $version_required ), '<' ) ) {

			deactivate_plugins( basename( __FILE__ ) );
			exit( $exit_message );

		}

	}


	/**
	 * Enqueue Plugin Scripts and Styles
	 * Adds plugin stylesheet and allows for custom stylesheet to be added by
	 * end-user.
	 *
	 * @package    BNS_Add_Widget
	 * @since      0.4
	 *
	 * @uses       BNS_Add_Widget::plugin_data
	 * @uses       BNS_CUSTOM_PATH
	 * @uses       BNS_CUSTOM_URL
	 * @uses       plugin_dir_url
	 * @uses       wp_enqueue_style
	 *
	 * @version    0.4.3
	 * @date       September 12, 2012
	 * Set versions to dynamically match the plugin version
	 *
	 * @version    1.0
	 * @date       February 21, 2016
	 * Updated location of custom CSS for plugin
	 */
	function scripts_and_styles() {

		$plugin_data = $this->plugin_data();

		/** Styles */
		wp_enqueue_style( 'BNSAW-Style', plugin_dir_url( __FILE__ ) . 'bnsaw-style.css', array(), $plugin_data['Version'], 'screen' );

		/** For custom stylesheets in the /wp-content/bns-custom/ folder */
		if ( is_readable( BNS_CUSTOM_PATH . 'bnsaw-custom-style.css' ) ) {
			wp_enqueue_style( 'BNSAW-Custom-Style', BNS_CUSTOM_URL . 'bnsaw-custom-style.css', array(), $plugin_data['Version'], 'screen' );
		}

	}


	/**
	 * BNS Add Widget
	 * The main section of code that sets the sidebar parameters to be used.
	 *
	 * @package BNS_Add_Widget
	 * @since   0.1
	 *
	 * @uses    register_sidebar
	 *
	 * @version 0.4
	 * @date    November 14, 2011
	 */
	function BNS_Add_Widget_Definition() {

		register_sidebar(
			array(
				'name'          => __( 'BNS Add Widget', 'bns-add-widget' ),
				'id'            => 'bns-add-widget',
				'description'   => __( 'This widget area will generally be found at the bottom of the page in the theme footer area.', 'bns-add-widget' ),
				'before_widget' => '<div class="bns-add-widget"><div id="%1$s" class="widget %2$s">',
				'after_widget'  => '</div><!-- #%1$s .widget .%2$s --></div><!-- .bns-add-widget -->',
				'before_title'  => '<h2 class="bns-add-widget-title">',
				'after_title'   => '</h2>',
			)
		);

	}


	/**
	 * BNS Add Widget Hook
	 * Provides default content for the `add_action` hook into `wp_footer`.
	 *
	 * @package  BNS_Add_Widget
	 * @since    0.1
	 *
	 * @uses     apply_filters
	 * @uses     dynamic_sidebar
	 * @internal REQUIRES `wp_footer` action hook to be available
	 *
	 * @version  0.6
	 * @date     November 26, 2012
	 * Added filter hook and CSS wrapper to text
	 *
	 * @version  0.6.1
	 * @date     February 13, 2013
	 * Fixed misread token issue
	 */
	function BNS_Add_Widget_Hook() { ?>

		<div class="bnsaw-credit">
			<?php
			if ( ! dynamic_sidebar( 'bns-add-widget' ) ) {
				echo apply_filters( 'bnsaw_credit_text', sprintf( '<span class="bnsaw-credit-text">%1$s</span>', sprintf( __( 'You are using the %1$s plugin. Thank You!', 'bns-add-widget' ), '<a href="http://buynowshop.com/plugins/bns-add-widget/">BNS Add Widget</a>' ) ) );
			}
			?>
		</div>

	<?php }


	/**
	 * BNS Add Widget Update Message
	 *
	 * @package BNS_Add_Widget
	 * @since   0.8
	 *
	 * @uses    get_transient
	 * @uses    is_wp_error
	 * @uses    set_transient
	 * @uses    wp_kses_post
	 * @uses    wp_remote_get
	 *
	 * @param $args
	 */
	function update_message( $args ) {

		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		$bnsaw_data = get_plugin_data( __FILE__ );

		$transient_name = 'bnsaw_upgrade_notice_' . $args['Version'];
		if ( false === ( $upgrade_notice = get_transient( $transient_name ) ) ) {

			/** @var string $response - get the readme.txt file from WordPress */
			$response = wp_remote_get( 'https://plugins.svn.wordpress.org/bns-add-widget/trunk/readme.txt' );

			if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) {
				$matches = null;
			}
			$regexp         = '~==\s*Changelog\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( $bnsaw_data['Version'] ) . '\s*=|$)~Uis';
			$upgrade_notice = '';

			if ( preg_match( $regexp, $response['body'], $matches ) ) {
				$version = trim( $matches[1] );
				$notices = (array) preg_split( '~[\r\n]+~', trim( $matches[2] ) );

				if ( version_compare( $bnsaw_data['Version'], $version, '<' ) ) {

					/** @var string $upgrade_notice - start building message (inline styles) */
					$upgrade_notice = '<style type="text/css">
							.bnsaw_plugin_upgrade_notice { padding-top: 20px; }
							.bnsaw_plugin_upgrade_notice ul { width: 50%; list-style: disc; margin-left: 20px; margin-top: 0; }
							.bnsaw_plugin_upgrade_notice li { margin: 0; }
						</style>';

					/** @var string $upgrade_notice - start building message (begin block) */
					$upgrade_notice .= '<div class="bnsaw_plugin_upgrade_notice">';

					$ul = false;

					foreach ( $notices as $index => $line ) {

						if ( preg_match( '~^=\s*(.*)\s*=$~i', $line ) ) {

							if ( $ul ) {
								$upgrade_notice .= '</ul><div style="clear: left;"></div>';
							}
							/** End if - unordered list created */

							$upgrade_notice .= '<hr/>';
							continue;

						}
						/** End if - non-blank line */

						/** @var string $return_value - body of message */
						$return_value = '';

						if ( preg_match( '~^\s*\*\s*~', $line ) ) {

							if ( ! $ul ) {
								$return_value = '<ul">';
								$ul           = true;
							}
							/** End if - unordered list not started */

							$line = preg_replace( '~^\s*\*\s*~', '', htmlspecialchars( $line ) );
							$return_value .= '<li style=" ' . ( $index % 2 == 0 ? 'clear: left;' : '' ) . '">' . $line . '</li>';

						} else {

							if ( $ul ) {
								$return_value = '</ul><div style="clear: left;"></div>';
								$return_value .= '<p>' . $line . '</p>';
								$ul = false;
							} else {
								$return_value .= '<p>' . $line . '</p>';
							}
							/** End if - unordered list started */

						}
						/** End if - non-blank line */

						$upgrade_notice .= wp_kses_post( preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $return_value ) );

					}
					/** End foreach - line parsing */

					$upgrade_notice .= '</div>';

				}
				/** End if - version compare */

			}
			/** End if - response message exists */

			/** Set transient - minimize calls to WordPress */
			set_transient( $transient_name, $upgrade_notice, DAY_IN_SECONDS );

		}
		/** End if - transient check */

		echo $upgrade_notice;

	}


	/**
	 * Plugin Data
	 *
	 * Returns the plugin header data as an array
	 *
	 * @package    BNS_Add_widget
	 * @since      1.0
	 * @date       February 21, 2016
	 *
	 * @uses       get_plugin_data
	 *
	 * @return array
	 */
	function plugin_data() {

		/** Call the wp-admin plugin code */
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		/** @var $plugin_data - holds the plugin header data */
		$plugin_data = get_plugin_data( __FILE__ );

		return $plugin_data;

	}


	/**
	 * Plugin Meta
	 *
	 * Adds additional links to plugin meta links
	 *
	 * @package    BNS_Add_Widget
	 * @since      1.0
	 * @date       February 21, 2016
	 *
	 * @uses       __
	 * @uses       plugin_basename
	 *
	 * @param   $links
	 * @param   $file
	 *
	 * @return  array $links
	 */
	function plugin_meta( $links, $file ) {

		$plugin_file = plugin_basename( __FILE__ );

		if ( $file == $plugin_file ) {

			$links = array_merge(
				$links, array(
					'fork_link'      => '<a href="https://github.com/Cais/BNS-Add-Widget">' . __( 'Fork on GitHub', 'bns-add-widget' ) . '</a>',
					'wish_link'      => '<a href="http://www.amazon.ca/registry/wishlist/2NNNE1PAQIRUL">' . __( 'Grant a wish?', 'bns-add-widget' ) . '</a>',
					'support_link'   => '<a href="http://wordpress.org/support/plugin/bns-add-widget">' . __( 'WordPress support forums', 'bns-add-widget' ) . '</a>',
					'translate_link' => '<a href="https://translate.wordpress.org/projects/wp-plugins/bns-add-widget">' . __( 'Add your translation', 'bns-add-widget' ) . '</a>'
				)
			);

		}

		return $links;

	}


}


/** @var $bns_add_widget - new instance of the class */
$bns_add_widget = new BNS_Add_Widget();