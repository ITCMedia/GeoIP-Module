<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * GeoIP Helper
 * Класс для определения региона по IP-адресу. Для опредления используется сервис ipgeobase.ru
 *
 * @package HostCMS 6 GeoIp
 * @author James V. Kotov
 * @copyright 2015
 * @version 3.0
 * @access public
 * @required HostCMS v6.1.1+
 *
 * @sample
 * $sIp = Core_Array::get($_SERVER, 'REMOTE_ADDR', Core_Array::get($_SERVER, 'HTTP_X_FORWARDED_FOR', '127.0.0.1'));
 * echo 'Ваш IP ' . $sIp;
 * $oGeoData = Core_Geoip::instance()->getGeoData($sIp);
 * if (!is_null($oGeoData))
 * {
 *		echo 'Ваш город ' . Core_Entity::factory('Shop_Country_Location_City', $oGeoData->cityId)->name;
 * 		echo 'Ваш регион ' . Core_Entity::factory('Shop_Country_Location', $oGeoData->locationId)->name;
 * 		echo 'Ваша страна ' . Core_Entity::factory('Shop_Country', $oGeoData->countryId)->name;
 * }
 * else
 * {
 * 		echo 'Для вашего IP определить регион не удалось';
 * }
 */

class Core_Geoip
{
	/**
	 * The singleton instances.
	 * @var mixed
	 */
	static public $instance = NULL;


	/**
	 * Cache name
	 * @var string
	 */
	protected $_cacheName = 'default';


	/**
	 * Run-time кеш гео-данных
	 * @var array
	 */
	protected $_geodata = array();

	/**
	 * Правила преобразования регионов в коды HostCMS для спорных городов
	 * @var array
	 */
	private $_locations = array('Республика Адыгея' => 3, 'Республика Алтай' => 4,
        'Алтайский край' => 4, 'Амурская область' => 5, 'Архангельская область' => 6,
        'Астраханская область' => 7, 'Республика Башкортостан' => 8,
        'Белгородская область' => 9, 'Брянская область' => 10, 'Республика Бурятия' =>
        11, 'Владимирская область' => 12, 'Волгоградская область' => 13,
        'Вологодская область' => 14, 'Воронежская область' => 15, 'Республика Дагестан' =>
        16, 'Еврейская автономная область' => 17, 'Забайкальский край' => 74,
        'Ивановская область' => 18, 'Республика Ингушетия' => 73, 'Иркутская область' =>
        19, 'Республика Кабардино-Балкария' => 20, 'Калининградская область' => 21,
        'Республика Калмыкия' => 22, 'Калужская область' => 23, 'Камчатский край' => 24,
        'Республика Карачаево-Черкессия' => 58, 'Республика Карелия' => 25,
        'Кемеровская область' => 26, 'Кировская область' => 27, 'Республика Коми' => 28,
        'Костромская область' => 29, 'Краснодарский край' => 30, 'Красноярский край' =>
        31, 'Курганская область' => 32, 'Курская область' => 33, 'Ленинградская область' =>
        2, 'Липецкая область' => 34, 'Магаданская область' => 35, 'Республика Марий-Эл' =>
        36, 'Республика Мордовия' => 37, 'Москва' => 1, 'Московская область' => 1,
        'Мурманская область' => 38, 'Ненецкий автономный округ' => 77,
        'Нижегородская область' => 39, 'Новгородская область' => 40,
        'Новосибирская область' => 41, 'Омская область' => 42, 'Оренбургская область' =>
        43, 'Орловская область' => 44, 'Пензенская область' => 45, 'Пермский край' => 46,
        'Приморский край' => 47, 'Псковская область' => 48, 'Ростовская область' => 49,
        'Рязанская область' => 50, 'Самарская область' => 51, 'Санкт-Петербург' => 2,
        'Саратовская область' => 52, 'Республика Саха (Якутия)' => 53,
        'Сахалинская область' => 54, 'Свердловская область' => 55,
        'Республика Северная Осетия (Алания)' => 56, 'Смоленская область' => 57,
        'Ставропольский край' => 58, 'Тамбовская область' => 59, 'Республика Татарстан' =>
        60, 'Тверская область' => 61, 'Томская область' => 62, 'Республика Тыва (Тува)' =>
        63, 'Тульская область' => 64, 'Тюменская область' => 65, 'Республика Удмуртия' =>
        66, 'Ульяновская область' => 67, 'Хабаровский край' => 69, 'Республика Хакасия' =>
        70, 'Ханты-Мансийский автономный округ' => 71, 'Челябинская область' => 72,
        'Республика Чечня' => 73, 'Республика Чувашия' => 75,
        'Чукотский автономный округ' => 76, 'Ямало-Ненецкий автономный округ' => 77,
        'Ярославская область' => 78);


