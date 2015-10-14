<?php
/**
 * Created by PhpStorm.
 * User: Вадим
 * Date: 02.02.2015
 * Time: 20:02
 */
require_once('config.php');
require_once('class_xml_parser.php');
require_once('Track.php');

/*
 * абстрактный класс чтения массива треков из файлов
 */
function cmp($a, $b) {
	if ($a == $b) {
		return 0;
	}
	return ($a < $b) ? -1 : 1;
}

function getMedian($ar){
	sort($ar);
	$size = count($ar);
	$med = $size/2;
	$medint = (int)$med;
	if ($med === $medint){
		return $ar[$medint];
	}
	else{
		return ($ar[$medint]+$ar[$medint+1])/2;
	}
}
abstract class Reader {
	public  $path='';
	public $filepathArray=array();
	public $arrayTrack=array();
	public $trackCount = array();

	/*public function __construct($p){
		$this->path=$p;

	}*/
	abstract protected function readTrack($filepath);
	abstract protected function readTrackArray();
	abstract protected function findTrackFiles();


}

/*
 * класс чтения массива треков для APEX
 */
class ReaderApex extends Reader {

	private $arrayfits;
	public function __construct($p){
		$this->path=$p;

	}
	protected function readTrack($filepath)
	{
		$xml = new cls_xml_parser();
		try{
			$array = $xml->loadFile($filepath, false, "ROOT");


			if(!isset($array['ROOT']['tracks']['track'])){
				return;
			}
			$tracks = $array['ROOT']['tracks']['track'];

			if(isset($tracks['meas'])){
				$tracks = $array['ROOT']['tracks'];
			}

		if (!is_array($tracks))
		{
			//throw new Exception("track not array:".realpath($filepath)."");
			return;
		}
			foreach($tracks as $track){

				$trackId = $track['trackid'];
				$astlenpx = $track['astlenpx'];
				$fake = $track['fake'];
				$starfactor = $track['starfactor'];
				$rafactor = $track['rafactor'];
				$trackparams = array();
				foreach ($track as $keyparam=>$vparam)
				{
					if ($keyparam != "meas")
					{
						$trackparams[$keyparam]=$vparam;

					}
				}
				if(!is_array($track['meas'][0]))
				{return;}
				////////////////
				/*$s = realpath($filepath);
				$d=dirname($s);
				$te = fopen($d."/resmeas.txt","a+");
				ob_start();
				print_r($track);
				$r1 = ob_get_clean();
				fputs($te, $r1);
				fclose($te);*/
				////////////////////////
				if(array_key_exists("$trackId", $this->arrayTrack)
				&&
				count($track['meas']) < count($this->arrayTrack["$trackId"])
				){
				break;
				}else{
					$this->trackCount[$trackId]=0;
					$this->arrayTrack["$trackId"]=array();
					$this->arrayfits["$trackId"] = array();
					foreach($track['meas'] as $meas){

						//$this->arrayTrack["$trackId"][] = array('x' => $meas['x'], 'y' => $meas['y'], 'fitpath' => $meas['filename']);
						if (!in_array($meas['filename'],$this->arrayfits["$trackId"])){
							$this->arrayTrack["$trackId"][] = new Point($trackId, array('x' => $meas['x'], 'y' => $meas['y'], 'fitpath' => $meas['filename'],'mag'=>$meas['mag'],'fake'=>$fake,'astlenpx'=>$astlenpx,'starfactor'=>$starfactor,'rafactor'=>$rafactor), 'Apex',$trackparams);
							$this->arrayfits["$trackId"][]=$meas['filename'];
							$this->trackCount[$trackId]+=1;
						}

					}
				}

			}


		return;
		}catch(Exception $e){
			echo "ERROR\n";
			echo realpath($filepath);
			$f=fopen("resterr.txt","a+");
			fputs($f,$e->getMessage());
			fclose($f);
			throw new Exception("no simpledat");
		}
	}

	private function remakeArrayTrack()
	{
		$c = $this->arrayTrack;
		$this->arrayTrack=array();
		foreach ($c as $t){
			foreach ($t as $m)
			{
				$this->arrayTrack[]=$m;
			}
		}
	}
	public function readTrackArray(){
		if ($this->findTrackFiles()){
			foreach($this->filepathArray as $filetrack){

				$this->readTrack($filetrack);

			}
			$this->remakeArrayTrack();
			return true;
		}
		return false;

	}

	protected function findTrackFiles(){
		//chdir($this->path);
		$this->filepathArray = glob('R[0-9]*.xml',GLOB_NOSORT);
		if (count($this->filepathArray)==0){return false;}
		print_r($this->filepathArray);
		return true;



	}
}

