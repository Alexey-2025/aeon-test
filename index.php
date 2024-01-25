<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 'On');

// INIT

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
init_controllers_common();

DB::connect();

$DBConnect=new includes\core\DBConnect;//Чтоб не было множество подключений - создаем одно подключение к базе данных, в дальнейшем передаем конструктуру в классе DB "includes\core\DB.php"
$DB=new includes\core\DB;

// SESSION

Session::init();
Route::init();

$g['path'] = Route::$path;
HTML::assign('global', $g);
HTML::display('./partials/index.html');
