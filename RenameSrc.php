<?php
/**
 * Created by PhpStorm.
 * User: Вадим
 * Date: 02.04.2015
 * Time: 15:22
 */

$a = glob("./10323_RR/[0-9]*/[0-9]*/images/*.fit/src.jpg");

//region Description
foreach($a as $i){
	$s = substr_replace($i, "jpeg", -3);
	rename($i, $s);
	//echo "$s\n";
}
//endregion
print_r($a);
