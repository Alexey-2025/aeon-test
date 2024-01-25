<?php

class Users {

 	public static function usersList($d=[]){
		$search=[
			0 => '',
			1 => '',
			2 => '',
		];
		$url='users?';
		
		if(isset($d['search'])){
			$query="";
			if(isset($d['search']['first_name']) && is_string($d['search']['first_name']) && trim($d['search']['first_name'])){
				$d['search']['first_name']=trim($d['search']['first_name']);
				$query.="`first_name` LIKE ".$GLOBALS['DB']->escapeLike($d['search']['first_name'])." AND ";
				$search[0]=htmlspecialchars($d['search']['first_name'], ENT_QUOTES, 'UTF-8');
				$url.='search[first_name]='.urlencode($d['search']['first_name']).'&';
			}
			if(isset($d['search']['email']) && is_string($d['search']['email']) && trim($d['search']['email'])){
				$d['search']['email']=trim($d['search']['email']);
				$query.="`email` LIKE ".$GLOBALS['DB']->escapeLike($d['search']['email'])." AND ";
				$search[1]=htmlspecialchars($d['search']['email'], ENT_QUOTES, 'UTF-8');
				$url.='search[email]='.urlencode($d['search']['email']).'&';
			}
			if(isset($d['search']['phone']) && is_string($d['search']['phone']) && trim($d['search']['phone'])){
				$d['search']['phone']=$search[2]=preg_replace('%[^0-9]+%', '', $d['search']['phone']);
				$query.="`phone` LIKE ".$GLOBALS['DB']->escapeLike($d['search']['phone'])." AND ";
				$url.='search[phone]='.$search[2].'&';
			}
		}
		$limit=20;
		$offset=((isset($d['offset']) && is_numeric($d['offset']) && $d['offset']>0) ? (int)($d['offset']/$limit)*$limit : 0);//Чтоб передавали правильно offset - делим на лимит и умножаем на лимит (если передать 39 offset станет 20 как и должен быть на второй странице)
		$items=[];
		$q=$GLOBALS['DB']->query("SELECT COUNT(*) FROM `users`".((!isset($query) || $query=="")? "" : " WHERE ".substr($query,0,-5)));
		$count=$q[0]['COUNT(*)'];
		
		if($count>0){
			if($count<$offset){//Показываем последнюю страницу
				$offset=(int)($count/$limit)*$limit;
			}
			if($q=$GLOBALS['DB']->query("SELECT * FROM `users` ".((!isset($query) || $query=="")? "" : "WHERE ".substr($query,0,-4))."LIMIT $offset,$limit")){
				foreach($q as $key => $v){
					$items[$key]['userId']=$v['user_id'];
					$items[$key]['plotId']=$v['plot_id'];
					$items[$key]['firstName']=$v['first_name'];
					$items[$key]['lastName']=$v['last_name'];
					$items[$key]['email']=$v['email'];
					$items[$key]['phone']=$v['phone'];
					$items[$key]['lastLogin']=date("Y-m-d H:i:s", $v['last_login']);
				}
			}
		}
		paginator($count, $offset, $limit, $url, $paginator);
		return ['items' => $items, 'search' => $search, 'paginator' => $paginator, 'url' => (($offset>0)? $url.'offset='.$offset : substr($url,0,-1))];
	}
	
	public static function userIdFetch($id,$r=false){
		if($q=$GLOBALS['DB']->query("SELECT * FROM users WHERE `user_id`=?i",$id)){
			if($r===false){
				HTML::assign('user', [
					'id' => $q[0]['user_id'],
					'plot_id' => $q[0]['plot_id'],
					'first_name' => $q[0]['first_name'],
					'last_name' => $q[0]['last_name'],
					'email' => $q[0]['email'],
					'phone' => $q[0]['phone'],
				]);
				return true;
			} else {
				return [
					'id' => $q[0]['user_id'],
					'plot_id' => $q[0]['plot_id'],
					'first_name' => $q[0]['first_name'],
					'last_name' => $q[0]['last_name'],
					'email' => $q[0]['email'],
					'phone' => $q[0]['phone'],
				];
			}
		}
		return false;
	}
	
