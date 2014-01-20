<?php

// Straussn's JSON-Databaseclass
// Handle JSON-Files like a very, very simple DB. Useful for little ajax applications.
// Last change: 05-06-2012
// Version: 1.0b
// by Manuel Strauss, Web: http://straussn.eu, E-Mail: StrZlee@gmx.net, Skype: StrZlee

/*
	Example:

		$db = new JsonDB( "./path_to_my_jsonfiles/" );
		$result = $db -> select( "json_file_name_without_extension", "search-key", "search-value" );
			
			Example JSON-File: 
				[
					{"ID": "0", "Name": "Hans Wurst", "Age": "12"},
					{"ID": "1", "Name": "Karl Stoascheissa", "Age": "15"},
					{"ID": "2", "Name": "Poidl Peidlbecka", "Age": "14"}
				]
		
		Method Overview:
		
			new JsonDB(".(path_to_my_jsonfiles/");
			JsonDB -> createTable("hello_world_table");
			JsonDB -> select ( "table", "key", "value" ) - Selects multible lines which contains the key/value and returns it as array
			JsonDB -> selectAll ( "table" )  - Returns the entire file as array
			JsonDB -> update ( "table", "key", "value", ARRAY ) - Replaces the line which corresponds to the key/value with the array-data
			JsonDB -> updateAll ( "table", ARRAY ) - Replaces the entire file with the array-data
			JsonDB -> insert ( "table", ARRAY , $create = FALSE) - Appends a row, returns true on success. if $create is TRUE, we will create the table if it doesn't already exist.
			JsonDB -> delete ( "table", "key", "value" ) - Deletes all lines which corresponds to the key/value, returns number of deleted lines
			JsonDB -> deleteAll ( "table" ) - Deletes the whole data, returns "true" on success
			new JsonTable("./data/test.json", $create = FALSE) - If $create is TRUE, creates table if it doesn't exist.
*/

class JsonTable {

	protected $jsonFile;
	protected $fileHandle;
	protected $fileData = array();
	
	public function __construct($_jsonFile, $create = false) {
		if (!file_exists($_jsonFile)) {
			if($create === true)
			{
				$this->createTable($_jsonFile, true);
			}
			else
			{
				throw new Exception("JsonTable Error: Table not found: ".$_jsonFile);
			}
		}

		$this->jsonFile = $_jsonFile;
		$this->fileData = json_decode(file_get_contents($this->jsonFile), true);
		$this->lockFile();
	}
	
	public function __destruct() {
		$this->save();
		fclose($this->fileHandle);	
	}
	
	protected function lockFile() {
		$handle = fopen($this->jsonFile, "w");
		if (flock($handle, LOCK_EX)) $this->fileHandle = $handle;
		else throw new Exception("JsonTable Error: Can't set file-lock");
	}
	
	protected function save() {
		if (fwrite($this->fileHandle, json_encode($this->fileData))) return true;
		else throw new Exception("JsonTable Error: Can't write data to: ".$this->jsonFile);
	}
	
	public function selectAll() {
		return $this->fileData;
	}
	
	public function select($key, $val = 0) {
		$result = array();
		if (is_array($key)) $result = $this->select($key[1], $key[2]);
		else {
			$data = $this->fileData;
			foreach($data as $_key => $_val) {
				if (isset($data[$_key][$key])) {
					if ($data[$_key][$key] == $val) {
						$result[] = $data[$_key];
					}
				}
			}
		}
		return $result;
	}
	
	public function updateAll($data = array()) {
		if (isset($data[0]) && substr_compare($data[0],$this->jsonFile,0)) $data = $data[1];
		return $this->fileData = array($data);
	}
	
	public function update($key, $val = 0, $newData = array()) {
		$result = false;
		if (is_array($key)) $result = $this->update($key[1], $key[2], $key[3]);
		else {
			$data = $this->fileData;
			foreach($data as $_key => $_val) {
				if (isset($data[$_key][$key])) {
					if ($data[$_key][$key] == $val) {
						$data[$_key] = $newData;
						$result = true;
						break;
					}
				}
			}
			if ($result) $this->fileData = $data;
		}
		return $result;
	}
	
	public function insert($data = array(), $create = false) {
		if (isset($data[0]) && substr_compare($data[0],$this->jsonFile,0)) $data = $data[1];
		$this->fileData[] = $data;
		return true;
	}
	
	public function deleteAll() {
		$this->fileData = array();
		return true;
	}
	
	public function delete($key, $val = 0) {
		$result = 0;
		if (is_array($key)) $result = $this->delete($key[1], $key[2]);
		else {
			$data = $this->fileData;
			foreach($data as $_key => $_val) {
				if (isset($data[$_key][$key])) {
					if ($data[$_key][$key] == $val) {
						unset($data[$_key]);
						$result++;
					}
				}
			}
			if ($result) {
				sort($data);
				$this->fileData = $data;
			}
		}
		return $result;
	}

	public function createTable($tablePath) {
		if(is_array($tablePath)) $tablePath = $tablePath[0];
		if(file_exists($tablePath))
			throw new Exception("Table already exists: ".$tablePath);

		if(fclose(fopen($tablePath, 'a')))
		{
			return true;
		}
		else
		{
			throw new Exception("New table couldn't be created: ".$tablePath);
		}
	}	
	
}

class JsonDB {

	protected $path = "./";
	protected $fileExt = ".json";
	protected $tables = array();
	
	public function __construct($path) {
		if (is_dir($path)) $this->path = $path;
		else throw new Exception("JsonDB Error: Database not found");
	}
	
	protected function getTableInstance($table, $create) {
		if (isset($tables[$table])) return $tables[$table];
		else return $tables[$table] = new JsonTable($this->path.$table, $create);
	}
	
	public function __call($op, $args) {
		if ($args && method_exists("JsonTable", $op)) {
			$table = $args[0].$this->fileExt;
			$create = false;
			if($op == "createTable")
			{
				return $this->getTableInstance($table, true);
			}
			elseif($op == "insert" && isset($args[2]) && $args[2] === true)
			{
				$create = true;
			}
			return $this->getTableInstance($table, $create)->$op($args);
		} else throw new Exception("JsonDB Error: Unknown method or wrong arguments ");
	}
	
	public function setExtension($_fileExt) {
		$this->fileExt = $_fileExt;
		return $this;
	}
	
}

?>
