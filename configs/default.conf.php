<?php
header('Content-type: text/html; charset=utf-8', true);

$_ENV['ver'] = '0.1.1205';
// DB정보를 입력하여 주십시오.
$_ENV['db'] = array('host'=>'DB호스트','user_id'=>'DB아이디','password'=>'DB패스워드','db'=>'DB명');
// 언어셋코드를 입력하여 주십시오. 현재는 한국어(ko)만 지원합니다.
$_ENV['language'] = 'ko';
// 구글맵 API 키를 입력하여 주십시오. (https://console.developers.google.com 에서 Google Maps Javascript V3 API 키를 획득할 수 있습니다.)
$_ENV['apikey'] = ''; // 공개 API 액세스 KEY
// 별도의 설정을 하지 않아도 되나 서버의 절대경로가 자동으로 설정되지 않을 경우 서버의 절대경로와 상대경로를 입력하여 주십시오.
$_ENV['path'] = str_replace('/configs/default.conf.php','',__FILE__);
$_ENV['dir'] = str_replace($_SERVER['DOCUMENT_ROOT'],'',$_ENV['path']);

REQUIRE_ONCE $_ENV['path'].'/classes/functions.php';

$_ENV['tables'] = array(
	'photos'=>'az_photos_table'
);

function __autoload($class) {
	if (file_exists($_ENV['path'].'/classes/'.$class.'.class.php') == true) REQUIRE_ONCE $_ENV['path'].'/classes/'.$class.'.class.php';
}
?>