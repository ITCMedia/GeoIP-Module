<?php

Core_Session::close();
if(isset($_COOKIE['current_city'])) $city_name = $_COOKIE['current_city'];
if(isset($_COOKIE['current_city_r'])) $city_nameR = $_COOKIE['current_city_r'];
if(isset($_COOKIE['current_city_p'])) $city_nameP = $_COOKIE['current_city_p'];

/*
 * 0 - PHP-���������
 * 1 - XSL-���������
 */
//$type = 0;

// ��������� ������
$createIndex = FALSE;

// ���������� ������� � ������ ����
$perFile = 50000;

$oSite = Core_Entity::factory('Site')->getByAlias(Core::$url['host']);

$oSite_Alias = $oSite->getCurrentAlias();

if(getSubDomain($_SERVER['HTTP_HOST'], -3) != ''){
	$oSite->getCurrentAlias()->name = getSubDomain($_SERVER['HTTP_HOST'], -3) . '.' . $oSite->getCurrentAlias()->name;
}


if (is_null($oSite_Alias))
{
?>Site hasn't had a default alias!<?php
	exit();
}

//if ($type == 0)
//{

$oCore_Sitemap = new Core_Sitemap($oSite);
$oCore_Sitemap
	->createIndex($createIndex)
	->perFile($perFile)
	// ���������������� ��� � 3 ���
	->rebuildTime(60*60*24 * 3);

if (Core::moduleIsActive('informationsystem'))
{
	$oCore_Sitemap
		// ���������� ������ �������������� ������ � ����� �����
		->showInformationsystemGroups(Core_Page::instance()->libParams['showInformationsystemGroups'])
		// ���������� �������� �������������� ������ � ����� �����
		->showInformationsystemItems(Core_Page::instance()->libParams['showInformationsystemItems']);
}

if (Core::moduleIsActive('shop'))
{
	$oCore_Sitemap
		// ���������� ������ �������� � ����� �����
		->showShopGroups(Core_Page::instance()->libParams['showShopGroups'])
		// ���������� ������ �������� � ����� �����
		->showShopItems(Core_Page::instance()->libParams['showShopItems'])
		// ���������� ����������� � ����� �����
		->showModifications(Core_Array::get(Core_Page::instance()->libParams, 'showModifications', 1));
}

$oCore_Sitemap
	// ���������������� ��� ������� ������������ ������ ����������� ������
	//->limit(10000)
	->fillNodes()
	->execute();

//}
/*else
{
	$Structure_Controller_Show = new Structure_Controller_Show(
		$oSite->showXmlAlias(TRUE)
	);

	$Structure_Controller_Show
		//->parentId(0)
		->xsl(
			Core_Entity::factory('Xsl')->getByName(Core_Page::instance()->libParams['xsl'])
		);

	if (Core::moduleIsActive('informationsystem'))
	{
		$Structure_Controller_Show
			// ���������� ������ �������������� ������ � ����� �����
			->showInformationsystemGroups(Core_Page::instance()->libParams['showInformationsystemGroups'])
			// ���������� �������� �������������� ������ � ����� �����
			->showInformationsystemItems(Core_Page::instance()->libParams['showInformationsystemItems']);
	}

	if (Core::moduleIsActive('shop'))
	{
		$Structure_Controller_Show
			// ���������� ������ �������� � ����� �����
			->showShopGroups(Core_Page::instance()->libParams['showShopGroups'])
			// ���������� ������ �������� � ����� �����
			->showShopItems(Core_Page::instance()->libParams['showShopItems']);
	}

	$Structure_Controller_Show->show();
}*/

exit();