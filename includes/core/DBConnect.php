<?php

namespace Includes\Core;

class DBConnect {

public $sql;

	function __construct(){
		$this->sql=new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		if(!$this->sql->connect_error){
			mysqli_set_charset($this->sql, "utf8");
		}else{
			exit('<b>Произошла ошибка при подключение к базе данных</b> ERROR #'.$this->sql->connect_errno.': '.$this->sql->connect_error);
		}
	}

	public function connection(){
		return $this->sql;
	}

}

?>