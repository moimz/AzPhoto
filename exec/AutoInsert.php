<?php
REQUIRE_ONCE '../configs/default.conf.php';

$db = new db();
$insertPath = @opendir('../insert');
$i = 0;

if (is_dir('../userfiles') == false) {
	exit('Create userfiles folder on Root dir!');
}

if (is_dir('../userfiles/origin') == false) {
	mkdir('../userfiles/origin');
	chmod('../userfiles/origin',0707);
}

if (is_dir('../userfiles/viewer') == false) {
	mkdir('../userfiles/viewer');
	chmod('../userfiles/viewer',0707);
}

if (is_dir('../userfiles/thumbnail') == false) {
	mkdir('../userfiles/thumbnail');
	chmod('../userfiles/thumbnail',0707);
}

if (is_dir('../userfiles/calendar') == false) {
	mkdir('../userfiles/calendar');
	chmod('../userfiles/calendar',0707);
}

$count = 0;
while ($insert = @readdir($insertPath)) {
	if ($insert != '.' && $insert != '..' && is_file('../insert/'.$insert) == true) {
		$isInsert = false;
		$type = strtolower(array_pop(explode('.',$insert)));
		if ($type == 'jpg' || $type == 'jpeg') {
			$check = getimagesize('../insert/'.$insert);
			if ($check[2] == 2) {
				$exif = exif_read_data('../insert/'.$insert);

				if (is_array($exif) == true && isset($exif['GPSLongitude']) == true && isset($exif['GPSLatitude']) == true) {
					$filename = md5_file('../insert/'.$insert);
					if ($db->getCount($_ENV['tables']['photos'],"where `filename`='$filename'") > 0) {
						@unlink('../insert/'.$insert);
						continue;
					}
					
					$file = array();
					$file['filename'] = $filename;
					$file['time'] = strtotime($exif['DateTime']);
					$file['title'] = $exif['FileName'];
					$file['width'] = $check[0];
					$file['height'] = $check[1];
					$file['exif'] = base64_encode(json_encode($exif));
					$file['longitude'] = GetGPS($exif['GPSLongitude'], $exif['GPSLongitudeRef'])+180;
					$file['latitude'] = GetGPS($exif['GPSLatitude'], $exif['GPSLatitudeRef'])+90;
					$file['reg_date'] = time();
					
					@copy('../insert/'.$insert,'../userfiles/origin/'.$file['filename'].'.jpg');
					chmod('../userfiles/origin/'.$file['filename'].'.jpg',0707);
					if ($check[0] > 1500 || $check[1] > 1500) {
						if ($check[0] > $check[1]) GetThumbnail('../insert/'.$insert,'../userfiles/viewer/'.$file['filename'].'.jpg',1500,0,false);
						else GetThumbnail('../insert/'.$insert,'../userfiles/viewer/'.$file['filename'].'.jpg',0,1500,false);
					} else {
						@copy('../insert/'.$insert,'../userfiles/viewer/'.$file['filename'].'.jpg');
					}
					chmod('../userfiles/viewer/'.$file['filename'].'.jpg',0707);
					
					GetThumbnail('../insert/'.$insert,'../userfiles/thumbnail/'.$file['filename'].'.jpg',0,60,false);
					chmod('../userfiles/thumbnail/'.$file['filename'].'.jpg',0707);
					
					if ($check[0] < $check[1]) {
						GetThumbnail('../insert/'.$insert,'../userfiles/calendar/'.$file['filename'].'.jpg',500,0,false);
					} else {
						GetThumbnail('../insert/'.$insert,'../userfiles/calendar/'.$file['filename'].'.jpg',0,500,false);
					}
					chmod('../userfiles/calendar/'.$file['filename'].'.jpg',0707);
					
					$isInsert = true;
					
					$db->insert($_ENV['tables']['photos'],$file);
				}
			}
		}
		
		@unlink('../insert/'.$insert);
	}
	$count++;
	
	if ($count == 30) {
		@closedir($insertPath);
		exit('<script>location.href = location.href;</script>');
		sleep(1);
	}
}
@closedir($insertPath);
exit('DONE!');
?>