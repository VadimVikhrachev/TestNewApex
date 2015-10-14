<?php
/**
 * Created by PhpStorm.
 * User: Вадим
 * Date: 05.02.2015
 * Time: 17:21
 */
function binarySearchPosition($array,$needle)
{
	//$array=sort($array);
	$n=count($array);
	if($n==0){
		return 0;
	}
	$first=0;
	$last = $n;

	while ($first<$last)
	{
		$mid =(integer)( $first+($last-$first)/2);
		if($needle<=$array[$mid]){
			$last=$mid;
		}else{
			$first = $mid+1;
		}

	}
	return $last;

}