/*
 * класс чтения массива треков для KALESA
 */
class ReaderKalesa extends Reader {

	private $uniqPoints = array();

	public function __construct($p){
		$this->path=$p;
	}

	//считать трек из файла
	protected function readTrack($filepath){
		$arstr = file($filepath);
		$this->trackCount[$filepath]=0;
		$armag=array();
		for ($i=2;$i<count($arstr);$i++)
		{
			$armeas = $arstr[$i];
			$meas = explode(" ",$armeas);
			$del = 13 - count($meas);
			$mag = (double)$meas[11-$del];
			$armag[]=$mag;


		}
		$trackparams=array();
		$trackparams['mag']=getMedian($armag);

		for ($i=2;$i<count($arstr);$i++)
		{
			$armeas = $arstr[$i];
			$trackId = $filepath;
			$this->trackCount[$filepath]+=1;
			$meas = explode(" ",$armeas);
			$del = 13 - count($meas);//поправка, если в track калесы значения с минусами
			$x=(double)$meas[7-$del];
			$y=3056 -(double)$meas[9-$del];
			$mag = (double)$meas[11-$del];
			$fitpath = array_pop($meas);
			//$this->arrayTrack["$trackId"][]=array('x'=>$x,'y'=>$y,'fitpath'=>$fitpath);
			$p = array('x'=>$x,'y'=>$y,'fitpath'=>$fitpath);
			if (!(in_array($p,$this->uniqPoints))) {
				$this->arrayTrack[] = new Point($trackId, array('x' => $x, 'y' => $y, 'fitpath' => $fitpath,'mag'=>$mag), 'Kalesa',$trackparams);
				array_push($this->uniqPoints,$p);

			}else
			{
				$key = array_search($p,$this->uniqPoints);
				//$m = $this->arrayTrack[$key];
				//$m->SetTrackId('Kalesa',$trackId);
				$this->arrayTrack[$key]->SetTrackId('Kalesa',$trackId);
				//echo "KEY = ".$m->GetTrackId('Kalesa')."\n";

			}
		}
		return;
	}
	//формируем массив треков
	public function readTrackArray(){
		if ($this->findTrackFiles()){

			foreach($this->filepathArray as $filetrack){
				$this->readTrack($filetrack);
			}
			return true;
		}
		return false;
	}
	//находим все файлы в директории
	protected function findTrackFiles(){
		chdir($this->path);
		$this->filepathArray = glob('*simple*.dat',GLOB_NOSORT);
		if (count($this->filepathArray)==0){return false;}
		return true;
	}
}

/*
 * класс сравнивающий треки от APEX и KALESA
 */
class Comparator{
	public $arrayApex,$measApex;
	public $arrayKalesa,$measKalesa;
	public $delta;
	public $finishArray;
	public $finArrayApex;
	public $finArrayKalesa;

	public $arapexid;
	public $arkalesaid;

	public $pathimages;

	private $filterTrack;
	private $untrack;


	public function __construct($arApex,$arKalesa,$d,$pimages){
		$this->arrayApex=$arApex;
		$this->arrayKalesa=$arKalesa;
		/*$this->FillArray($this->measApex,$this->arrayApex,'Apex');
		$this->FillArray($this->measKalesa,$this->arrayKalesa,'Kalesa');*/
		$this->measApex = $arApex->arrayTrack;
		$this->measKalesa = $arKalesa->arrayTrack;
		$this->delta=$d;
		$this->pathimages = $pimages;



	}

	public function PrintApex(){
		foreach($this->finArrayApex as $ma)
		{
			$ma->printPoint();

		}
	}

	public function PrintKalesa(){
		foreach($this->finArrayKalesa as $ma)
		{
			$ma->printPoint();

		}

	}

	public function PrintFullArray(){
		foreach($this->finishArray as $m)
		{
			$m->printPoint();
			//echo count($this->finishArray);

		}

	}

	public function PrintApex1(){
		foreach($this->finArrayApex as $ma)
		{
			$ma->printPoint1($this->pathimages);

		}
	}

	public function PrintKalesa1(){
		foreach($this->finArrayKalesa as $ma)
		{
			$ma->printPoint1($this->pathimages);

		}

	}

	public function PrintFullArray1(){
		foreach($this->finishArray as $m)
		{
			$m->printPoint1($this->pathimages);
			//echo count($this->finishArray);

		}

	}

