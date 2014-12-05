<?php
REQUIRE_ONCE '../configs/default.conf.php';

$db = new db();
$longitude = Request('longitude');
$latitude = Request('latitude');
$area = Request('area');

$widthCount = round($area['width']/15);
$heightCount = round($area['height']/30);
$longitude['start'] = $longitude['start'] + 180;
$longitude['end'] = $longitude['end'] + 180;
$latitude['start'] = $latitude['start'] + 90;
$latitude['end'] = $latitude['end'] + 90;

$find = '';

if ($longitude['start'] < $longitude['end']) {
	$find = 'where `longitude`>='.$longitude['start'].' && `longitude`<='.$longitude['end'];
	$longitudeWidth = $longitude['end'] - $longitude['start'];
} else {
	$find = 'where ((`longitude`>='.$longitude['start'].' && `longitude`<=360) or (`longitude`>=0 and `longitude`<'.$longitude['end'].'))';
	$longitudeWidth = 360 - $longitude['start'] + $longitude['end'];
}
$find.= ' and `latitude`<='.$latitude['start'].' and `latitude`>='.$latitude['end'];
$latitudeHeight = $latitude['start'] - $latitude['end'];
$longitudeAreaWidth = $longitudeWidth/$widthCount;
$latitudeAreaHeight = $latitudeHeight/$heightCount;

$pins = array();
$pinsArea = array();

$photos = $db->getRows($_ENV['tables']['photos'],$find.' order by `latitude` asc');
for ($i=0;$i<$widthCount-1;$i++) {
	$pinsArea[$i] = array();
	for ($j=0;$j<$heightCount-1;$j++) {
		$pinsArea[$i][$j] = array(
			'longitude'=>array('start'=>($longitude['start']+$longitudeAreaWidth*$i) > 360 ? ($longitude['start']+$longitudeAreaWidth*$i) - 360 : ($longitude['start']+$longitudeAreaWidth*$i),'end'=>($longitude['start']+$longitudeAreaWidth*($i+1)) > 360 ? ($longitude['start']+$longitudeAreaWidth*($i+1)) - 360 : ($longitude['start']+$longitudeAreaWidth*($i+1))),
			'latitude'=>array('start'=>$latitude['start']-$latitudeAreaHeight*$j,'end'=>$latitude['start']-$latitudeAreaHeight*($j+1)),
			'photos'=>0,
			'pin'=>null,
			'pinDistance'=>0,
			'flag'=>false
		);
	}
}

for ($i=0, $loop=sizeof($photos);$i<$loop;$i++) {
	if ($longitude['start'] < $photos[$i]['longitude']) $indexLongitude = floor(($photos[$i]['longitude'] - $longitude['start']) / $longitudeAreaWidth);
	else $indexLongitude = floor(($photos[$i]['longitude'] + 360 - $longitude['start']) / $longitudeAreaWidth);
	$indexLatitude = floor(($latitude['start'] - $photos[$i]['latitude']) / $latitudeAreaHeight);
	
	$centerX = ($pinsArea[$indexLongitude][$indexLatitude]['longitude']['start'] + $pinsArea[$indexLongitude][$indexLatitude]['longitude']['end']) / 2;
	$centerY = ($pinsArea[$indexLongitude][$indexLatitude]['latitude']['start'] + $pinsArea[$indexLongitude][$indexLatitude]['latitude']['end']) / 2;
	$pinDistance = pow($centerX - $photos[$i]['longitude'],2) + pow($centerY - $photos[$i]['latitude'],2);
	
	if ($pinsArea[$indexLongitude][$indexLatitude]['flag'] == false) {
		$pinsArea[$indexLongitude][$indexLatitude]['flag'] = true;
		$pinsArea[$indexLongitude][$indexLatitude]['pin'] = array('longitude'=>$photos[$i]['longitude']-180,'latitude'=>$photos[$i]['latitude']-90);
		$pinsArea[$indexLongitude][$indexLatitude]['pinDistance'] = $pinDistance;
	} elseif ($pinsArea[$indexLongitude][$indexLatitude]['pinDistance'] > $pinDistance) {
		$pinsArea[$indexLongitude][$indexLatitude]['pin'] = array('longitude'=>$photos[$i]['longitude']-180,'latitude'=>$photos[$i]['latitude']-90);
		$pinsArea[$indexLongitude][$indexLatitude]['pinDistance'] = $pinDistance;
	}
	$pinsArea[$indexLongitude][$indexLatitude]['photos']++;
}

for ($i=0;$i<$widthCount-1;$i++) {
	for ($j=0;$j<$heightCount-1;$j++) {
		$pinArea = $pinsArea[$i][$j];
		if ($pinArea['flag'] == true) {
			$pin = $pinArea['pin'];
			if (isset($pinsArea[$i+1][$j]) == true && $pinsArea[$i+1][$j]['flag'] == true) {
				$distance = $pinsArea[$i+1][$j]['pin']['longitude'] - $pin['longitude'];
				
				if ($distance < $longitudeAreaWidth) {
					$pinArea['longitude']['end'] = $pinsArea[$i+1][$j]['longitude']['end'];
					$pinArea['photos']+= $pinsArea[$i+1][$j]['photos'];
					$pinsArea[$i+1][$j]['flag'] = false;
				}
			}
			
			if (isset($pinsArea[$i][$j+1]) == true && $pinsArea[$i][$j+1]['flag'] == true) {
				$distance = $pin['latitude'] - $pinsArea[$i][$j+1]['pin']['latitude'];
				
				if ($distance < $latitudeAreaHeight) {
					$pinArea['latitude']['end'] = $pinsArea[$i][$j+1]['latitude']['end'];
					$pinArea['photos']+= $pinsArea[$i][$j+1]['photos'];
					$pinsArea[$i][$j+1]['flag'] = false;
				}
			}
			
			if (isset($pinsArea[$i+1][$j+1]) == true && $pinsArea[$i+1][$j+1]['flag'] == true) {
				$distance = pow(pow($pinsArea[$i+1][$j+1]['pin']['longitude'] - $pin['longitude'],2) + pow($pinsArea[$i+1][$j+1]['pin']['latitude'] - $pin['latitude'],2),0.5);
				
				if ($distance < pow(pow($longitudeAreaWidth,2)+pow($latitudeAreaHeight,2),0.5)) {
					$pinArea['longitude']['end'] = $pinsArea[$i+1][$j+1]['longitude']['end'];
					$pinArea['latitude']['end'] = $pinsArea[$i+1][$j+1]['latitude']['end'];
					$pinArea['photos']+= $pinsArea[$i+1][$j+1]['photos'];
					$pinsArea[$i+1][$j+1]['flag'] = false;
				}
			}
			
			unset($pinArea['pin'],$pinArea['pinDistance'],$pinArea['flag']);
			$pin['x'] = $i;
			$pin['y'] = $j;
			$pin['area'] = $pinArea;
			$pins[] = $pin;
		}
	}
}

exit(json_encode(array('success'=>true,'pins'=>$pins)));
?>