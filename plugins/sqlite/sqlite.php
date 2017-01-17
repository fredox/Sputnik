<?php

class sqlite extends plugin {

	public $db;
	public $filepath;
	
	public function _INIT_HOOK_sqlite()
	{
		$this->db = new PDO( 'sqlite:' . $this->filepath );
	}
	
	public function install()
	{
		echo "\n[sqlite] Plguin Installation";
		$file = readline( '> Enter the db filepath please:' );
		$this->filepath = $this->path() . $file;
		$this->_INIT_HOOK_sqlite();
		$this->db->exec(
			"CREATE TABLE IF NOT EXISTS system_vars (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			name TEXT,
			value TEXT)"
		);
		$query = $this->db->prepare( "INSERT INTO system_vars (name, value) VALUES ('sqlite_installation_time',". time() .")");
		$query->execute();

		echo "[sqlite] Installed Ok.	";	
	}

	public function lastId( $tableName )
	{
		$sql = 'select id from ' . $tableName . ' ORDER by id DESC';
		$statement = $this->db->query( $sql );

		$result = $statement->fetch();

		return $result['id'];
	}
	
}