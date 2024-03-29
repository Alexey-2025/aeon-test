<?php

function controller_users() {	
	//vars
	$offset=isset($_GET['offset']) ? flt_input($_GET['offset']) : 0;
	//info
	$users = Users::usersList(['offset' => $offset, 'search' => $_GET['search'] ?? '']);
	//output
	HTML::assign('users', $users['items']);
	HTML::assign('paginator', $users['paginator']);
	HTML::assign('search', $users['search']);
	HTML::assign('offset', $offset);
	HTML::assign('section', 'users.html');
	HTML::assign('main_content', 'home.html');
}
