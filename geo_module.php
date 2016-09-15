<?
$sIp = Core_Array::get($_SERVER, 'REMOTE_ADDR', Core_Array::get($_SERVER, 'HTTP_X_FORWARDED_FOR', '127.0.0.1'));
$oGeoData = Core_Geoip::instance()->getGeoData($sIp);
$city_name = Core_Entity::factory('Shop_Country_Location_City', $oGeoData->cityId)->name;
if (isset($_POST['city_choose'])) {$choosenCity = $_POST['city_choose'];}
if (!empty($choosenCity)) {$_SESSION['current_city'] = $choosenCity;}

if (isset($_SESSION['current_city'])){
	//echo $_SESSION['current_city'];
	$city_name = $_SESSION['current_city'];
}else{
	if (!is_null($oGeoData))
	{
		//echo $city_name;
	}else{
		$_SESSION['current_city'] = 'Брянск';
		$city_name = 'Брянск';
	}
}

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

if (isset($city_name)){
	$city_nameR = morpher_inflect($city_name, 'Р');
	$city_nameP = morpher_inflect($city_name, 'П');
}
