<?php
/*
Plugin Name: Atception
Plugin URI: https://github.com/humanmade/alt-media
Description: Adds the ability to associate different attachments together, by default you can set a thumbnail for video and audio files.
Version: 1.0
Author: Robert O'Rourke
Author URI: http://hmn.md
License: GPLv2 or later
*/

// override this if you want to make this plugin a dependency in a theme or other plugin
defined( 'HM_ATCEPTION_URL' ) or define( 'HM_ATCEPTION_URL', plugins_url( '', __FILE__ ) );

// load the thing that does the work
require_once 'class-atception.php';

add_action( 'plugin_loaded', array( 'Atception', 'get_instance' ), 10 );

/**
 * Adds a UI to allow you to associate media of $alt_mime_type with $mime_type
 * Useful for providing featured or fallback images for audio or video files
 *
 * @param string $key           An identifier for the association used to query attachments
 * @param string $label         A human readable names for the field
 * @param string $mime_type     Mime type to select alternative media for eg. 'video', 'audio', 'image/gif'
 * @param string $alt_mime_type Mime type to be associated with the media eg. 'image'
 * @param bool   $multiple      Allow associating multiple files of this type
 */
function hm_register_alt_media( $key, $label, $mime_type, $alt_mime_type = 'image', $multiple = false ) {
	Atception::get_instance()->register_alt_media( $key, $label, $mime_type, $alt_mime_type, $multiple );
}

/**
 * Unregister an alt media mapping
 *
 * @param $key
 */
function hm_unregister_alt_media( $key ) {
	Atception::get_instance()->unregister_alt_media( $key );
}

/**
 * Fetches the associated media for the attachment by key
 * If $multiple is false then the post ID or object is returned, otherwise an
 * array of IDs or post objects is returned.
 * Returns false if no alt media is found.
 *
 * @param int    $attachment_id The attachment post ID
 * @param string $key           The key the association was registered with
 * @param string $fields        What fields to return - passed into get_posts(), can be 'ids', 'all'
 * @return WP_Post|int|array|false
 */
function hm_get_alt_media( $attachment_id, $key, $fields = 'ids' ) {
	return Atception::get_instance()->get_alt_media( $attachment_id, $key, $fields );
}

// default bindings
hm_register_alt_media( 'video_thumb', __( 'Video thumbnail' ), 'video', 'image' );
hm_register_alt_media( 'audio_thumb', __( 'Audio thumbnail' ), 'audio', 'image' );
