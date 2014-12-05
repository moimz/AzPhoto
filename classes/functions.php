<?php
function Request($var,$type='request') {
	global $_REQUEST, $_SESSION;

	switch ($type) {
		case 'request' :
			$value = isset($_REQUEST[$var])==true ? (is_array($_REQUEST[$var]) == true ? $_REQUEST[$var] : $_REQUEST[$var]) : null;
		break;

		case 'session' :
			$value = isset($_SESSION[$var])==true ? $_SESSION[$var] : null;
		break;

		case 'cookie' :
			$value = isset($_COOKIE[$var])==true ? $_COOKIE[$var] : null;
		break;
	}

	if (is_array($value) == false) {
		$value = trim($value);
	}

	return $value;
}

function GetThumbnail($imgPath,$thumbPath,$width,$height,$delete=false) {
	$result = true;
	$imginfo = @getimagesize($imgPath);
	$extName = $imginfo[2];

	switch($extName) {
		case '2' :
			$src = @ImageCreateFromJPEG($imgPath) or $result = false;
			$type = 'jpg';
			break;
		case '1' :
			$src = @ImageCreateFromGIF($imgPath) or $result = false;
			$type = 'gif';
			break;
		case '3' :
			$src = @ImageCreateFromPNG($imgPath) or $result = false;
			$type = 'png';
			break;
		default :
			$result = false;
	}

	if ($result == true) {
		if ($width == 0) {
			$width = ceil($height*$imginfo[0]/$imginfo[1]);
		}

		if ($height == 0) {
			$height = ceil($width*$imginfo[1]/$imginfo[0]);
		}

		$thumb = @ImageCreateTrueColor($width,$height);

		@ImageCopyResampled($thumb,$src,0,0,0,0,$width,$height,@ImageSX($src),@ImageSY($src)) or $result = false;

		// Change FileName
		if ($type=="jpg") {
			@ImageJPEG($thumb,$thumbPath,75) or $result = false;
		} elseif($type=="gif") {
			@ImageGIF($thumb,$thumbPath,75) or $result = false;
		} elseif($type=='png') {
			@imagePNG($thumb,$thumbPath) or $result = false;
		} else {
			$result = false;
		}
		@ImageDestroy($src);
		@ImageDestroy($thumb);
		@chmod($thumbPath,0755);
	}

	if ($delete == true) {
		@unlink($imgPath);
	}

	return $result;
}

function GetGPS($exifCoord, $hemi) {
	$degrees = count($exifCoord) > 0 ? GetGPSToNumber($exifCoord[0]) : 0;
	$minutes = count($exifCoord) > 1 ? GetGPSToNumber($exifCoord[1]) : 0;
	$seconds = count($exifCoord) > 2 ? GetGPSToNumber($exifCoord[2]) : 0;
	
	$flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;
	
	return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
}

function GetGPSToNumber($coordPart) {
	$parts = explode('/', $coordPart);

	if (count($parts) <= 0) return 0;
	if (count($parts) == 1) return $parts[0];

	return floatval($parts[0]) / floatval($parts[1]);
}
?>