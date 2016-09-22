<?
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

?>