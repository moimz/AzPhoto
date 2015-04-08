<?php
REQUIRE_ONCE '../configs/default.conf.php';

$db = new db();
$get = Request('get');

if ($get == 'map') {
	$longitude = Request('longitude');
	$latitude = Request('latitude');
	
	if ($longitude['start'] < $longitude['end']) {
		$find = 'where `longitude`>='.$db->antiInjection($longitude['start']).' && `longitude`<='.$db->antiInjection($longitude['end']);
		$longitudeWidth = $longitude['end'] - $longitude['start'];
	} else {
		$find = 'where ((`longitude`>='.$db->antiInjection($longitude['start']).' && `longitude`<=360) or (`longitude`>=0 and `longitude`<'.$db->antiInjection($longitude['end']).'))';
		$longitudeWidth = 360 - $longitude['start'] + $longitude['end'];
	}
	$find.= ' and `latitude`<='.$db->antiInjection($latitude['start']).' and `latitude`>='.$db->antiInjection($latitude['end']);
	
	$photos = $db->getRows($_ENV['tables']['photos'],$find.' order by `time` asc');
	for ($i=0, $loop=sizeof($photos);$i<$loop;$i++) {
		$photos[$i]['longitude'] = $photos[$i]['longitude'] - 180;
		$photos[$i]['latitude'] = $photos[$i]['latitude'] - 90;
		$photos[$i]['time'] = date('Y년 m월 d일 H:i:s',$photos[$i]['time']);
		$photos[$i]['exif'] = json_decode(base64_decode($photos[$i]['exif']),true);
	}
}

if ($get == 'calendar') {
	$start = Request('start')/1000;
	$end = Request('end')/1000;
	$find = "where `time`>=".$db->antiInjection($start)." and `time`<".$db->antiInjection($end);
	$photos = $db->getRows($_ENV['tables']['photos'],$find.' order by `time` asc');
	for ($i=0, $loop=sizeof($photos);$i<$loop;$i++) {
		$photos[$i]['date'] = date('Y-m-d',$photos[$i]['time']);
		$photos[$i]['longitude'] = $photos[$i]['longitude'] - 180;
		$photos[$i]['latitude'] = $photos[$i]['latitude'] - 90;
		$photos[$i]['time'] = date('Y년 m월 d일 H:i:s',$photos[$i]['time']);
		$photos[$i]['exif'] = json_decode(base64_decode($photos[$i]['exif']),true);
	}
}

exit(json_encode(array('success'=>true,'photos'=>$photos)));
?>