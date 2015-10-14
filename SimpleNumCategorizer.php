<?php
/**
 * Created by PhpStorm.
 * User: Вадим
 * Date: 20.02.2015
 * Time: 12:15
 */

//пройти по каждому симплнуму и вытащить id трека, являющийся папкой назначения
echo getcwd();
$a = glob("./10323_RR/*[0-9]");
print_r($a);


foreach ($a as $i){
//$i="./10094Image";
$path = $i."/SimpleNum";
	//$path = "./10094_R/20141001/SimpleNum";
	chdir($path);
	$output = opendir(".");
	//chdir($path);

	//echo "$i";



	while(false !== ($file = readdir($output))){
		if($file != "." && $file != ".." && $file!=""){
			//echo realpath($file)."\n";
			$arfile = file($file);
			$arrecord = explode(" ", $arfile[2]);
			$pathinfile = array_pop($arrecord);
			$arexploderecord = explode("/", $pathinfile);
			$fitname = array_pop($arexploderecord);
			//echo "$fitname\n";
			$arfin = explode(".", $fitname);
			$dirname = $arfin[0];
			//echo "$dirname\n";
			$oldname = realpath($file);
			$newname = realpath("../$dirname");
			//echo $oldname."\n";
			//echo $newname."\n";

			if(file_exists("../$dirname")){
				rename("$oldname", "$newname/$file");
			}


		}


	}


	chdir("/home/vadim");

}
	//chdir("./output1");
echo getcwd();


