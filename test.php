<?php
/**
 * Created by PhpStorm.
 * User: Вадим
 * Date: 03.02.2015
 * Time: 13:23
 */
//$a=glob('*simple*.dat',GLOB_NOSORT);
//array_walk($a,'realpath');
//print(realpath('R00007.xml'));

//print_r($a);



require_once('Reader.php');
require_once('binarySearchPosition.php');
/*ob_start();
print_r ($a);
$r=ob_get_clean();

$f=fopen ("testik.txt","w");
fputs($f,$r);


fclose($f);
echo $r;*/
chdir("./10323_R");
$a = glob("[0-9]*");
foreach ($a as $i){
	//$pathdate = "./Testdir1";
	chdir ($i);
	$testdir = glob("[0-9]*[0-9]");
	print_r($testdir);
//chdir('./TestDir');
	echo getcwd();
	$n=0;
	foreach($testdir as $dir){
		//if($dir != "." && $dir != ".."){
			//echo $dir;
			chdir($dir);
			//unlink("resterr.txt");
			//chdir("../..");
			$n+=1;
			echo $n;
			try{
				print "CWD1 = ".getcwd()."\n";
				$rKalesa = new ReaderKalesa(".");
				$rApex = new ReaderApex(".");
				//print(realpath($r->path));
				//print(getcwd());
				echo "DIR=";
				$pathtoimages = getcwd();
				print(getcwd());
				echo "\n";
				$a1 = $rKalesa->readTrackArray();
				$a2 = $rApex->readTrackArray();
				if (!$a1 && !$a2){chdir("..");continue;}

				//print_r($rApex->arrayTrack);
				$comp = new Comparator($rApex, $rKalesa, 5,$pathtoimages);
				if($comp->Compare()){
					ob_start();
					$comp->PrintApex1();
					$comp->PrintKalesa1();
					$comp->PrintFullArray1();
					//print ("yes");
					$reswrite1 = ob_get_clean();

					$fres1 = fopen("restest1.txt", "w");
					fputs($fres1, $reswrite1);
					fclose($fres1);

					ob_start();
					/*print ($rApex->trackCount[" 142460151 "]);
					echo "\n";*/

					$comp->PrintFullArray2();
					$comp->PrintApex2();
					$comp->PrintKalesa2();


					//print ("yes");
					$reswrite2 = ob_get_clean();

					$fres2 = fopen("restest2.txt", "w");
					fputs($fres2, $reswrite2);
					fclose($fres2);
				}


			chdir("..");

			}catch(Exception $e){
				$ferr = fopen("resterr.txt", "a+");
				$msg = $e->getMessage();
				//echo "ERROR!=".$msg;
				fputs($ferr, $msg);
				fclose($ferr);
				chdir("..");

			}

	}
	chdir("..");
}








