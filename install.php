<?php

include_once( 'sputnik.php' );

include_once( 'Example.php' );

$requiredPlugins = array(
	 'html'
	,'sqlite'
	,'simpleauth'
);

$app = new Example( $requiredPlugins );

$app->install();

