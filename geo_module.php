<?
$sIp = Core_Array::get($_SERVER, 'REMOTE_ADDR', Core_Array::get($_SERVER, 'HTTP_X_FORWARDED_FOR', '127.0.0.1'));
$oGeoData = Core_Geoip::instance()->getGeoData($sIp);
if (isset($_POST['city_choose'])) {$choosenCity = $_POST['city_choose'];}
if (!empty($choosenCity)) {$_SESSION['current_city'] = $choosenCity;}

if (isset($_SESSION['current_city'])){
	$city_name = $_SESSION['current_city'];
}else{
	if (!is_null($oGeoData))
	{
		$city_name = Core_Entity::factory('Shop_Country_Location_City', $oGeoData->cityId)->name;
	}else{
		$_SESSION['current_city'] = 'Брянск';
		$city_name = 'Брянск';
	}
}

// Функция выделения основного домена сайта для редиректа
function extractDomain($host, $level = 2, $ignoreWWW = false) { //  Уровень означает уровень домена - site.ru - 2х-уровневый
    $parts = explode(".", $host);
    if($ignoreWWW and $parts[0] == 'www') unset($parts[0]);
    $parts = array_slice($parts, -$level);
    return implode(".", $parts);
}

// Функция выделения поддомена сайта 
function getSubDomain($host, $level = -2) { //  Уровень означает уровень домена - level.site.ru - результат: level
	$tmp = explode('.', $host);
	$tmp = array_slice($tmp, 0, $level);
	$str = implode(".", $tmp);
	return $str;
}

// Функция склонение города
function morpher_inflect($text, $padeg)
{
	$credentials = array('Username'=>'test', 
						 'Password'=>'test');

	$header = new SOAPHeader('http://morpher.ru/', 
						 'Credentials', $credentials);        

	$url = 'http://morpher.ru/WebService.asmx?WSDL';

	$client = new SoapClient($url); 

	$client->__setSoapHeaders($header);

	$params = array('parameters'=>array('s'=>$text));

	$result = (array) $client->__soapCall('GetXml', $params); 

	$singular = (array) $result['GetXmlResult']; 

	return $singular[$padeg];
}

// Склонение города
if (isset($city_name)){
	$city_nameR = morpher_inflect($city_name, 'Р');
	$city_nameP = morpher_inflect($city_name, 'П');
}

// Вступительная фраза перед названием города
$prePhrase = array("phrase1" => "купить в",
                "phrase2" => "заказать в");

// Функция редиректа на целевой домен		
function changeDomain($region){
	switch ($region) {
		case 'Брянск' :
			header('HTTP/1.1 200 OK');
			header('Location: http://bryansk.' . extractDomain ($_SERVER['HTTP_HOST'], 3) . $_SERVER['REQUEST_URI'], true, 301); // Указан уровень 3 - т.к тестовый домен трехуровневый
			exit();
			break; 

		case 'Казань' :
			header('HTTP/1.1 200 OK');
			header('Location: http://kazan.' . extractDomain ($_SERVER['HTTP_HOST'], 3) . $_SERVER['REQUEST_URI'], true, 301); // Указан уровень 3 - т.к тестовый домен трехуровневый
			exit();
			break; 

		case 'Москва' :
			header('HTTP/1.1 200 OK');
			header('Location: http://' . extractDomain ($_SERVER['HTTP_HOST'], 3) . $_SERVER['REQUEST_URI'], true, 301); // Указан уровень 3 - т.к тестовый домен трехуровневый
			exit();
			break; 
	}
}
// Редирект при выборе города из выпадающего списка в форме
if (isset($_POST['city_choose'])){
	changeDomain($_POST['city_choose']);
}

// if($_SERVER['HTTP_HOST'] != extractDomain ($_SERVER['HTTP_HOST'], 3)){ // Проверяем, что если не на основном домене
	// changeDomain($city_name);
// }