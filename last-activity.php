<?php
/*
Plugin Name: Last Activity
Plugin URI : https://wordpress.org/plugins/last-activity
Description: Keep Tracks of each plugin's last active datetime, helpful to find obsolete plugins for deletion.
Version: 1.0.1
Author: Sajjad Hossain Sagor
Author URI: https://sajjadhsagor.com
Text Domain: last-activity
Domain Path: /languages
Requires PHP: 7.4
Requires at least: 5.7
Tested up to: 6.6

License: GPL2
This WordPress Plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

This free software is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this software. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! defined( 'PL_ACTIVITY_ROOT_DIR' ) )
{
	define( 'PL_ACTIVITY_ROOT_DIR', dirname( __FILE__ ) ); // Plugin root dir
}

if( ! defined( 'PL_ACTIVITY_ROOT_URL' ) )
{
	define( 'PL_ACTIVITY_ROOT_URL', plugin_dir_url( __FILE__ ) ); // Plugin root url
}

if( ! defined( 'PL_ACTIVITY_OPTION_NAME' ) )
{
	define( 'PL_ACTIVITY_OPTION_NAME', 'pl_activity_data' ); // Plugin option name
}

/**
 * Plugin Activation Hook
 */
register_activation_hook( __FILE__, 'pl_activity_plugin_activated' );

if ( ! function_exists( 'pl_activity_plugin_activated' ) )
{
	function pl_activity_plugin_activated()
	{
		$plugin_options = get_option( PL_ACTIVITY_OPTION_NAME, [] );
		
		$all_plugins = get_plugins();

		foreach ( $all_plugins as $key => $plugin )
		{
			if ( ! isset( $plugin_options[$key] ) )
			{
				$plugin_options[$key] = current_time( 'timestamp' );
			}
		}

		update_option( PL_ACTIVITY_OPTION_NAME, $plugin_options );
	}
}

/**
 * Plugin Deactivation Hook
 */
register_deactivation_hook( __FILE__, 'pl_activity_plugin_deactivated' );

if ( ! function_exists( 'pl_activity_plugin_deactivated' ) )
{
	function pl_activity_plugin_deactivated() {}
}

/**
 * Plugin Uninstalled / Deleted Hook
 */
register_uninstall_hook( __FILE__, 'pl_activity_plugin_uninstalled' );

if ( ! function_exists( 'pl_activity_plugin_uninstalled' ) )
{
	function pl_activity_plugin_uninstalled()
	{
		delete_option( PL_ACTIVITY_OPTION_NAME );
	}
}

if ( ! function_exists( 'pl_activity_set_plugin_activity_timestamp' ) )
{
	function pl_activity_set_plugin_activity_timestamp( $plugin )
	{
		$plugin_options = get_option( PL_ACTIVITY_OPTION_NAME, [] );
		
		$all_plugins = get_plugins();

		foreach ( $all_plugins as $key => $_plugin )
		{
			if ( $plugin == $key )
			{
				$plugin_options[$key] = current_time( 'timestamp' );

				break;
			}
		}

		update_option( PL_ACTIVITY_OPTION_NAME, $plugin_options );
	}
}

/**
 * Load the plugin after the main plugin is loaded.
 */
add_action( 'plugins_loaded', function()
{
	/**
	 * Load Text Domain
	 * This gets the plugin ready for translation
	 */
	load_plugin_textdomain( 'last-activity', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
} );

/**
 * Add a new column in the plugin list.
 */
add_filter( 'manage_plugins_columns', function( $columns )
{
	$columns['pla_last_active'] = __( 'Last Active', 'last-activity' );
	
	return $columns;
} );

/**
 * Show last active status value of the plugin.
 */
add_action( 'manage_plugins_custom_column', function( $column_name, $plugin_file, $plugin_data )
{
	if ( $column_name === 'pla_last_active' )
	{
		$dateTimeFormat = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		$plugin_options = get_option( PL_ACTIVITY_OPTION_NAME, [] );

		if ( is_plugin_active( $plugin_file ) )
		{	
			echo esc_html( wp_date( $dateTimeFormat, current_time( 'timestamp' ) ) ) . '<br>0 days, 0 hours, 0 minutes ago';
		}
		else
		{
			if ( isset( $plugin_options[$plugin_file] ) )
			{
				$startDate = new DateTime( wp_date( $dateTimeFormat, current_time( 'timestamp' ) ) );
				
				$endDate = new DateTime( wp_date( $dateTimeFormat, $plugin_options[$plugin_file] ) );

				$interval = $startDate->diff( $endDate );

				$days = $interval->days; $hours = $interval->h; $minutes = $interval->i;
				
				echo esc_html( wp_date( $dateTimeFormat, $plugin_options[$plugin_file] ) ) . '<br>' . $days . ' days, ' . $hours . ' hours ' . $minutes . ' minutes ago';
			}
			else
			{
				echo esc_html( wp_date( $dateTimeFormat, current_time( 'timestamp' ) ) ) . '<br>0 days, 0 hours, 0 minutes ago';
			}
		}
	}

}, 10, 3 );

/**
 * Adds inline style
 */
add_action( 'admin_print_styles', function()
{
	echo '<style type="text/css" media="screen">th#pla_last_active { min-width: 15em; }</style>';
} );

/**
 * Reset plugin's last active to that moment.
 */
add_action( 'activated_plugin', function( $plugin, $network_activation )
{
	pl_activity_set_plugin_activity_timestamp( $plugin );

}, 10, 2 );

/**
 * Set plugin's last active to current that moment.
 */
add_action( 'deactivated_plugin', function( $plugin, $network_activation )
{
	pl_activity_set_plugin_activity_timestamp( $plugin );

}, 10, 2 );
