<?php
/**
 * Created by PhpStorm.
 * User: Вадим
 * Date: 23.03.2015
 * Time: 16:14
 */
//$globfile = fopen("RESULT1.txt","w");
$recordpath = "/storage/reserved/vadim";
$ar = glob("./10323_R/[0-9]*",GLOB_ONLYDIR);
print_r($ar);

foreach ($ar as $idir){
	$date = substr($idir,-8,8);
	$squares = glob("$idir/[0-9]*");
	foreach ($squares as $square){
	if(file_exists($square."/restest2.txt")){
		$data = file_get_contents($square."/restest2.txt");
		echo $data."\n";
		echo "123\n";
		//$data=file_get_contents($idir);
		file_put_contents("$recordpath/$date.json", $data, FILE_APPEND);
	}
	}
	$res = file_get_contents("$recordpath/$date.json");
	$res = "[".$res;
	$res = substr($res,0,-2);
	$res = $res."]";
	file_put_contents("$recordpath/$date.json", $res);
	}


