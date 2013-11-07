<?php

function my_plugin_load_first()
{
  $path = str_replace( WP_PLUGIN_DIR . '/', '', __FILE__ );
  if ( $plugins = get_option( 'active_plugins' ) ) {
    if ( $key = array_search( $path, $plugins ) ) {
      array_splice( $plugins, $key, 1 );
      array_unshift( $plugins, $path );
      update_option( 'active_plugins', $plugins );
    }
  }
}
add_action( 'activated_plugin', 'my_plugin_load_first' );
?>