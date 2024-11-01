<!DOCTYPE html>
<html lang="en">
<head>
</head>
<body>
<div id="root"></div>
<!-- START DEV -->
<!--option dev - use the localhost bundle.js -->
<!-- uncomment the below line to use the localhost bundle.js -->
<?php
?>
<!-- END DEV -->

<!-- START PROD -->
<!-- option prod - use the locally built bundle.js in the plugin. -->
<?php
const JSFILENAME = 'assets/js/main.js';
wp_enqueue_script( 'expertrec_react_app', plugins_url( JSFILENAME, EXPERTREC_PLUGIN_ROOT_FILE ), array(), filemtime( EXPERTREC_PLUGIN_DIR_PATH . JSFILENAME ), true );
const CSSFILENAME = 'assets/css/main.css';
wp_enqueue_style( 'expertrec_react_app', plugins_url( CSSFILENAME, EXPERTREC_PLUGIN_ROOT_FILE ), array(), filemtime( EXPERTREC_PLUGIN_DIR_PATH . CSSFILENAME ), 'all' );
?>

<!-- Add the nonce field here -->
<?php wp_nonce_field( 'expertrec_search_nonce', 'expertrec_search_nonce' ); ?>

<!-- END PROD -->
<div>
	<?php require 'expertrec-page-footer.php'; ?>
</div>
</body>
</html>
