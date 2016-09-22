<?php

require $_SERVER['DOCUMENT_ROOT'].'/geo_module.php';

$oInformationsystem = Core_Entity::factory('Informationsystem', Core_Array::get(Core_Page::instance()->libParams, 'informationsystemId'));

$Informationsystem_Controller_Show = new Informationsystem_Controller_Show($oInformationsystem);


$Informationsystem_Controller_Show->addEntity(
	Core::factory('Core_Xml_Entity')
	->name('city_name')->value($city_name)
);
$Informationsystem_Controller_Show->addEntity(
	Core::factory('Core_Xml_Entity')
	->name('city_nameR')->value($city_nameR)
);
$Informationsystem_Controller_Show->addEntity(
	Core::factory('Core_Xml_Entity')
	->name('city_nameP')->value($city_nameP)
);

$Informationsystem_Controller_Show
	->limit($oInformationsystem->items_on_page)	
	->parseUrl();

// Текстовая информация для указания номера страницы, например "страница"
$pageName = Core_Array::get(Core_Page::instance()->libParams, 'page')
	? Core_Array::get(Core_Page::instance()->libParams, 'page')
		: 'страница';

// Разделитель в заголовке страницы
$pageSeparator = Core_Array::get(Core_Page::instance()->libParams, 'separator')
	? Core_Page::instance()->libParams['separator']
		: ' / ';

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

$aTitle = array(htmlspecialchars(Core_Entity::factory('Site', CURRENT_SITE)->name), $oInformationsystem->name); // Добавлено название сайта из настроек CMS
$aDescription = array(htmlspecialchars(Core_Entity::factory('Site', CURRENT_SITE)->name), $oInformationsystem->name); // Добавлено название сайта из настроек CMS
$aKeywords = array();

if (!is_null($Informationsystem_Controller_Show->tag) && Core::moduleIsActive('tag'))
{
	$oTag = Core_Entity::factory('Tag')->getByPath($Informationsystem_Controller_Show->tag);
	if ($oTag)
	{
		$aTitle[] = $oTag->seo_title != '' ? $oTag->seo_title : Core::_('Informationsystem.tag', $oTag->name);
		$aDescription[] = $oTag->seo_description != '' ? $oTag->seo_description : $oTag->name;
		$aKeywords[] = $oTag->seo_keywords != '' ? $oTag->seo_keywords : $oTag->name;
	}
}

if($oInformationsystem->id == 0 && checkRestricted()){ // Подключение ИС к мете, если указан ID

	if ($Informationsystem_Controller_Show->group && !$Informationsystem_Controller_Show->item)
	{
		$oInformationsystem_Group = Core_Entity::factory('Informationsystem_Group', $Informationsystem_Controller_Show->group);

		$aTitle[] = $oInformationsystem_Group->seo_title != ''
			? $oInformationsystem_Group->seo_title . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP
			: $oInformationsystem_Group->name . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP;

		$aDescription[] = $oInformationsystem_Group->seo_description != ''
			? $oInformationsystem_Group->seo_description . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP
			: $oInformationsystem_Group->name . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP;

		$aKeywords[] = $oInformationsystem_Group->seo_keywords != ''
			? $oInformationsystem_Group->seo_keywords . ', ' . $city_name
			: $oInformationsystem_Group->name . ', ' . $city_name;
	}

	// Ниже добавляются города в родительном падеже
	if ($Informationsystem_Controller_Show->item)
	{
		$oInformationsystem_Item = Core_Entity::factory('Informationsystem_Item', $Informationsystem_Controller_Show->item);

		$aTitle[] = $oInformationsystem_Item->seo_title != ''
			? $oInformationsystem_Item->seo_title . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP
			: $oInformationsystem_Item->name . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP;

		$aDescription[] = $oInformationsystem_Item->seo_description != ''
			? $oInformationsystem_Item->seo_description . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP
			: $oInformationsystem_Item->name . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP;

		$aKeywords[] = $oInformationsystem_Item->seo_keywords != ''
			? $oInformationsystem_Item->seo_keywords . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP
			: $oInformationsystem_Item->name . ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP;
	}

} else { // Показывать простой меты

	if ($Informationsystem_Controller_Show->group && !$Informationsystem_Controller_Show->item)
	{
		$oInformationsystem_Group = Core_Entity::factory('Informationsystem_Group', $Informationsystem_Controller_Show->group);

		$aTitle[] = $oInformationsystem_Group->seo_title != ''
			? $oInformationsystem_Group->seo_title
			: $oInformationsystem_Group->name;

		$aDescription[] = $oInformationsystem_Group->seo_description != ''
			? $oInformationsystem_Group->seo_description
			: $oInformationsystem_Group->name;

		$aKeywords[] = $oInformationsystem_Group->seo_keywords != ''
			? $oInformationsystem_Group->seo_keywords
			: $oInformationsystem_Group->name;
	}

	if ($Informationsystem_Controller_Show->item)
	{
		$oInformationsystem_Item = Core_Entity::factory('Informationsystem_Item', $Informationsystem_Controller_Show->item);

		$aTitle[] = $oInformationsystem_Item->seo_title != ''
			? $oInformationsystem_Item->seo_title
			: $oInformationsystem_Item->name;

		$aDescription[] = $oInformationsystem_Item->seo_description != ''
			? $oInformationsystem_Item->seo_description
			: $oInformationsystem_Item->name;

		$aKeywords[] = $oInformationsystem_Item->seo_keywords != ''
			? $oInformationsystem_Item->seo_keywords
			: $oInformationsystem_Item->name;

	}

}
if ($Informationsystem_Controller_Show->page)
{
	array_unshift($aTitle, $pageName . ' ' . ($Informationsystem_Controller_Show->page + 1));
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

Core_Page::instance()->object = $Informationsystem_Controller_Show;