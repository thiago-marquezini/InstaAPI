<?php 
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

function JsonStrToObj($JsonTxt)
{
	$_Object = new stdClass();
	$_JsonTxt = json_decode($JsonTxt);
	
	foreach ($_JsonTxt as $Key)
	{
		$KeyName = $Key->name;
		$KeyValue = $Key->value;

	    $_Object->$KeyName = $KeyValue;
	}

	return $_Object;
}


function _session_killer($path)
{
	if (empty($path)) { return false;}
    return is_file($path) ? @unlink($path) : array_map(__FUNCTION__, glob($path.'/*')) == @rmdir($path);
}



?>