	/**
	 * Register an existing instance as a singleton.
	 * @return object
	 */
	static public function instance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Core_Geoip::getGeoData()
	 *
	 * @param string $ip
	 * @return
	 */
	public function getGeoData($ip = '')
	{
		if(!$ip || !Core_Valid::ip($ip))
		{
			return null;
		}

		$sCacheKey = 'geoip' . $ip;

		// короткое run-time кеширование, для повторных запросов того же ip в рамках одного выполнения скрипта
		if (isset($this->_geodata[$sCacheKey]))
		{
			return $this->_geodata[$sCacheKey];
		}

		// длительное системное кеширование
		if (Core::moduleIsActive('cache'))
		{
			$oCore_Cache = Core_Cache::instance(Core::$mainConfig['defaultCache']);
			$inCache = $oCore_Cache->get($sCacheKey, $this->_cacheName);

			if (!is_null($inCache))
			{
				$this->_geodata[$sCacheKey] = $inCache === false ? null : $inCache;
				return $inCache;
			}
		}

		// запишем в локальный кеш пустое значение
		// если что-то найдется для данного ip, информация в локальном кеше будет обновлена
		$this->_geodata[$sCacheKey] = null;

		$oResponseXml = $this->_getGeoIPResponseXML($ip);

		if(!is_null($oResponseXml))
		{
			if ($oResponseXml->ip && !strval($oResponseXml->ip->message) && strval($oResponseXml->ip->city))
			{
				$sRegionName = strval($oResponseXml->ip->region);
				$sCityName = strval($oResponseXml->ip->city);

				$iHostCMSRegionId = ($sRegionName && isset($this->_locations[$sRegionName])) ? $this->_locations[$sRegionName] : 0;

				$oCore_QueryBuilder_Select = Core_QueryBuilder::select();
				$oCore_QueryBuilder_Select
					->select(array('shop_country_locations.shop_country_id', 'country_id'))
					->select(array('shop_country_location_cities.shop_country_location_id', 'location_id'))
					->select(array('shop_country_location_cities.id', 'city_id'))
					->from('shop_country_location_cities')
					->leftjoin('shop_country_locations', 'shop_country_locations.id', '=', 'shop_country_location_cities.shop_country_location_id')
					->leftjoin('shop_countries', 'shop_countries.id', '=', 'shop_country_locations.shop_country_id')
					->where('shop_country_location_cities.name', 'LIKE', $sCityName);

		        if ($iHostCMSRegionId) {
					$oCore_QueryBuilder_Select
						->where('shop_country_location_cities.shop_country_location_id', '=', $iHostCMSRegionId);
		        }

				$result = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();

				if ($result)
				{
					$oGeoData = new stdClass();
					$oGeoData->countryId = intval($result['country_id']);
					$oGeoData->locationId = intval($result['location_id']);
					$oGeoData->cityId = intval($result['city_id']);

					$this->_geodata[$sCacheKey] = $oGeoData;
				}
			}
		}
		if (Core::moduleIsActive('cache'))
		{
			// вместо null в системный кеш сохраняем false, иначе при чтении из кеша нельзя будет различить
			// отсутствие данных в кеше и отсутствие данных для запрошенного ip
			$cacheContent = is_null($this->_geodata[$sCacheKey]) ? false : $this->_geodata[$sCacheKey];
			$oCore_Cache->set($sCacheKey, $cacheContent, $this->_cacheName);
		}
		return $this->_geodata[$sCacheKey];
	}


	/**
	 * Core_Geoip::_getGeoIPResponseXML()
	 *
	 * @param mixed $ip
	 * @return
	 */
	private function _getGeoIPResponseXML($ip)
	{
		$sUrl = 'http://ipgeobase.ru/geo?ip=' . rawurlencode($ip);
		$iPort = 7020;

		$oCore_Http = Core_Http::instance()
				->url($sUrl)
				->port($iPort)
				->method('GET')
				->timeout(5)
				->execute();

		$aResponseHeaders = $oCore_Http->parseHeaders();
		$iResponseStatus = intval(isset($aResponseHeaders['status']) ? $oCore_Http->parseHttpStatusCode($aResponseHeaders['status']) : 0);
		$sXml = $oCore_Http->getDecompressedBody();

		if ($iResponseStatus == 200 && $sXml != '')
		{
			$sXml = iconv("CP1251", "UTF-8", $sXml);
			$sXml = str_replace('windows-1251', 'UTF-8', $sXml);
			$oXml = @simplexml_load_string($sXml);
			if (is_object($oXml))
			{
				return $oXml;
			}
        }
        return null;
	}
}