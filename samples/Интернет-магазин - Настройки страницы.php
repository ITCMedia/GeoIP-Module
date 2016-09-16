<?php

require $_SERVER['DOCUMENT_ROOT'].'/geo_module.php';


$oShop = Core_Entity::factory('Shop', Core_Array::get(Core_Page::instance()->libParams, 'shopId'));


class My_Shop_Controller_Show extends Shop_Controller_Show
{
	protected function _groupCondition()
	{
		$oShop = $this->getEntity();

		if ($this->group)
		{
			// если ID группы не 0, т.е. не корневая группа
			// получаем подгруппы
			$aSubGroupsID = $this->fillShopGroup($oShop->id, $this->group); // добавляем текущую группу в массив
			$aSubGroupsID[] = $this->group;
			$this->shopItems()
				->queryBuilder()
				->where('shop_items.shop_group_id', 'IN', $aSubGroupsID); // получаем все товары из подгрупп
		}
		else
		{
			$this->shopItems()
				->queryBuilder()
				->where('shop_items.shop_group_id', 'NOT IN', Core_QueryBuilder::select('id')->from('shop_groups')->where('shop_id', '=', $oShop->id)->where('active', '=', 0));
		}
		return $this;
	}
	protected $_aGroupTree = array();
	public function fillShopGroup($iShopId, $iShopGroupParentId = 0, $iLevel = 0)
	{
		$iShopId = intval($iShopId);
		$iShopGroupParentId = intval($iShopGroupParentId);
		$iLevel = intval($iLevel);
		if ($iLevel == 0)
		{
			$aTmp = Core_QueryBuilder::select('id', 'parent_id')
				->from('shop_groups')
				->where('shop_id', '=', $iShopId)
				->where('deleted', '=', 0)
				->execute()->asAssoc()->result();
			foreach ($aTmp as $aGroup)
			{
				$this->_aGroupTree[$aGroup['parent_id']][] = $aGroup;
			}
		}
		$aReturn = array();
		if (isset($this->_aGroupTree[$iShopGroupParentId]))
		{
			foreach ($this->_aGroupTree[$iShopGroupParentId] as $childrenGroup)
			{
				$aReturn[] = $childrenGroup['id'];
				$aReturn = array_merge($aReturn, $this->fillShopGroup($iShopId, $childrenGroup['id'], $iLevel + 1));
			}
		}
		$iLevel == 0 && $this->_aGroupTree = array();
		return $aReturn;
	}
}

$Shop_Controller_Show = new My_Shop_Controller_Show($oShop);

$Shop_Controller_Show->addEntity(
	Core::factory('Core_Xml_Entity')
	->name('city_name')->value($city_name)
);
$Shop_Controller_Show->addEntity(
	Core::factory('Core_Xml_Entity')
	->name('city_nameR')->value($city_nameR)
);
$Shop_Controller_Show->addEntity(
	Core::factory('Core_Xml_Entity')
	->name('city_nameP')->value($city_nameP)
);


/* Количество */
$on_page = intval(Core_Array::getGet('on_page'));
if ($on_page > 0 && $on_page < 150)
{
	$limit = $on_page;

	$Shop_Controller_Show->addEntity(
		Core::factory('Core_Xml_Entity')
		->name('on_page')->value($on_page)
	);
}
else
{
	$limit = $oShop->items_on_page;
}

$Shop_Controller_Show
	->limit($limit)
	->parseUrl();

// Обработка скачивания файла электронного товара
$guid = Core_Array::getGet('download_file');
if (strlen($guid))
{
	$oShop_Order_Item_Digital = Core_Entity::factory('Shop_Order_Item_Digital')->getByGuid($guid);

	if (!is_null($oShop_Order_Item_Digital) && $oShop_Order_Item_Digital->Shop_Order_Item->Shop_Order->shop_id == $oShop->id)
	{
		$iDay = 7;

		// Проверяем, доступна ли ссылка (Ссылка доступна в течение недели после оплаты)
		if (Core_Date::sql2timestamp($oShop_Order_Item_Digital->Shop_Order_Item->Shop_Order->payment_datetime) > time() - 24 * 60 * 60 * $iDay)
		{
			$oShop_Item_Digital = $oShop_Order_Item_Digital->Shop_Item_Digital;
			if ($oShop_Item_Digital->filename != '')
			{
				Core_File::download($oShop_Item_Digital->getFullFilePath(), $oShop_Item_Digital->filename);
				exit();
			}
		}
		else
		{
			Core_Message::show(Core::_('Shop_Order_Item_Digital.time_is_up', $iDay));
		}
	}

	Core_Page::instance()->response->status(404)->sendHeaders()->showBody();
	exit();
}

