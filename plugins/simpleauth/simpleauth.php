<?php

class simpleauth extends plugin {

	public $dependencies = array( 'sqlite' );
	public $authUris     = array();

	public function _PRE_HOOK_simpleauth()
	{
		$urlToken = $this->sputnik->request['function'];
		if ( in_array( $urlToken, array_keys( $this->authUris ) ) ) {

			if ( !in_array( $this->sputnik->request['method'], $this->authUris[$urlToken]['methods'] ) )
				return;

			// TODO: only header auth for the moment.
			$headers = $this->sputnik->getRequestHeaders();

			if ( in_array( 'Authorization', array_keys( $headers ) ) ) {
				$authString = base64_decode( $headers['Authorization'] );
				list( $username, $password ) = explode( ':', $authString );

				if ( $this->authorizeUser( $username, $password ) )
					return;
			}

			throw new Exception( 'Unauthorized', 401 ); 
		}
	}

	public function install()
	{
		$db = $this->sputnik->getPlugin( 'sqlite' )->db;

		$db->exec(
			"CREATE TABLE IF NOT EXISTS users (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				username TEXT,
				hash TEXT
			)"
		);

		echo "\n[simpleauth] Plguin Installation\n";
		$username = readline( '> Enter the user:' );
		$password = readline( '> Enter the password:' );

		$this->createUser( $username, $password );
		echo "[simpleauth] Installed Ok.";
	}

	public function createUser( $username, $password )
	{
		// this system is not secure, must be done like the link below.
		// See: https://alias.io/2010/01/store-passwords-safely-with-php-and-mysql/

		$hash = md5($password);

		$db  = $this->sputnik->getPlugin( 'sqlite' )->db;
		$sql = 'INSERT INTO users (username, hash) VALUES ("'.$username.'","'.$hash.'")';

		$query = $db->prepare( $sql );
		$query->execute(); 
	}

	public function authorizeUser( $username, $password )
	{
		$db = $this->sputnik->getPlugin( 'sqlite' )->db;

		$sth = $db->prepare( 'SELECT hash FROM users WHERE username = :username LIMIT 1' );

		$sth->bindParam( ':username', $username );

		$sth->execute();

		$user = $sth->fetch(PDO::FETCH_OBJ);

		if ( md5($password) == $user->hash ) {
			return true;
		}

		return false;
	}
	
}