	public function PrintApex2(){
		$artrackcountapex = $this->arrayApex->trackCount;

		$aridpath = array();
		foreach($this->finArrayApex as $m)
		{
			$apid = (int)$m->apex->GetTrackId('Apex');
			$fitpath = $m->fit;
			$x = $m->apex->GetX();
			$y = $m->apex->GetY();
			$crop = $this->pathimages."/images/$fitpath/$x-$y.jpeg";
			$aridpath[$apid][] = $crop;

		}
		foreach($this->finArrayApex as $ma)
		{
			$apexid = (int)$ma->apex->GetTrackId('Apex');
			if (!in_array($apexid,$this->arapexid)){

				$capex = $artrackcountapex[$apexid];
				$ckalesa = 0;
				$this->arapexid[] = $apexid;
				if (count($aridpath[$apexid])>=5){
					$ma->printPoint2($capex, $ckalesa, $aridpath[$apexid]);
					print(",\n");
				}
			}

		}
	}

	public function PrintKalesa2(){
		$artrackcountkalesa = $this->arrayKalesa->trackCount;

		$aridpath = array();
		foreach($this->finArrayKalesa as $m)
		{
			$apid = $m->kalesa->GetUniqueTrackId('Kalesa');
			$fitpath = $m->fit;
			$x = $m->kalesa->GetX();
			$y = $m->kalesa->GetY();
			$crop = $this->pathimages."/images/$fitpath/$x-$y.jpeg";
			$aridpath[$apid][] = $crop;

		}

		foreach($this->finArrayKalesa as $ma)
		{
			$kalesaid = $ma->kalesa->GetUniqueTrackId('Kalesa');
			if (!in_array($kalesaid,$this->arkalesaid)){

				$capex = 0;
				$ckalesa = $artrackcountkalesa[$kalesaid];
				$this->arkalesaid[] = $kalesaid;
				if (count($aridpath[$kalesaid])>=5){
					$ma->printPoint2($capex, $ckalesa, $aridpath[$kalesaid]);
					print(",\n");
				}
			}


		}

	}

	public function PrintFullArray2(){
		$artrackcountapex = $this->arrayApex->trackCount;
		$artrackcountkalesa = $this->arrayKalesa->trackCount;

		$aridpath = array();
		foreach($this->finishArray as $m)
		{
			$apid = (int)$m->apex->GetTrackId('Apex');
			$fitpath = $m->fit;
			$x = $m->apex->GetX();
			$y = $m->apex->GetY();
			$crop = $this->pathimages."/images/$fitpath/$x-$y.jpeg";
			$aridpath[$apid][] = $crop;

		}

		foreach($this->finishArray as $m)
		{
			$apexid = (int)$m->apex->GetTrackId('Apex');
			$kalesaid = $m->kalesa->GetUniqueTrackId('Kalesa');
			if ( !in_array($apexid,$this->arapexid) && count($aridpath[$apexid])>=5){

				//$capex = 1;
				$capex = $artrackcountapex[$apexid];

				$ckalesa = $artrackcountkalesa[$kalesaid];
				$this->arapexid[] = $apexid;
				$this->arkalesaid[] = $kalesaid;
				$m->printPoint2($capex, $ckalesa, $aridpath[$apexid]);
				print(",\n");

			}
			//echo count($this->finishArray);

		}

	}
	//формирует массив из объектов Point
	private function FillArray(&$arempty,&$arfull,$type){
		foreach($arfull as $key=>$track)
		{
			$trackId = $key;
			foreach ($track as $meas)
			{
				$p = new Point($trackId,$meas,$type);

				$arempty[]=$p;
			}
		}
		//print($arempty[0]->GetX());

	}

