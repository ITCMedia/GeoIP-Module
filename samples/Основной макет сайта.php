<?
require $_SERVER['DOCUMENT_ROOT'].'/geo_module.php';
?>

<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>

		<title><?php keyReplace(Core_Page::instance()->title, $city_nameP, $city_nameR, $city_name); ?></title>
		<meta name="description" content="<?php keyReplace(Core_Page::instance()->description, $city_nameP, $city_nameR, $city_name); ?>" />
		<meta name="keywords" content="<?php keyReplace(Core_Page::instance()->keywords, $city_nameP, $city_nameR, $city_name); ?>" />

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