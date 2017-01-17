<?php

include_once( 'sputnik.php' );
include_once( 'appedantic.php' );

$requiredPlugins = array(
	 'html'
	,'sqlite'
	,'simpleauth'
);

$app = new Appedantic( $requiredPlugins );

$app->install();

$db = $app->getPlugin( 'sqlite' )->db;

$db->exec(
	"CREATE TABLE IF NOT EXISTS words (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		word TEXT,
		description TEXT,
		examples TEXT,
		creation_time INTEGER,
		update_time INTEGER,
		publish_time INTEGER
	)"
);