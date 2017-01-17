<?php

    include_once( 'sputnik.php' );
	include_once( 'Example.php' );
	
	$requiredPlugins = array(
		 'html'
		,'sqlite'
		,'simpleauth'
	);

		
	$app = new Example( $requiredPlugins );
	
	// API uris
	$app->uris = array(
		'/api/v1/?'	=> 'index'
	);
	
	
	// HTML uris
	$app->getPlugin( 'html' )->htmlUris = array(
		$path . '/api/sputnik' => 'sputnik_info'
	);

	$app->run();
	
	