	public static function usersListFetch($d=[]){
		$users=self::usersList($d);
		HTML::assign('users', $users['items']);
		HTML::assign('search', $users['search']);
		return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $users['paginator'], 'url' => $users['url']];
	}
	
	public static function editUsers($d=[]){
		$error=[];
		
		if(!isset($d['first_name']) || !trim($d['first_name'])){
			$error['first_name'][]='First name не может быть пустым.';
		}
		if(!isset($d['last_name']) || !trim($d['last_name'])){
			$error['last_name'][]='Last name не может быть пустым.';
		}
		if(!isset($d['email']) || !trim($d['email'])){
			$error['email'][]='Email не может быть пустым.';
		} else {
			preg_match('/^[a-zA-Z0-9._-]+@[a-zA-ZА-Яа-яЁё0-9._-]+\.[a-zA-ZА-Яа-я]+/u',$d['email'],$email);
			if(!isset($email[0]) || mb_strlen($email[0],'UTF-8')!=mb_strlen($d['email'])){
				$error['email'][]='Некорректно заполнено поле Email.';
			} else {
				$d['email']=mb_strtolower($d['email'],'UTF-8');//Приводим email к нижнему регистру
			}
		}
		if(!isset($d['phone']) || !trim($d['phone'])){
			$error['phone'][]='Phone не может быть пустым.';
		} else {
			$d['phone']=preg_replace('%[^0-9]+%', '', $d['phone']);
			if(!$d['phone']){
				$error['phone'][]='Некорректно заполнено поле Phone.';
			}
		}
		
		if(!count($error)){
			$plots=array_unique(explode(',',$d['plot_id']));
			foreach($plots as $key => $v){
				$plots[$key]=(int)$v;
				if($plots[$key]>0){
					$plot=Plot::plot_info($v);
					if($plot['id']==0){
						unset($plots[$key]);
					}
				} else {
					unset($plots[$key]);
				}
			}
			if(count($plots)){
				sort($plots);
				$plots=implode(',',array_unique($plots));
			} else {
				$plots='';
			}
			if(isset($d['id']) && $q=self::userIdFetch($d['id'],true)){
				$GLOBALS['DB']->query("UPDATE `users` SET `plot_id`='$plots', `first_name`=?s, `last_name`=?s, `email`=?s, `phone`={$d['phone']}, `updated`=".Session::$ts." WHERE `user_id`={$q['id']}",$d['first_name'],$d['last_name'],$d['email']);
			} else {
				$GLOBALS['DB']->query("INSERT `users` SET `plot_id`='$plots', `first_name`=?s, `last_name`=?s, `email`=?s, `phone`={$d['phone']}, `last_login`=".Session::$ts,$d['first_name'],$d['last_name'],$d['email']);
			}
			$users=self::usersList($d);
			HTML::assign('users', $users['items']);
			HTML::assign('search', $users['search']);
			return ['status' => 'success', 'html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $users['paginator']];
		}
		
		return ['status' => 'error', 'error' => $error];

	}
		
	public static function editUsersWindow($d=[]){
		if(isset($d['id'])){
			self::userIdFetch($d['id']);
		}
		return ['html' => HTML::fetch('./partials/users_edit.html')];
	}
	
	public static function deleteUser($d=[]){
		if(isset($d['user_id'])){
			$GLOBALS['DB']->query("DELETE FROM `users` WHERE `user_id`=?i",$d['user_id']);
			$users=self::usersList($d);
			HTML::assign('users', $users['items']);
			HTML::assign('search', $users['search']);
			return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $users['paginator'], 'url' => $users['url']];
		}
    }

}
