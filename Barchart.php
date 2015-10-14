<?php
/**
 * Created by PhpStorm.
 * User: Вадим
 * Date: 12.08.2015
 * Time: 19:02
 */

function BinarySearchPos($x,$a)//функции ищет позицию элемента x в отсортированном массиве a
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
		if($x<=$a[$mid]){
			$last=$mid;
		}else{
			$first = $mid+1;
		}

	}
	return $last;

}

/*
 * класс гистограмм
 */
class Barchart{
	private $filename;
	private $dataArray;//массив исходных данных, по которым стоится гистограмма
	private $intervalArray;//интервал сортировки данных
	private $countArray;
	private $percentArray;
	private $names;

	public function _construct(){
		//$this->GetDataArray($filename);
		//$this->SetIntervalArray($intervals);

	}

	/*
	 * функция извлекает поле блеска из общего файла и добавляет в соответсвующий массив (Апекс,Колесса)
	 */
	public function GetDataArray($filename){

		if ($lines=file($filename)){
			foreach ($lines as $line){
				$cells =  preg_split("/[\s]+/",$line);
				$countcells = count($cells);
				if ($cells[0]!="-" ){

					if($cells[1]=="-"){

						array_reverse($cells);
						$value = $cells[19];
						$this->dataArray["Apex"]["only"][] = $value;
					}
					else{

						array_reverse($cells);
						$value = $cells[19];
						$this->dataArray["Apex"]["tog"][] = $value;
						$this->dataArray["Kalesa"]["tog"][] = $value;
					}

				}
				else{

					$value = $cells[10];
					$this->dataArray["Kalesa"]["only"][] = $value;
				}
			}
		}


	}

	/*
	 * Задать интервал разбиения данных
	 */
	public function SetIntervalArray($ar){
		$this->intervalArray = $ar;

	}

	private function PrepareData($mode){

		$arrayforcount = array();
		if ($mode != "sum"){
			$unmode = $this->Unmode($mode);
			$arrayforcount[$mode] = array_merge($this->dataArray[$mode]["only"],$this->dataArray[$mode]["tog"]);
			$arrayforcount[$unmode] = $this->dataArray[$unmode]["tog"];
		}
		else{

			$arrayforcount["Apex"] = array_merge($this->dataArray["Apex"]["only"],$this->dataArray["Apex"]["tog"]);
			$arrayforcount["Kalesa"] = array_merge($this->dataArray["Kalesa"]["only"], $this->dataArray["Kalesa"]["tog"]);

		}
		return $arrayforcount;
	}

	private function Unmode($mode){
		if ($mode == "Apex"){
			return "Kalesa";
		}
		if ($mode == "Kalesa")
		{
			return "Apex";
		}
		else throw new Exception("неверный режим");
	}


	/*
	 * Рассчитать распределение дынных по заданным интервалам
	 */
	public function CountIntervals($mode){

		$arforcount = $this->PrepareData($mode);
		$size = count($this->intervalArray);
		$this->countArray['sum']=array_fill(0,$size+1,0);
		foreach ($arforcount as $serie => $arvalue){
			$this->countArray[$serie] = array_fill(0,$size+1,0);

			foreach($arvalue as $value){

				$a = BinarySearchPos($value, $this->intervalArray);
				$this->countArray[$serie][$a] += 1;
				$this->countArray['sum'][$a]+=1;
			}

		}

	}


	public function CalculateData($mode)
	{

		$size = count($this->countArray["Apex"]);

		if ($mode != "sum"){
			$unmode = $this->Unmode($mode);
			for($i = 0; $i < $size; $i++){
				if($this->countArray['sum'][$i] != 0){
					$this->percentArray[$mode][$i] = (int)(($this->countArray[$mode][$i] / $this->countArray[$mode][$i]) * 100);
					$this->percentArray[$unmode][$i] = (int)(($this->countArray[$unmode][$i] / $this->countArray[$mode][$i]) * 100);

				} else{
					$this->percentArray['Apex'][$i] = 0;
					$this->percentArray['Kalesa'][$i] = 0;

				}
			}
		}
		else{
			for($i = 0; $i < $size; $i++){
				if($this->countArray['sum'][$i] != 0){
					$this->percentArray["Apex"][$i] = (int)(($this->countArray["Apex"][$i] / $this->countArray["sum"][$i]) * 100);
					$this->percentArray["Kalesa"][$i] = (int)(($this->countArray["Kalesa"][$i] / $this->countArray["sum"][$i]) * 100);

				} else{
					$this->percentArray['Apex'][$i] = 0;
					$this->percentArray['Kalesa'][$i] = 0;

				}
			}
		}

	}





	public function MakeNames(){

		$size = count($this->intervalArray);
		$this->names[0]="'"."<".$this->intervalArray[0]."'";

		for ($i=0;$i!=$size-1;$i++){
			$this->names[$i+1]= "'".$this->intervalArray[$i]."-".$this->intervalArray[$i+1]."'";
		}

		$this->names[$size]="'".">".$this->intervalArray[$size-1]."'";

	}

	public function MakeCfg($outfile){

		$categories = implode (",",$this->names);
		$kalesadata = implode(",",$this->percentArray['Kalesa']);
		$apexdata = implode(",",$this->percentArray['Apex']);
		$kalesacount = implode(",",$this->countArray['Kalesa']);
		$apexcount = implode(",",$this->countArray['Apex']);

		ob_start();
		print <<<END
{

chart: {
type: 'column'
},
title: {
text: 'Сравнение Апекс-Колесса'
},
subtitle: {
text: 'Сравнение по блеску'
},
xAxis: {
categories: [ $categories
],
crosshair: true
},
yAxis: {
min: 0,
title: {
text: 'Количество проводок,%'
}
},
tooltip: {
headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
	pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
		'<td style="padding:0"><b>{point.y:.1f} mm</b></td></tr>',
	footerFormat: '</table>',
shared: true,
useHTML: true
},
plotOptions: {
	column: {
		pointPadding: 0.2,
		borderWidth: 0,
		dataLabels: {
                    enabled: true,
                    formatter: function(){
                    var kalesacount = [$kalesacount];
                    var apexcount = [$apexcount];
                    if (this.point.series.name =='Колесса'){
                         return kalesacount[this.point.x];}
                    if (this.point.series.name =='Апекс'){
                        return apexcount[this.point.x];}
                    }
                }
	}



},
series: [{
name: 'Колесса',
data: [$kalesadata],
y:"2"

}, {
name: 'Апекс',
data: [$apexdata],
y:3

}]
}
END;
		$reswrite = ob_get_clean();

		$fres = fopen($outfile, "w");
		fputs($fres, $reswrite);
		fclose($fres);
	}

	public function MakeBarchart(){

		foreach (func_get_args() as $mode)
		{
			$this->CountIntervals($mode);
			$this->CalculateData($mode);
			$this->MakeNames();
			$this->MakeCfg("cfg-$mode");
		}
	}


}