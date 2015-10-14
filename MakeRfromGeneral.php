<?php
/**
 * Created by PhpStorm.
 * User: Вадим
 * Date: 27.07.2015
 * Time: 13:00
 */

require_once('config.php');
require_once('class_xml_parser.php');

$dirofdate = "./10323_RR";//пусть к директории с датами измерений
chdir($dirofdate);
$dir = opendir(".");
while (false !== ($dateofsquares = readdir($dir)))//проходим по всем датам
{
	if($dateofsquares != "." && $dateofsquares != ".."){
		echo $dateofsquares."\n";
		//chdir("./$dateofsquares");
		$xmlfile = glob("$dateofsquares/*.xml")[0];//находим общий xml-файл с результатами обработки апекса
		echo $xmlfile."\n";
		$xml = new cls_xml_parser();
		$array = $xml->loadFile($xmlfile, false, 'root');
		$arrayTrack = $array['root']['track'];
		$squareTrack = array();
		foreach ($arrayTrack as $track)
		{
			$square = $track['meas'][0]['filename'];
			$s = explode(".",$square);
			$square = $s[0];
			$squareTrack["$square"][] = $track;

		}

		foreach ($squareTrack as $dirl=>$tracks)
		{
			$Rxml = new cls_xml_parser();
			$Rstr = $Rxml->CreateFromArray('tracks',array('track'=>$tracks));
			$Rstr = '<?xml version="1.0"?>'."<ROOT>".$Rstr;
			$Rstr .= "</ROOT>";
			file_put_contents("$dateofsquares/{$dirl}/R00001.xml",$Rstr);
		}

		//chdir("..");
	}
}
/*
$filepath =('./10323_R/20150703_10323.xml');//
$xml = new cls_xml_parser();
$array = $xml->loadFile($filepath, false, 'root');
$arrayTrack = $array['root']['track'];
//print_r($array['root']['track']);
$squareTrack = array();
foreach ($arrayTrack as $track)
{
	$square = $track['meas'][0]['filename'];
	$s = explode(".",$square);
	$square = $s[0];
	$squareTrack["$square"][] = $track;

	//print("$square \n");
}

chdir("10323_R/20150704");
foreach ($squareTrack as $dir=>$tracks)
{
	$Rxml = new cls_xml_parser();
	$Rstr = $Rxml->CreateFromArray('tracks',array('track'=>$tracks));
	$Rstr = '<?xml version="1.0"?>'."<ROOT>".$Rstr;
	$Rstr .= "</ROOT>";
	file_put_contents("{$dir}/R00001.xml",$Rstr);


}

print_r($squareTrack);*/


