<?php

function controller_user($act, $d) {
	if($act=='edit_window'){
		return Users::editUsersWindow($d);
	}
	if($act=='edit'){
		return Users::editUsers($d);
	}
	if($act=='delete'){
		return Users::deleteUser($d);
	}
	return '';
}
