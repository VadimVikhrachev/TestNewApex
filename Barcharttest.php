<?php
/**
 * Created by PhpStorm.
 * User: Вадим
 * Date: 14.08.2015
 * Time: 17:50
 */

require_once("Barchart.php");
$d="20150704";
$b = new Barchart();
$b->GetDataArray("RESULT10323-{$d}2cropmedian.txt");
$b->SetIntervalArray(array(13,14,15,16,17));
$b->MakeBarchart("Apex","Kalesa","sum");
/*$b->GetDataArray("RESULT10323-{$d}2cropmedian.txt");
$b->SetIntervalArray(array(13,14,15,16,17));
$b->CountIntervals();
$b->CalculateData();
$b->MakeNames();
$b->MakeCfg();*/
copy("D:/TestNewApex/cfg-Kalesa","D:/phantomjs-2.0.0-windows/phantomjs-2.0.0-windows/bin/cfg");
chdir("D:/phantomjs-2.0.0-windows/phantomjs-2.0.0-windows/bin");
exec("phantomjs highcharts-convert.js -infile cfg -outfile g{$d}.png");
copy("g{$d}.png","D:/TestNewApex/g{$d}-Kalesa.png");