	private function SetUniqueKalesaId(){
		$a = array();
		$sortbadkalesaid = array();
		foreach ($this->finishArray as $pointAK){
			$apexId = $pointAK->GetApexId();
			//echo "APEXID = "."$apexId"."\n";
			$arKalesaId = $pointAK->GetKalesaId();
			//print_r($arKalesaId);
			$sortbadkalesaid["$apexId"][]= $arKalesaId;

			foreach ($arKalesaId as $kid){
				$sortbadkalesaid[$kid][]=$apexId;
			}
			//$pointAK ->kalesa->SetTrackId('Kalesa','11');
		}

		foreach ($sortbadkalesaid as &$x)
		{
			$x=array_unique($x);
		}

		foreach ($this->finishArray as $pointAK){
			$apexId = $pointAK->GetApexId();
			//echo "APEXID = "."$apexId"."\n";
			$arKalesaId = $pointAK->GetKalesaId();
			//print_r($arKalesaId);
			$arKIdFilt = array();
			foreach ($arKalesaId as $x){
				if (count($sortbadkalesaid[$x])==1)
				{
					$arKIdFilt[]=$x;
				}
			}
			$a["$apexId"][]= $arKIdFilt;
			//$pointAK ->kalesa->SetTrackId('Kalesa','11');
		}
		//print_r($a);

		foreach ($a as $idAp=>$arK)
		{
			/*echo "TEST = ";
			print_r($arK);*/
			$countar = array();
			foreach ($arK as $arKalesaPoint){

				foreach ($arKalesaPoint as $k)
				{
					if (isset($countar[$k])){
						$countar[$k]+=1;

					}else{
						$countar[$k] = 1;

					}
				}

			}

			print_r($countar);
			uasort($countar,'cmp');
			if ( count($arK) == end($countar))
			{
				$finIdKalesa = end(array_keys($countar));
				foreach ($this->finishArray as $p)
				{
					if ($p->GetApexId() == $idAp){
					//$p->kalesa->trackId['Kalesa'] = $finIdKalesa;
					$p->kalesa->SetUniqueTrackId('Kalesa',$finIdKalesa);
					}

				}
			}else{
				$finIdKalesa = end(array_keys($countar));
				foreach ($this->finishArray as $p)
				{
					if ($p->GetApexId() == $idAp){
						$p->kalesa->SetUniqueTrackId('Kalesa',$finIdKalesa."-Error");
						//$p->kalesa->SetTrackId('Kalesa','Error');

					}

				}

			}

		}

	}

	public function Compare(){

		if (!is_array($this->measKalesa) && !is_array($this->measApex))
		{return false;}
		usort($this->measKalesa,array("Point","cmp_obj"));
		/*foreach ($this->measKalesa as $m)
		{
			print($m->GetX());
			print("\n");
		}*/
		foreach ($this->measApex as $m){
			$size = count($this->measApex);
			echo "SIZE MEASAPEX="."$size\n";
			if (!$this->FindSimilar($m,$this->measKalesa)){
				$fpapex = new FinPoint();
				$fpapex->SetApex($m);
				$this->finArrayApex[]=$fpapex;
			}
		}

		foreach ($this->measKalesa as $mk)
		{
			if ($mk->GetTrackId('Apex')===false)
			{
				$fpkalesa = new FinPoint();
				$fpkalesa->SetKalesa($mk);
				$this->finArrayKalesa[]=$fpkalesa;

			}
		}
		$this->SetUniqueKalesaId();


		foreach ($this->filterTrack as $k=>$arpointAK )
		{
			if (count($arpointAK) <5){

				foreach ($arpointAK as $pAK){
					$this->finArrayApex[] = $pAK;


				}



			}
		}
		return true;
	}

	//найти для заданного объекта Point схожий объект в массиве
	//присвоить значения Id в случае успеха
	private function FindSimilar($m,&$ar)
	{
		$x=$m->GetX();
		$y=$m->GetY();
		$fitid = $m->GetFitId();
		$xmin = $x-$this->delta;
		$xmax = $x+$this->delta;
		$xminPosition = $this->BinarySearchPos($xmin,$ar);
		$xmaxPosition = $this->BinarySearchPos($xmax,$ar);
		if ($xminPosition==$xmaxPosition){
			return false;
		}else{
			for ($i=$xminPosition;$i!=$xmaxPosition;$i++)
			{
				$mcur = &$ar[$i];
				$ycur = $mcur->GetY();
				$fitidcur = $mcur->GetFitId();
				if (abs($y-$ycur)<$this->delta && ($fitid==$fitidcur) ){
					echo "FitApex = $fitid;FitKalesa = $fitidcur"."\n";
					//$m->SetTrackId('Kalesa',$mcur->GetTrackId('Kalesa'));
					$mcur->SetTrackId('Apex',$m->GetTrackId('Apex'));
					$fp = new FinPoint();
					$fp->SetApex($m);
					$fp->SetKalesa($mcur);
					$this->finishArray[]=$fp;
					$this->filterTrack[$m->GetTrackId('Apex')][]= $fp;

					return true;
				}
			}
		}
	}

	private function BinarySearchPos($x,&$a)
	{
		$n=count($a);
		if($n==0){
			return 0;
		}
		$first=0;
		$last = $n;

		while ($first<$last)
		{
			$mid =(integer)( $first+($last-$first)/2);
			if($x<=$a[$mid]->GetX()){
				$last=$mid;
			}else{
				$first = $mid+1;
			}

		}
		return $last;

	}


}

class Tester{
	private $countTracks = array();
	private $countPoints = array();
	private $comparator;
}