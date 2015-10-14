<?php
/**
 * Created by PhpStorm.
 * User: Вадим
 * Date: 22.01.2015
 * Time: 16:17
 */
function crop($file_input, $file_output, $crop = 'square',$percent = false) {
	list($w_i, $h_i, $type) = getimagesize($file_input);
	if (!$w_i || !$h_i) {
		echo 'Невозможно получить длину и ширину изображения';
		return;
	}
	$types = array('','gif','jpeg','png');
	$ext = $types[$type];
	if ($ext) {
		$func = 'imagecreatefrom'.$ext;
		$img = $func($file_input);
	} else {
		echo 'Некорректный формат файла';
		return;
	}
	if ($crop == 'square') {
		$min = $w_i;
		if ($w_i > $h_i) $min = $h_i;
		$w_o = $h_o = $min;
	} else {
		list($x_o, $y_o, $w_o, $h_o) = $crop;
		if ($percent) {
			$w_o *= $w_i / 100;
			$h_o *= $h_i / 100;
			$x_o *= $w_i / 100;
			$y_o *= $h_i / 100;
		}
		if ($w_o < 0) $w_o += $w_i;
		$w_o -= $x_o;
		if ($h_o < 0) $h_o += $h_i;
		$h_o -= $y_o;
	}
	$img_o = imagecreatetruecolor($w_o, $h_o);
	imagecopy($img_o, $img, 0, 0, $x_o, $y_o, $w_o, $h_o);
	if ($type == 2) {
		return imagejpeg($img_o,$file_output,100);
	} else {
		$func = 'image'.$ext;
		return $func($img_o,$file_output);
	}
}


class Point{
	private $x,$y;
	private $trackId = array(
		"Apex"=>array(),
	    "Kalesa"=>array()
	);
	private $fit;//относительный путь к файлу *.fit
	private $type;
	private $mag;
	private $pathcrop;
	private $astlenpx;
	public $fake,$starfactor,$rafactor;
	public $trackparams;



	public function __construct($trid,$m,$type,$tp=array()){
		$this->SetTrackId($type,$trid);
		$this->SetFitId($m);
		$this->x=$m['x'];
		$this->y=$m['y'];
		$this->mag = $m['mag'];
		$this->astlenpx = $m['astlenpx'];
		$this->fake = $m['fake'];
		$this->starfactor = $m['starfactor'];
		$this->rafactor = $m['rafactor'];
		$this->trackparams=$tp;
		$this->makeCrop($this->x,$this->y,$this->fit);


		$this->type=$type;

	}

	public function makeCrop($x,$y,$fit)
	{
		$w =40;
		$h =40;
		$x1=$x-$w/2;
		$y1=$y-$h/2;
		$x2= $x + $w/2;
		$y2=$y+$h/2;
		$fitar = explode(".",$fit);
		$sq = $fitar[0];
		$pos = strpos($fit,"T");
		$st = (int)($pos-8);
		$date = substr($fit,$st,8);
		$t = getcwd();
		$srcpath ="$t/images/$fit/src.jpeg";
		$respath = "$t/images/$fit/$x-$y.jpeg";
		//echo "SRCPATH = $srcpath\n";

		crop($srcpath,$respath,array($x1,$y1,$x2,$y2),false);
	}

	public function  PrintPoint(){
		/*echo "-----\n";
		echo $this->x." ".$this->y."\n".$this->fit."\n";

		print_r($this->trackId);*/
		$x = $this->x;
		$y = $this->y;
		$fit = $this->fit;
		$apex = $this->trackId['Apex'][0];
		$kalesa = $this->trackId['Kalesa'][0];
		$k2 = $this->trackId['Kalesa'][1];
		printf('[%10s] (%5d) (%5d) [%10s] [%-10s,%-10s]',$fit,$x,$y,$apex,$kalesa,$k2);
		echo "\n";
	}
	public function GetX(){
		return $this->x;
	}

	public function GetY(){
		return $this->y;
	}

	public function GetMag(){
		return $this->mag;
	}

	public function GetFitId(){
		return $this->fit;
	}

	public function GetAstlenpx(){
		return $this->astlenpx;
	}

	private function SetFitId($m){
		$p = $m['fitpath'];
		$pathar = explode('/',$p);
		$this->fit = trim(array_pop($pathar));
	}

	public function SetTrackId($typeid,$trid){
		//if(!in_array($trid,$this->trackId["$typeid"])){
		$this->trackId["$typeid"][]=$trid;//}
		return;

	}

	public function SetUniqueTrackId($typeid,$trid){
		//if(!in_array($trid,$this->trackId["$typeid"])){
		$a = array();
		$a[0]=$trid;
		$this->trackId["$typeid"]=$a;//}

		return;

	}

	public function  GetTrackId($typeid){
		if (isset($this->trackId[$typeid][0])){
			$res='';
			foreach($this->trackId[$typeid] as $trid){
				$res="$res"."$trid ";

			}
		//return $this->trackId["$typeid"][0];
			return $res;
		}else{
			return false;
		}


	}

	public function GetTrackIdArray($typeid){
		return $this->trackId["$typeid"];

	}

	public function  GetUniqueTrackId($typeid){
		return $this->trackId["$typeid"][0];


	}

	public function cmp_obj($a,$b){
		if ($a==$b){
			return 0;
		}
		return ($a->GetX() > $b->GetX())?+1:-1;

	}
}

class FinPoint{
	public $apex;
	public $kalesa;
	public $fit;//относительный путь к файлу *.fit

	public function SetApex($a){
		$this->apex=$a;
		$this->fit = $a->GetFitId();
	}

	public function SetKalesa($k)
	{
		$this->kalesa=$k;
		$this->fit = $k->GetFitId();

	}

