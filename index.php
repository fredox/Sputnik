<?php

    include_once( 'sputnik.php' );
	include_once( 'appedantic.php' );
	
	$requiredPlugins = array(
		 'html'
		,'sqlite'
		,'simpleauth'
	);

	class Example extends Sputnik {
		public function GET_index()
		{
			$this->response['data'] = 'This is an Example';
		}
	}
	
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
	
	