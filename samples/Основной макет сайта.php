<?
require $_SERVER['DOCUMENT_ROOT'].'/geo_module.php';
?>

<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>

		<?
if (Core::moduleIsActive('shop') && isset(Core_Page::instance()->libParams['shopId'])) { // �������� ���� ��� ��������
		?>
		<title><?php Core_Page::instance()->showTitle();?></title>
		<meta name="description" content="<?php Core_Page::instance()->showDescription();?>" />
		<meta name="keywords" content="<?php Core_Page::instance()->showKeywords()?>" />

		<?
} else if(Core::moduleIsActive('informationsystem') && isset(Core_Page::instance()->libParams['informationsystemId'])) {  // �������� ���� ��� ��
		?>
		<title><?php Core_Page::instance()->showTitle();?></title>
		<meta name="description" content="<?php Core_Page::instance()->showDescription();?>" />
		<meta name="keywords" content="<?php Core_Page::instance()->showKeywords()?>" />
		<?
} else {  // �������� ���� ��� �������
		?>
		<title><?php Core_Page::instance()->showTitle(); echo ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP; ?></title>
		<meta name="description" content="<?php Core_Page::instance()->showDescription(); echo ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP; ?>" />
		<meta name="keywords" content="<?php Core_Page::instance()->showKeywords();  echo ' ' . $prePhrase['phrase1'] . ' ' . $city_nameP; ?>" />
		<?
}
		?>

		<meta content="text/html; charset=<?php echo SITE_CODING?>" http-equiv="Content-Type" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
	</head>
	<body>
		<header>   
			<div class="phone_top">
				<?
					if($city_name == "������"){
				?>
				<p id='Label1'>
					����� ��������
				</p>
				<?
					} elseif($city_name == "������"){ 
				?>
				<p id='Label2'>
					����� ��������
				</p>
				<?
					}
				?>
			</div>
			
			<form action="./" method="POST">
				<select size="1" name="city_choose" onchange="$(this).parent('form').submit();">
					<?
						echo "<option selected value='$city_name'>������� �����: $city_name</option>";
					?>
					<option value="������">������</option>
					<option value="������">������</option>
				</select>
			</form>
		</header> 
	</body>
</html>