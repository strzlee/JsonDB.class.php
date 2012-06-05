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
		
			JsonDB -> select ( "table", "key", "value" ) - Selects multible lines which contains the key/value and returns it as array
			JsonDB -> selectAll ( "table" )  - Returns the entire file as array
			JsonDB -> update ( "table", "key", "value", ARRAY ) - Replaces the line which corresponds to the key/value with the array-data
			JsonDB -> updateAll ( "table", ARRAY ) - Replaces the entire file with the array-data
			JsonDB -> insert ( "table", ARRAY ) - Appends a row, returns true on success
			JsonDB -> delete ( "table", "key", "value" ) - Deletes all lines which corresponds to the key/value, returns number of deleted lines
			JsonDB -> deleteAll ( "table" ) - Deletes the whole data, returns "true" on success
*/

class JsonTable {

	protected $jsonFile;
	protected $fileHandle;
	protected $fileData = array();
	
	public function __construct($_jsonFile) {
		if (file_exists($_jsonFile)) {
			$this->jsonFile = $_jsonFile;
			$this->fileData = json_decode(file_get_contents($this->jsonFile), true);
			$this->lockFile();
		}
		else throw new Exception("JsonTable Error: File not found: ".$_jsonFile);
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
	
	public function insert($data = array()) {
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
	
}

class JsonDB {

	protected $path = "./";
	protected $fileExt = ".json";
	protected $tables = array();
	
	public function __construct($path) {
		if (is_dir($path)) $this->path = $path;
		else throw new Exception("JsonDB Error: Path not found");
	}
	
	protected function getTableInstance($table) {
		if (isset($tables[$table])) return $tables[$table];
		else return $tables[$table] = new JsonTable($this->path.$table);
	}
	
	public function __call($op, $args) {
		if ($args && method_exists("JsonTable", $op)) {
			$table = $args[0].$this->fileExt;
			return $this->getTableInstance($table)->$op($args);
		} else throw new Exception("JsonDB Error: Unknown method or wrong arguments ");
	}
	
	public function setExtension($_fileExt) {
		$this->fileExt = $_fileExt;
		return $this;
	}
	
}

?>
