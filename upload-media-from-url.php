<?php
/**
 * Plugin Name: Upload media from URL
 * Plugin URI: https://github.com/Robbertvermeulen/upload-media-from-url
 * Description: Upload media files from external URL
 * Version: 1.0
 * Author: Robbert Vermeulen
 * Author URI: https://www.robbertvermeulen.com
 */

/**
 * Constants
 */
define( 'PLUGIN_ADMIN_PAGE_URL', admin_url( 'upload.php?page=upload-media-from-url' ) );

/**
 * Adds submenu page below media tab
 */
function umf_add_menu_pages() {
   add_submenu_page(
      'upload.php',
      __( 'From URL', 'upload_media_from_url' ),
      __( 'Upload from URL', 'upload_media_from_url' ),
      'manage_options',
      'upload-media-from-url',
      'umf_upload_media_from_url_page'
   );
}
add_action( 'admin_menu', 'umf_add_menu_pages' );

/**
 * The submenu page output
 */
function umf_upload_media_from_url_page() { ?>

   <div class="wrap">

      <h1><?php _e( 'Upload from URL', 'upload_media_from_url' ); ?></h1>

      <p>
         <form method="post" method="<?php echo admin_url( 'admin.php' ); ?>">
            <input type="text" class="large-text" name="url" placeholder="<?php _e( 'Insert URL', 'upload_media_from_url' ); ?>">
            <?php submit_button( __( 'Upload', 'upload_media_from_url' ), 'primary', 'upload_external_media' ); ?>
         </form>
      </p>

      <?php
      if ( ! empty( $_GET['uploaded'] ) ) {
         $attachment = get_post( $_GET['uploaded'] ); ?>
         <ul>
            <?php echo '<li><a href="' . get_edit_post_link( $attachment->ID ) . '">' . sprintf( __( 'Uploaded "%s"', 'upload_external_media' ), $attachment->post_title ) . '</a></li>'; ?>
         </ul>
      <?php } ?>

   </div>

   <?php
}

/**
 * Handles the upload form on admin init
 */
function umf_handle_upload_media_from_url() {

   if ( ! empty( $_POST['upload_external_media'] ) ) {

      if ( empty( $_POST['url'] ) )
         return;

      $url = $_POST['url'];

      // Retrieve date directory path from url
      $components = explode( '/', $url );
      $time = implode( '/', array_slice( $components, -3, 2 ) );

      // Get file name
      $filename = basename( $url );

      // Create destination path
      $upload_dir = wp_upload_dir( $time );
      $destination = $upload_dir['path'] . '/' . $filename;

      if ( ! file_exists( $destination ) ) {

         // Copy external url to destination on server
         copy( $url, $destination );

         // Insert attachment
         $args = [
            'post_title' => $filename,
            'post_mime_type' => mime_content_type( $destination ),
            'post_date' => date( "Y-m-d H:i:s", strtotime( str_replace( '/', '-', $time ) ) )
         ];
         $attachment_id = wp_insert_attachment( $args, $destination );

         // Add attachment metadata
         $attach_data = wp_generate_attachment_metadata( $attachment_id, $destination );
         wp_update_attachment_metadata( $attachment_id, $attach_data );

         wp_redirect( add_query_arg( 'uploaded', $attachment_id, PLUGIN_ADMIN_PAGE_URL ) );
         exit;

      }
   }
}
add_action( 'admin_init', 'umf_handle_upload_media_from_url' );
