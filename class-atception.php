<?php

class Atception {

	/**
	 * Array of mime type mapping and whether they allow multiple additional attachments
	 *
	 * @var array
	 */
	public static $map = array();

	protected static $instance;

	public static function get_instance() {
		if ( ! self::$instance ) self::$instance = new self();
		return self::$instance;
	}

	public function __construct() {

		// add button to select attachment attachment
		add_filter( 'attachment_fields_to_edit', array( $this, 'edit_fields' ), 10, 2 );

		// queue the things
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// handle ajax
		add_action( 'wp_ajax_alt_media_get_thumb', array( $this, 'ajax_get_thumb' ) );
		add_action( 'wp_ajax_alt_media_delete', array( $this, 'ajax_delete' ) );

	}

	public function edit_fields( $form_fields, $attachment ) {

		$mime_type = get_post_mime_type( $attachment );

		foreach( self::$map as $key => $settings ) {

			// check attachment is the right mime type
			if ( strpos( $mime_type, $settings['mime_type'] ) === false ) {
				continue;
			}

			$attached_media = $this->get_alt_media( $attachment->ID, $key );

			$list = array();

			foreach( $attached_media as $media_id ) {
				$list[] = $this->get_alt_item( $media_id );
			}

			$data = $this->get_data_att_string( array(
				'attachment-id' => $attachment->ID,
				'mime-type'     => $settings['alt_mime_type'],
				'attachments'   => implode( ',', $attached_media ),
				'multiple'      => $settings['multiple'],
				'nonce'         => wp_create_nonce( 'alt_media' ),
				'key'           => $key
			) );

			$button = sprintf( '
				<div class="alt-media-container" %s>
					<div class="alt-media-placeholder">%s</div>
				</div>',
				$data,
				implode( '', $list ) );

			// just add a media popup button
			$form_fields[ "alt_media_" . sanitize_key( $key ) ] = array(
				'label' => sprintf( '<a class="button alt-media-add">%s %s</a>', __( 'Select' ), $settings['label'] ),
				'input' => 'html',
				'html' => $button
			);

		}

		return $form_fields;
	}

	public function get_alt_media( $attachment_id, $key, $fields = 'ids' ) {
		$attachments = get_post_meta( $attachment_id, sanitize_key( "alt_media_{$key}" ) );

		if ( ! $attachments ) {
			return false;
		}

		$args = apply_filters( 'get_alt_media_args', array(
			'post_type' => 'attachment',
			'post__in'  => $attachments,
			'fields'    => $fields
		), $attachment_id, $key, $fields );

		$alt_media = get_posts( $args );

		// return the single value for non multiple
		if ( is_array( $alt_media ) && ! self::$map[ $key ][ 'multiple' ] ) {
			return array_shift( $alt_media );
		}

		return $alt_media;
	}

	private function is_image( $attachment_id ) {
		return strpos( get_post_mime_type( $attachment_id ), 'image/' ) === 0;
	}

	private function get_data_att_string( $data_atts ) {
		$output = '';

		foreach( $data_atts as $name => $value ) {
			$output .= " data-{$name}=\"" . esc_attr( $value ) . '"';
		}

		return $output;
	}

	private function get_alt_item( $attachment_id ) {
		return '
			<div class="alt-media-item" data-id="' . $attachment_id . '">
				<a class="alt-media-delete" href="#">' . __( 'Delete' ) . '</a>
				' . wp_get_attachment_image( $attachment_id, 'thumbnail', ! $this->is_image( $attachment_id ) ) . '
				' . get_the_title( $attachment_id ) . '
				<input type="hidden" name="alt_media[]" value="' . esc_attr( $attachment_id ) . '" />
			</div>';
	}

	public function admin_enqueue_scripts() {

		wp_enqueue_script( 'alt-media', HM_ATCEPTION_URL . '/js/alt-media.js', array( 'jquery' ) );

		wp_enqueue_style( 'alt-media', HM_ATCEPTION_URL . '/css/alt-media.css' );

	}

	public function register_alt_media( $key, $label, $mime_type, $alt_mime_type, $multiple = false ) {

		if ( ! isset( self::$map[ $key ] ) ) {
			self::$map[ $key ] = array();
		}

		self::$map[ $key ] = array(
			'label'         => $label,
			'mime_type'     => sanitize_mime_type( $mime_type ),
			'alt_mime_type' => sanitize_mime_type( $alt_mime_type ),
			'multiple'      => (bool) $multiple
		);

	}

	public function unregister_alt_media( $key ) {
		if ( isset( self::$map[ $key ] ) ) {
			unset( self::$map[ $key ] );
		}
	}

	public function save( $key, $attachment_id, $alt_id ) {
		add_post_meta( $attachment_id, sanitize_key( "alt_media_{$key}" ), $alt_id );
	}

	public function delete( $key, $attachment_id, $alt_id ) {
		delete_post_meta( $attachment_id, sanitize_key( "alt_media_{$key}" ), $alt_id );
	}

	public function ajax_get_thumb() {

		// check please
		check_ajax_referer( 'alt_media', 'alt_media_nonce' );

		$response = array(
			'success' => true
		);

		if ( ! isset( $_POST['id'] ) ) {
			$response[ 'success' ] = false;
		} else {

			$key = sanitize_text_field( $_POST['key'] );

			$attachment = intval( $_POST['id'] );

			$alt_attachments = array_filter( array_map( function( $item ) {
				return intval( $item );
			}, $_POST['alt_attachments'] ) );

			// remove existing if multiple is disabled
			if ( ! intval( $_POST['multiple'] ) && isset( $_POST['attachments'] ) ) {

				$attachments = array_filter( array_map( function( $item ) {
					return intval( $item );
				}, $_POST['attachments'] ) );

				foreach( $attachments as $cur_attachment ) {
					$this->delete( $key, $attachment, $cur_attachment );
				}

			}

			$response['attachments'] = array();

			foreach( $alt_attachments as $i => $alt_attachment ) {
				$response['attachments'][ $i ] = array(
					'image' => wp_get_attachment_image( $alt_attachment, 'thumbnail', ! $this->is_image( $alt_attachment ), array(
						'class' => 'file-icon'
					) ),
					'file' => get_attached_file( $alt_attachment ),
					'name' => get_the_title( $alt_attachment ),
					'html' => $this->get_alt_item( $alt_attachment )
				);
				$this->save( $key, $attachment, $alt_attachment );
			}
		}

		header( 'Content-type: application/json' );
		echo json_encode( $response );
		die;
	}

	public function ajax_delete() {

		// check please
		check_ajax_referer( 'alt_media', 'alt_media_nonce' );

		$response = array(
			'success' => true
		);

		if ( ! isset( $_POST['id'] ) ) {
			$response[ 'success' ] = false;
		} else {

			$key = sanitize_text_field( $_POST['key'] );

			$attachment = intval( $_POST['id'] );
			$alt_attachment = intval( $_POST['alt_attachment'] );
			$this->delete( $key, $attachment, $alt_attachment );
		}

		header( 'Content-type: application/json' );
		echo json_encode( $response );
		die;
	}

}