// Сравнение товаров
if (Core_Array::getRequest('compare'))
{
	$shop_item_id = intval(Core_Array::getRequest('compare'));

	if (Core_Entity::factory('Shop_Item', $shop_item_id)->shop_id == $oShop->id)
	{
		Core_Session::start();
		if (isset($_SESSION['hostcmsCompare'][$oShop->id][$shop_item_id]))
		{
			unset($_SESSION['hostcmsCompare'][$oShop->id][$shop_item_id]);
		}
		else
		{
			$_SESSION['hostcmsCompare'][$oShop->id][$shop_item_id] = 1;
		}
	}

	Core_Page::instance()->response
		->status(200)
		->header('Pragma', "no-cache")
		->header('Cache-Control', "private, no-cache")
		->header('Vary', "Accept")
		->header('Last-Modified', gmdate('D, d M Y H:i:s', time()) . ' GMT')
		->header('X-Powered-By', 'HostCMS')
		->header('Content-Disposition', 'inline; filename="files.json"');

	Core_Page::instance()->response
		->body(json_encode('OK'))
		->header('Content-type', 'application/json; charset=utf-8');

	Core_Page::instance()->response
		->sendHeaders()
		->showBody();

	exit();
}

// Избранное
if (Core_Array::getRequest('favorite'))
{
	$shop_item_id = intval(Core_Array::getRequest('favorite'));

	if (Core_Entity::factory('Shop_Item', $shop_item_id)->shop_id == $oShop->id)
	{
		Core_Session::start();
		if (isset($_SESSION['hostcmsFavorite'][$oShop->id]) && in_array($shop_item_id, $_SESSION['hostcmsFavorite'][$oShop->id]))
		{
			unset($_SESSION['hostcmsFavorite'][$oShop->id][
				array_search($shop_item_id, $_SESSION['hostcmsFavorite'][$oShop->id])
			]);
		}
		else
		{
			$_SESSION['hostcmsFavorite'][$oShop->id][] = $shop_item_id;
		}
	}

	Core_Page::instance()->response
		->status(200)
		->header('Pragma', "no-cache")
		->header('Cache-Control', "private, no-cache")
		->header('Vary', "Accept")
		->header('Last-Modified', gmdate('D, d M Y H:i:s', time()) . ' GMT')
		->header('X-Powered-By', 'HostCMS')
		->header('Content-Disposition', 'inline; filename="files.json"');

	Core_Page::instance()->response
		->body(json_encode('OK'))
		->header('Content-type', 'application/json; charset=utf-8');

	Core_Page::instance()->response
		->sendHeaders()
		->showBody();

	exit();
}

// Viewed items
if ($Shop_Controller_Show->item && $Shop_Controller_Show->viewed)
{
	$view_item_id = $Shop_Controller_Show->item;

	if (Core_Entity::factory('Shop_Item', $view_item_id)->shop_id == $oShop->id)
	{
		Core_Session::start();

		// Добавляем если такой товар еще не был просмотрен
		if (!isset($_SESSION['hostcmsViewed'][$oShop->id]) || !in_array($view_item_id, $_SESSION['hostcmsViewed'][$oShop->id]))
		{
			$_SESSION['hostcmsViewed'][$oShop->id][] = $view_item_id;
		}
	}
}

if (!is_null(Core_Array::getGet('vote')))
{
	$oSiteuser = Core_Entity::factory('Siteuser')->getCurrent();
	$entity_id = intval(Core_Array::getGet('id'));

	if ($entity_id && !is_null($oSiteuser))
	{
		$entity_type = strval(Core_Array::getGet('entity_type'));
		$vote = intval(Core_Array::getGet('vote'));

		$oObject = Vote_Controller::instance()->getVotedObject($entity_type, $entity_id);

		if (!is_null($oObject))
		{
			$oVote = $oObject->Votes->getBySiteuser_Id($oSiteuser->id);

			$vote_value = $vote ? 1 : -1;

			$deleteVote = 0;
			// Пользователь не голосовал ранее
			if (is_null($oVote))
			{
				$oVote = Core_Entity::factory('Vote');
				$oVote->siteuser_id = $oSiteuser->id;
				$oVote->value = $vote_value;

				$oObject->add($oVote);
			}
			// Пользователь голосовал ранее, но поставил противоположную оценку
			elseif ($oVote->value != $vote_value)
			{
				$oVote->value = $vote_value;
				$oVote->save();
			}
			// Пользователь голосовал ранее и поставил такую же оценку как и ранее, обнуляем его голосование, как будто он вообще не голосовал
			else
			{
				$deleteVote = 1;
				$oVote->delete();
			}

			$aVotingStatistic = Vote_Controller::instance()->getRate($entity_type, $entity_id);

			Core_Page::instance()->response
				->body(
				json_encode(array('value' => $oVote->value, 'item' => $oObject->id, 'entity_type' => $entity_type,
													'likes' => $aVotingStatistic['likes'], 'dislikes' => $aVotingStatistic['dislikes'],
													'rate' => $aVotingStatistic['rate'], 'delete_vote' => $deleteVote)
									)
			);
		}
	}

	Core_Page::instance()->response
		->status(200)
		->header('Pragma', "no-cache")
		->header('Cache-Control', "private, no-cache")
		->header('Vary', "Accept")
		->header('Last-Modified', gmdate('D, d M Y H:i:s', time()) . ' GMT')
		->header('X-Powered-By', 'HostCMS')
		->header('Content-Disposition', 'inline; filename="files.json"');

	if (strpos(Core_Array::get($_SERVER, 'HTTP_ACCEPT', ''), 'application/json') !== FALSE)
	{
		Core_Page::instance()->response->header('Content-type', 'application/json; charset=utf-8');
	}
	else
	{
		Core_Page::instance()->response
			->header('X-Content-Type-Options', 'nosniff')
			->header('Content-type', 'text/plain; charset=utf-8');
	}

	if(Core_Array::getRequest('_'))
	{
		Core_Page::instance()->response
			->sendHeaders()
			->showBody();
		exit();
	}
}