	public function GetApexId(){
		return $this->apex->GetTrackId('Apex');
	}

	public function GetKalesaId(){
		return $this->kalesa->GetTrackIdArray('Kalesa');

	}

	public function  PrintPoint(){

		if (isset($this->apex)){
			$xapex = $this->apex->GetX();
			$yapex = $this->apex->GetY();
			$idApex = $this->apex->GetTrackId('Apex');
		}else{
			$xapex = -1;
			$yapex = -1;
			$idApex ='';
		}
		if(isset($this->kalesa)){
			$xkalesa = $this->kalesa->GetX();
			$ykalesa = $this->kalesa->GetY();
			//$idKalesa = $this->kalesa->GetTrackId('Kalesa');
			$idKalesa = $this->kalesa->GetUniqueTrackId('Kalesa');
		}else{
			$xkalesa = -1;
			$ykalesa = -1;
			$idKalesa = '';
			$k2 = '';


		}
		$fit = $this->fit;


		printf('[%10s] (%5d;%5d)[%10s] | (%5d;%5d)[%-10s]',$fit,$xapex,$yapex,$idApex,$xkalesa,$ykalesa,$idKalesa);
		echo "\n";
	}

	public function  PrintPoint1($upimages){

		$isResidual = 0;
		$astlenpx ="";
		if (isset($this->apex)){
			$xa = $this->apex->GetX();
			$ya = $this->apex->GetY();
			$x = $xa;
			$y = $ya;
			$mag = $this->apex->GetMag();
			//$idApex = "+";
			$idApex = $this->apex->GetTrackId('Apex');
			$astlenpx = $this->apex->GetAstlenpx();
			$fake = $this->apex->fake;
			$starfactor = $this->apex->starfactor;
			$rafactor = $this->apex->rafactor;
			$isResidual+=1;

		}else{

			$idApex ="-";

		}
		if(isset($this->kalesa)){
			$xk = $this->kalesa->GetX();
			$yk = $this->kalesa->GetY();
			$x = $xk;
			$y = $yk;
			$mag = $this->kalesa->GetMag();
			//$idKalesa = $this->kalesa->GetTrackId('Kalesa');
			//$idKalesa = "+";
			$idKalesa = $this->kalesa->GetUniqueTrackId('Kalesa');
			$isResidual+=1;

		}else{

			$idKalesa = '-';

		}
		if ($isResidual == 2)
		{
			$residual = sqrt(pow(($xa-$xk),2)+pow(($ya-$yk),2));
		}
		else
		{
			$residual = "";
		}
		$fit = $this->fit;
		$crop = $upimages."\\images\\$fit\\$x-$y.jpeg";



		printf('%50s %5d;%-5d %5.2f %5.2f  %10s  %50s  %200s %2d %3.1f %2.1f %2.1f',$fit,$x,$y,$residual,$mag,$idApex,$idKalesa,$crop,$fake,$astlenpx,$starfactor,$rafactor);
		echo "\n";
	}

	public function  PrintPoint2($ca,$ck,$arpath){


		$trackparams = array();
		if (isset($this->apex)){
			$idApex = $this->apex->GetTrackId('Apex');
			$trackparams = $this->apex->trackparams;
		}else{
			$idApex ='-';
			$trackparams = $this->kalesa->trackparams;
		}
		if(isset($this->kalesa)){
			//$idKalesa = $this->kalesa->GetTrackId('Kalesa');
			$idKalesa = $this->kalesa->GetUniqueTrackId('Kalesa');
		}else{

			$idKalesa = '-';



		}

		$res = imagecreate(200,40);
		$jp0 = imagecreatefromjpeg($arpath[0]);
		$jp1 = imagecreatefromjpeg($arpath[1]);
		$jp2 = imagecreatefromjpeg($arpath[2]);
		$jp3 = imagecreatefromjpeg($arpath[3]);
		$jp4 = imagecreatefromjpeg($arpath[4]);
		imagecopymerge($res,$jp0,0,0,0,0,40,40,100);
		imagecopymerge($res,$jp1,40,0,0,0,40,40,100);
		imagecopymerge($res,$jp2,80,0,0,0,40,40,100);
		imagecopymerge($res,$jp3,120,0,0,0,40,40,100);
		imagecopymerge($res,$jp4,160,0,0,0,40,40,100);
//$combined->writeImage("resImagick.jpeg");
		imagejpeg($res,"tmp.jpeg");
		$data = file_get_contents("tmp.jpeg");

		$fit = $this->fit;
		$pos = strpos($fit,"T");
		$fit = substr($fit,0,$pos);
		//$p = $arpath[0];
		$arrayForJSON = array(
			"ApexId"=>$idApex,
		    "KalesaId"=>$idKalesa,
		    "NumberOfApex"=>$ca,
		    "NumberOfKalesa"=>$ck,
		    "Track"=>$fit,
			"crops"=>base64_encode($data)


		);
		$arrayForJSON+=$trackparams;

		//unlink("tmp.jpeg");

		$JSONout = json_encode($arrayForJSON);
		print ($JSONout);
		///СТАРЫЙ ВЫВОД
		/*printf('%-10s  %50s %3d %3d %30s %20s %20s %20s %20s %20s',$idApex,$idKalesa,$ca,$ck,$fit,$arpath[0],$arpath[1],$arpath[2],$arpath[3],$arpath[4]);
		foreach ($trackparams as $v)
		{
			print(" $v ");
		}
		echo "\n";*/
	}

	/*public function cmp_obj($a,$b){
		if ($a==$b){
			return 0;
		}
		return ($a->GetX() > $b->GetX())?+1:-1;

	}*/
}
