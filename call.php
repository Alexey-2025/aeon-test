<?php

// INIT

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') echo '';

require('./cfg/general.inc.php');
require('./includes/core/functions.php');

spl_autoload_register(function($var){
	$var=str_replace('\\', '/', __DIR__.'/'.$var).'.php';
	if(file_exists($var)){
		require_once($var);
		return true;
	}
	return false;
});

init_classes();
init_controllers_call();

DB::connect();

$DBConnect=new includes\core\DBConnect;//Чтоб не было множество подключений - создаем одно подключение к базе данных, в дальнейшем передаем конструктуру в классе DB "includes\core\DB.php"
$DB=new includes\core\DB;

// VARS

$location = isset($_POST['location']) ? flt_input($_POST['location']) : NULL;
$data = isset($_POST['data']) ? flt_input($_POST['data']) : NULL;

$dpt = $location['dpt'] ?? NULL;
$act = $location['act'] ?? NULL;

// SESSION

Session::init(1);
Route::route_call($dpt, $act, $data);