// Текстовая информация для указания номера страницы, например "страница"
$pageName = Core_Array::get(Core_Page::instance()->libParams, 'page')
	? Core_Array::get(Core_Page::instance()->libParams, 'page')
		: 'страница';

// Разделитель в заголовке страницы
$pageSeparator = Core_Array::get(Core_Page::instance()->libParams, 'separator')
	? Core_Page::instance()->libParams['separator']
		: ' / ';

$aTitle = array(htmlspecialchars(Core_Entity::factory('Site', CURRENT_SITE)->name), $oShop->name); // Добавлено название сайта из настроек CMS
$aDescription = array(htmlspecialchars(Core_Entity::factory('Site', CURRENT_SITE)->name), $oShop->name); // Добавлено название сайта из настроек CMS
$aKeywords = array();

if (!is_null($Shop_Controller_Show->tag) && Core::moduleIsActive('tag'))
{
	$oTag = Core_Entity::factory('Tag')->getByPath($Shop_Controller_Show->tag);
	if ($oTag)
	{
		$aTitle[] = $oTag->seo_title != '' ? $oTag->seo_title : Core::_('Shop.tag', $oTag->name);
		$aDescription[] = $oTag->seo_description != '' ? $oTag->seo_description : $oTag->name;
		$aKeywords[] = $oTag->seo_keywords != '' ? $oTag->seo_keywords : $oTag->name;
	}
}

if ($Shop_Controller_Show->group && !$Shop_Controller_Show->item)
{
	$oShop_Group = Core_Entity::factory('Shop_Group', $Shop_Controller_Show->group);

	$aTitle[] = $oShop_Group->seo_title != ''
		? $oShop_Group->seo_title . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP
		: $oShop_Group->name . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP;

	$aDescription[] = $oShop_Group->seo_description != ''
		? $oShop_Group->seo_description . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP
		: $oShop_Group->name . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP;

	$aKeywords[] = $oShop_Group->seo_keywords != ''
		? $oShop_Group->seo_keywords . ', ' . $city_name
		: $oShop_Group->name . ', ' . $city_name;
}

if ($Shop_Controller_Show->item)
{
	$oShop_Item = Core_Entity::factory('Shop_Item', $Shop_Controller_Show->item);

	$aTitle[] = $oShop_Item->seo_title != ''
		? $oShop_Item->seo_title . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP
		: $oShop_Item->name . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP;

	$aDescription[] = $oShop_Item->seo_description != ''
		? $oShop_Item->seo_description . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP
		: $oShop_Item->name . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP;

	$aKeywords[] = $oShop_Item->seo_keywords != ''
		? $oShop_Item->seo_keywords . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP
		: $oShop_Item->name . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP;
}

if ($Shop_Controller_Show->producer)
{
	$oShop_Producer = Core_Entity::factory('Shop_Producer', $Shop_Controller_Show->producer);
	Core_Page::instance()->title($oShop_Producer->name);
	Core_Page::instance()->description($oShop_Producer->name);
	Core_Page::instance()->keywords($oShop_Producer->name);
}

if ($Shop_Controller_Show->page)
{
	array_unshift($aTitle, $pageName . ' ' . ($Shop_Controller_Show->page + 1));
}

if (count($aTitle))
{
	$aTitle = array_reverse($aTitle);
	$aDescription = array_reverse($aDescription);
	$aKeywords = array_reverse($aKeywords);

	Core_Page::instance()->title(implode($pageSeparator, $aTitle));
	Core_Page::instance()->description(implode($pageSeparator, $aDescription));
	Core_Page::instance()->keywords(implode($pageSeparator, $aKeywords));
}

Core_Page::instance()->object = $Shop_Controller_Show;