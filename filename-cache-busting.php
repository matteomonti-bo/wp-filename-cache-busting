<?php
/**
 * Plugin Name: Filename cache busting
 * Version: 0.1
 * Description: Filename based cache busting to improve performance
 * Author: Matteo Monti
 * Author URI: https://github.com/matteomonti-bo
 * License: GPL v2 or later
 */
defined( 'ABSPATH' ) or die( 'no script kiddies please' );

// Hooks list
add_filter( 'script_loader_src', 'fcb_filenameReplace' );
add_filter( 'style_loader_src', 'fcb_filenameReplace' );
register_activation_hook( __FILE__, 'fcb_activate');
register_deactivation_hook( __FILE__, 'fcb_deactivate');

function fcb_filenameReplace($src) {
	// Exclude admin scripts
	if (is_admin())
			return $src;
	// Exclude external scripts
	$host_regex = '/^http(s)?\:\/\/' . preg_quote($_SERVER['HTTP_HOST']) . '/';
	if (!preg_match($host_regex, $src)) {
			return $src;
	}

	return preg_replace(
	'/\.(js|css)\?ver=(.+)$/',
	'.$2.$1',
	$src
	);
}

function fcb_saveRewriteRules($rules) {
	if ( is_multisite() ) {
		return;
	}
	global $wp_rewrite;
	$home_path     = get_home_path();
	$htaccess_file = $home_path . '.htaccess';

	/*
	 * check if htaccess exists and is writable
	 */
	if ( ( ! file_exists( $htaccess_file ) && is_writable( $home_path ) ) || is_writable( $htaccess_file ) ) {
		if ( got_mod_rewrite() ) {
			return _fcb_insertWithMarkers( $htaccess_file, 'FILENAME CACHE BUSTING', $rules );
		}
	}

	return false;
}


/**
 * Inserts an array of strings into a file (.htaccess ), placing it between
 * BEGIN and END markers.
 *
 * Replaces existing marked info. Retains surrounding
 * data. Creates file if none exists.
 */
function _fcb_insertWithMarkers( $filename, $marker, $insertion ) {
	if ( ! file_exists( $filename ) ) {
		if ( ! is_writable( dirname( $filename ) ) ) {
			return false;
		}
		if ( ! touch( $filename ) ) {
			return false;
		}
	} elseif ( ! is_writeable( $filename ) ) {
		return false;
	}

	if ( ! is_array( $insertion )  && !empty($insertion) ) {
		$insertion = explode( "\n", $insertion );
	}

	$start_marker = "# BEGIN {$marker}";
	$end_marker   = "# END {$marker}";

	$fp = fopen( $filename, 'r+' );
	if ( ! $fp ) {
		return false;
	}

	// Attempt to get a lock. If the filesystem supports locking, this will block until the lock is acquired.
	flock( $fp, LOCK_EX );

	$lines = array();
	while ( ! feof( $fp ) ) {
		$lines[] = rtrim( fgets( $fp ), "\r\n" );
	}

	// Split out the existing file into the preceding lines, and those that appear after the marker
	$pre_lines    = $post_lines = $existing_lines = array();
	$found_marker = $found_end_marker = false;
	foreach ( $lines as $line ) {
		if ( ! $found_marker && false !== strpos( $line, $start_marker ) ) {
			$found_marker = true;
			continue;
		} elseif ( ! $found_end_marker && false !== strpos( $line, $end_marker ) ) {
			$found_end_marker = true;
			continue;
		}
		if ( ! $found_marker ) {
			$pre_lines[] = $line;
		} elseif ( $found_marker && $found_end_marker ) {
			$post_lines[] = $line;
		} else {
			$existing_lines[] = $line;
		}
	}

	// Check to see if there was a change
	if ( $existing_lines === $insertion ) {
		flock( $fp, LOCK_UN );
		fclose( $fp );

		return true;
	}


	if(empty($insertion)){
		$new_file_data = implode(
			"\n",
			array_merge(
				$pre_lines,
				$post_lines
			)
		);
	}else{
		$new_file_data = implode(
			"\n",
			array_merge(
				array( $start_marker ),
				$insertion,
				array( $end_marker ),
				$pre_lines,
				$post_lines
			)
		);
	}

	// Write to the start of the file, and truncate it to that length
	fseek( $fp, 0 );
	$bytes = fwrite( $fp, $new_file_data );
	if ( $bytes ) {
		ftruncate( $fp, ftell( $fp ) );
	}
	fflush( $fp );
	flock( $fp, LOCK_UN );
	fclose( $fp );

	return (bool) $bytes;
}


function fcb_activate(){
	$myRules = "<IfModule mod_rewrite.c>
			Options +FollowSymlinks
			RewriteEngine On
			RewriteCond %{REQUEST_FILENAME} !-f
			RewriteCond %{REQUEST_FILENAME} !-d
			RewriteRule ^(.+)\.(\d+)\.(js|css)$ $1.$3 [L]
	</IfModule>";
	fcb_saveRewriteRules($myRules);
}

function fcb_deactivate(){
	fcb_saveRewriteRules("");
}

