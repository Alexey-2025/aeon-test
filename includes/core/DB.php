<?php

namespace Includes\Core;

class DB {

	public $sql;
	public $set=[];
	public $log=[];
	public $exception=true;

	function __construct(){
		$this->sql=$GLOBALS['DBConnect']->connection();
	}
	
	public function query(){
		$this->log[]=self::prepareQuery(func_get_args());
		if($r=$this->sql->query(self::prepareQuery(func_get_args()))){
			if(is_object($r)){
				if($r->num_rows){
					return $r->fetch_all(MYSQLI_ASSOC);
				} else {
					return false;
				}
			} else {
				return true;
			}
		}
		if($this->exception===true){
			throw new \Exception('Ошибка выполнения запроса MySQL "'.self::prepareQuery(func_get_args()).'" ERROR #'.$this->sql->errno.': '.$this->sql->error);
		}
		return null;
	}
	
	/*
		$array => массив
		$key2  => 0 "удаление значений", 1 "значение == 1", 2 "массив добавляется целиком"
	*/
	
	public function key(){
		$v=func_get_args();
		$c=count($v);
		$mc=$c-1;
		$r=[];
		foreach($v[0] as $v2){
			$v3=&$r;
			for($i=2;$i<$c;$i++){
				if($i<$mc){
					if(!isset($v3[$v2[$v[$i]]])){
						$v3[$v2[$v[$i]]]=[];
					}
					$v3=&$v3[$v2[$v[$i]]];
				} else {
					if($v[1]==0){
						$k=$v2[$v[$i]];
						for($i2=2;$i2<$c;$i2++){
							unset($v2[$v[$i2]]);
						}
						$v3[$k]=$v2;
					} else if($v[1]==1){
						$v3[$v2[$v[$i]]]=1;
					} else {
						$v3[$v2[$v[$i]]]=$v2;
					}
				}
			}
		}
		return $r;
	}
	
	public function nKey($a,$a2){
		$r=[];
		foreach($a as $k => $v){
			$r[$k]=[];
			foreach($a2 as $v2){
				if(isset($v[$v2])){
					$r[$k][$v2]=$v[$v2];
				}
			}
		}
		return $r;
	}
	
	public function one(&$v){
		$v=$v[0];
		return $this;
	}
	
	public function json(&$a,$v){
		foreach($a as $v2 => $v3){
			if(is_string($v)){
				if(!is_null($a[$v2][$v])){
					$a[$v2][$v]=json_decode($v3[$v],true);
				}
			} else {
				foreach($v as $v4){
					if(!is_null($a[$v2][$v4])){
						$a[$v2][$v4]=json_decode($v3[$v4],true);
					}
				}
			}
		}
		return $this;
	}
	
	public function id(){
		if($this->sql->insert_id>0){
			return $this->sql->insert_id;
		}
		return false;
	}
	
	protected function prepareQuery($args){
		$query='';
		$raw=array_shift($args);
		$array=preg_split('~(\?[isjl])~u',$raw,null,PREG_SPLIT_DELIM_CAPTURE);
		$anum=count($args);
		$pnum=floor(count($array)/2);
		if($pnum!=$anum){
			$this->error("Передано (<b>$anum</b>) аргумента из (<b>$pnum</b>) в запросе $raw");
		}
		foreach($array as $i=>$part){
			if(($i%2)==0){
				$query.=$part;
				continue;
			}
			$v=array_shift($args);
			switch ($part)
			{
				case '?i':
					$part=$this->escapeInt($v);
					break;
				case '?s':
					$part=$this->escapeString($v);
					break;
				case '?j':
					if(!is_null($v)){
						$part=$this->escapeString(json_encode($v));
					} else {
						$part='NULL';
					}
					break;
				case '?l':
					$part=$this->escapeLike($v);
					break;
			}
			$query .= $part;
		}
		return $query;
	}
	
	protected function escapeInt($v){
		if(is_null($v)){
			return 'NULL';
		} elseif(is_numeric($v) || is_string($v)){
			return (int)$v;
		}
		return 0;
	}
	
	protected function escapeString($v){
		if(is_null($v)){
			return 'NULL';
		}
		return "'".$this->sql->real_escape_string($v)."'";
	}
	
	public function escapeLike($v){
		if(!is_array($v)){
			return "'%".str_replace(array("\\", "\0", "\n", "\r", "'", "\"", "\x1a", "%", "_"), array('\\\\\\\\', '\\0', '\\n', '\\r', '\\\'', '\\"', '\\Z', '\%', '\_'), $v)."%'";
		} elseif($v[1]==1){
			return "'%".str_replace(array("\\", "\0", "\n", "\r", "'", "\"", "\x1a", "%", "_"), array('\\\\\\\\', '\\0', '\\n', '\\r', '\\\'', '\\"', '\\Z', '\%', '\_'), $v)."'";
		} else {
			return "'".str_replace(array("\\", "\0", "\n", "\r", "'", "\"", "\x1a", "%", "_"), array('\\\\\\\\', '\\0', '\\n', '\\r', '\\\'', '\\"', '\\Z', '\%', '\_'), $v)."%'";
		}
	}
	
	protected function error($err){
		throw new \Exception(__CLASS__.": ".$err);
	}

}

?>