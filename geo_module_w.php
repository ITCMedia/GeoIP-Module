<?
$sIp = Core_Array::get($_SERVER, 'REMOTE_ADDR', Core_Array::get($_SERVER, 'HTTP_X_FORWARDED_FOR', '127.0.0.1'));
if (!is_null($sIp)) // Проверяем, если модуль GeoIP получил данные
	$oGeoData = Core_Geoip::instance()->getGeoData($sIp);
	
if (isset($_POST['city_choose'])) {$choosenCity = htmlspecialchars($_POST['city_choose']);}
if (!empty($choosenCity)) {
	setcookie('current_city', $choosenCity, time() + 3600 * 24 * 365, '/', '.'.extractDomain ($_SERVER['HTTP_HOST'], 3)); // Установка куки на доменное имя
}

if (isset($_COOKIE['current_city'])){
	$city_name = $_COOKIE['current_city']; // Если существуют куки, устанавливаем их
}else{
	if (!is_null($oGeoData))
	{
		$city_name = Core_Entity::factory('Shop_Country_Location_City', $oGeoData->cityId)->name; // Если кук нет, показывам, что получил GeoIP
	}else{
		$city_name = 'Москва'; // Если данных с GeoIP нет - выводим дефолтный город
	}
}

// Функция выделения основного домена сайта для редиректа
function extractDomain($host, $level = 2, $ignoreWWW = FALSE) { //  Уровень означает уровень домена - site.ru - 2х-уровневый
    $parts = explode(".", $host);
    if($ignoreWWW and $parts[0] == 'www') unset($parts[0]);
    $parts = array_slice($parts, -$level);
    return implode(".", $parts);
}

// Функция выделения поддомена сайта 
function getSubDomain($host, $level = -2, $ignoreWWW = TRUE) { //  Уровень означает уровень домена - level.site.ru - результат: level. + Игнорировать www при получении поддомена
	$parts = explode('.', $host);
    if($ignoreWWW and $parts[0] == 'www') unset($parts[0]);
	$parts = array_slice($parts, 0, $level);
	return implode(".", $parts);
}

// Функция склонение города с проверкой на доступность сервиса
function morpher_inflect($text, $padeg)
{
	if (function_exists('get_headers')){ 
		$check_url = get_headers('http://morpher.ru/');
		if (strpos($check_url[0],'200')) {
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
		} else {
			return false;
		}
	}
}

// Склонение города
if (isset($city_name) || !is_null($oGeoData) && function_exists('morpher_inflect')){
	$city_nameR = morpher_inflect($city_name, 'Р');
	$city_nameP = morpher_inflect($city_name, 'П');
} else {
	$city_nameR = 'Москвы'; // Дефолтные города, если GeoIP и Морфер недоступен
	$city_nameP = 'Москве';	
}

// Указывает, если был произведен переход на конкретный региональный поддомен, то там стоит выводить только конкретную мету
if(getSubDomain($_SERVER['HTTP_HOST'], -3) != ''){
	switch (getSubDomain($_SERVER['HTTP_HOST'], -3)) {
		case 'bryansk' :
			$city_nameR = morpher_inflect('Брянск', 'Р');
			$city_nameP = morpher_inflect('Брянск', 'П');
			$city_name = 'Брянск';
			break; 

		case 'spb' :
			$city_nameR = morpher_inflect('Санкт-Петербург', 'Р');
			$city_nameP = morpher_inflect('Санкт-Петербург', 'П');
			$city_name = 'Санкт-Петербург';
			break; 
	}
}

// Постановка rel="canonical" для посетителя с региона на корневом домене
function putRelCanonical($city_name){
	if($_SERVER['HTTP_HOST'] == extractDomain ($_SERVER['HTTP_HOST'], 3)){ 
		switch ($city_name) {
			case 'Брянск' :
				echo "<link rel='canonical' href='http://bryansk.". extractDomain ($_SERVER['HTTP_HOST'], 3) ."' /> \n";
				break; 

			case 'Санкт-Петербург' :
				echo "<link rel='canonical' href='http://spb.". extractDomain ($_SERVER['HTTP_HOST'], 3) ."' /> \n";
				break; 
		}
	}
}

// Функция редиректа на целевой домен: проверяет, если регион соответствует кукам или GeoIP и если пользователь находится не на домене, на который надо производить редирект, то выполняет переход по указанному адресу
function changeDomain($region){
	if(($region == 'Брянск') && ('http://'.$_SERVER['HTTP_HOST'] != 'http://bryansk.' . extractDomain ($_SERVER['HTTP_HOST'], 3)))
	{	// Редирект на указанный поддомен для региона
		header('HTTP/1.1 200 OK');
		header('Location: http://bryansk.' . extractDomain ($_SERVER['HTTP_HOST'], 3) . $_SERVER['REQUEST_URI'], true, 301); 
		exit();		
	} 
	else if(($region == 'Санкт-Петербург') && ('http://'.$_SERVER['HTTP_HOST'] != 'http://spb.' . extractDomain ($_SERVER['HTTP_HOST'], 3))) 
	{	// Редирект на указанный поддомен для региона
		header('HTTP/1.1 200 OK');
		header('Location: http://spb.' . extractDomain ($_SERVER['HTTP_HOST'], 3) . $_SERVER['REQUEST_URI'], true, 301); 
		exit();
	} 
	else if(($region == 'Москва') && ('http://'.$_SERVER['HTTP_HOST'] != 'http://' . extractDomain ($_SERVER['HTTP_HOST'], 3))) 
	{	// Редирект на корневой домен для Москвы
		header('HTTP/1.1 200 OK');
		header('Location: http://' . extractDomain ($_SERVER['HTTP_HOST'], 3) . $_SERVER['REQUEST_URI'], true, 301); 
		exit();
	}
}

// Проверка, что страница не состоит в списке на невывод меты
function checkRestricted() {
	$found = 0;
	if (file_exists("links.txt")){
		$linksFile = array();
		$linksFile = file("links.txt", FILE_IGNORE_NEW_LINES);
		if ($linksFile) {
			foreach($linksFile as $row) {
				if ($_SERVER['REQUEST_URI'] == $row){
					$found = 1;
					break;
				}
			}
		}
	}
	if($found == 0)
		return true;
	else 
		return false;
}

// Редирект при выборе города из выпадающего списка в форме
if (isset($_POST['city_choose'])){
	changeDomain($choosenCity);
}

// Если домен пользователя не совпадает с главной странице и у пользователя установлены куки, то выполняем редирект
if(($_SERVER['HTTP_HOST'] != extractDomain ($_SERVER['HTTP_HOST'], 3)) && isset($_COOKIE['current_city'])){ 
	changeDomain($_COOKIE['current_city']);
} 

// Вступительная фраза перед названием города
$prePhrase = array("phrase1" => "в",
                "phrase2" => "купить в",
                "phrase3" => "заказать в");
