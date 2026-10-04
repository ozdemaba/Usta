<?php
$root = dirname( __DIR__, 2 );
$required = array(
    $root . '/wordpress/goi-core/includes/class-goi-core-map.php',
    $root . '/wordpress/goi-core/includes/class-goi-core-map-data.php',
    $root . '/wordpress/goi-core/assets/goi-map.js',
    $root . '/wordpress/goi-core/assets/goi-map.css',
);
foreach ( $required as $file ) {
    if ( ! is_file( $file ) ) { fwrite( STDERR, "Missing map file: {$file}\n" ); exit( 1 ); }
}
$map = file_get_contents( $root . '/wordpress/goi-core/includes/class-goi-core-map.php' );
foreach ( array( 'map/config', 'map/viewport', 'deadline_class', 'valid_lat', 'valid_lon', 'latitude', 'longitude' ) as $token ) {
    if ( strpos( $map, $token ) === false ) { fwrite( STDERR, "Missing map contract token: {$token}\n" ); exit( 1 ); }
}
fwrite( STDOUT, "GOI map contract passed.\n" );
