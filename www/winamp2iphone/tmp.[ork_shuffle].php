<?php

require('../shared/php/functions.php');
$limit = 20;
$import = ImportPlaylist('C:\Users\Administrator\AppData\Roaming\Winamp\Plugins\ml\plf0001.m3u8');
//ork_shuffle($import);


$a = $import;
$out = [];
/*
foreach($a as $v) {
	//$s = preg_replace('/[^a-zA-Z]+/', '', $v);
	//$s = $s.'kjodcfakajcawnvciebvuyawvbaiyv'; // additional chars
	$s = str_shuffle($v);
	$c = sprintf('%u', crc32($s));
	$out[$c] = $v;
}
*/
foreach($a as $v) {
	//$k = str_shuffle($v);
	$k = sprintf('%u', crc32($v));
	$k = str_shuffle($k);
	$out[$k] = $v;
}

echo '<pre style="text-align:left">';
print_r($out);

