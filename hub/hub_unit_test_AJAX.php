<?php
require_once dirname(dirname(__FILE__))."/projects.php";


$methodName = $_POST['methodName'];
//call_user_func_array(array($this, $methodName), array($arg1, $arg2, $arg3));
call_user_func_array(array($this, $methodName), array());

?>