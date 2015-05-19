<?php
/**
 * @package   WDS Ratings
 * @author    WebDevStudios
 * @license   GPL-2.0+
 * @link      http://webdevstudios.com
 * @copyright 2015 WebDevStudios
 *
 * Plugin Name: WDS Ratings
 * Plugin URI:  http://www.webdevstudios.com
 * Description: Allow users to rate posts
 * Version:     0.4.0
 * Author:      WebDevStudios
 * Author URI:  http://webdevstudios.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wds_ratings
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// load the plugin
if ( ! class_exists( 'WDS_Ratings' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'wds-ratings-class.php' );

	// init our class
	wds_ratings()->hooks();

	// include helpers
	require_once( wds_ratings()->path . 'lib/helpers.php' );

	// include widget if enabled
	if ( 'on' === wds_ratings()->fetch_option( 'enable_widget' ) ) {
		require_once( wds_ratings()->path . 'lib/widget.php' );
	}

}
