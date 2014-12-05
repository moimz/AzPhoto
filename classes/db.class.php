<?php
class db {
	private $connect;
	
	function __construct() {
		$this->connect = @mysql_connect($_ENV['db']['host'],$_ENV['db']['user_id'],$_ENV['db']['password']) or $this->printError(mysql_error());
		@mysql_query("set names utf8") or $this->error(mysql_error());
		@mysql_query("use ".$_ENV['db']['db']) or $this->error(mysql_error());
	}
	
	function getField($table,$field,$query='') {
		$strQuery = 'select `'.$field.'` from `'.$table.'` '.$query;
		$query = @mysql_query($strQuery,$this->connect) or $this->printError(mysql_error(),$strQuery);
		$fetch = @mysql_fetch_assoc($query);
		
		return isset($fetch[$field]) == true ? $fetch[$field] : '';
	}
	
	function getRow($table,$query='') {
		$strQuery = 'select * from `'.$table.'` '.$query;
		$query = @mysql_query($strQuery,$this->connect) or $this->printError(mysql_error(),$strQuery);
		
		$row = mysql_fetch_assoc($query);
		return $row;
	}
	
	function getRows($table,$query='') {
		if (is_array($table) == true) $table = implode('`, `',$table);
		$strQuery = 'select * from `'.$table.'` '.$query;
		$query = @mysql_query($strQuery,$this->connect) or $this->printError(mysql_error(),$strQuery);
		
		$rows = array();
		while ($row = mysql_fetch_assoc($query)) {
			$rows[] = $row;
		}
		
		return $rows;
	}
	
	function getQueryRows($strQuery) {
		$query = @mysql_query($strQuery,$this->connect) or $this->printError(mysql_error(),$strQuery);
		
		$rows = array();
		while ($row = mysql_fetch_assoc($query)) {
			$rows[] = $row;
		}
		
		return $rows;
	}
	
	function getQueryRow($strQuery) {
		$query = @mysql_query($strQuery,$this->connect) or $this->printError(mysql_error(),$strQuery);
		return mysql_fetch_assoc($query);
	}
	
	function getCount($table,$query='',$field='*') {
		$field = $field == '*' ? $field : '`'.$field.'`';
		$strQuery = 'select count('.$field.') from `'.$table.'` '.$query;
		$query = @mysql_query($strQuery,$this->connect) or $this->printError(mysql_error(),$strQuery);
		return array_shift(mysql_fetch_array($query));
	}
	
	function insert($table,$datas) {
		$fields = array();
		$values = array();
		foreach ($datas as $field=>$value) {
			$fields[] = '`'.$field.'`';
			$values[] = '\''.$this->antiInjection($value).'\'';
		}
		$strQuery = 'insert into `'.$table.'` ('.implode(',',$fields).') values ('.implode(',',$values).')';
		@mysql_query($strQuery,$this->connect) or $this->printError(mysql_error(),$strQuery);
		
		$idx = @mysql_insert_id();
		
		return $idx;
	}
	
	function update($table,$datas,$find='') {
		$values = array();
		foreach ($datas as $field=>$value) {
			$values[] = '`'.$field.'`=\''.$this->antiInjection($value).'\'';
		}
		$strQuery = 'update `'.$table.'` set '.implode(',',$values).' '.$find;
		@mysql_query($strQuery,$this->connect) or $this->printError(mysql_error());
	}
	
	function delete($table,$find='') {
		$strQuery = 'delete from `'.$table.'` '.$find;
		@mysql_query($strQuery,$this->connect) or $this->printError(mysql_error());
	}
	
	private function printError($error,$query='') {
		echo $error.' ('.$query.')<br />';
	}
	
	function antiInjection($str) {
		if (!is_numeric($str)) $str = @mysql_real_escape_string($str);
		return $str;
	}
}
?>