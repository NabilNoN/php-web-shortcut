<?php
require_once 'mysql.php';
require_once 'database.php';

function initDatabase($tablePrefix="",$host=db_host,$user=db_user,$password=db_pwd,$name=db_name){
    $sql = new Mysql($host, $user, $password, $name);
    $sql->setTablePrefix($tablePrefix);
    return $sql;
}
//auto init database
//$sql = initDatabase();