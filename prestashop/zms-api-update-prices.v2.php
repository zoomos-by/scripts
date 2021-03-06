<?php 

error_reporting(E_ALL | E_STRICT) ;
ini_set('display_errors', 'On');

require "config/settings.inc.php";

echo 'Connecting to DB'. '<br/>';

$conn=mysqli_connect(_DB_SERVER_, _DB_USER_, _DB_PASSWD_) or die ("Не могу создать соединение");
mysqli_select_db($conn, _DB_NAME_) or die (mysql_error());

$ZMS_KEY = ''; // API KEY
$url = 'http://api.export.zoomos.by/pricelist?key='.$ZMS_KEY;

echo 'Downloading JSON from '.$url. '<br/>';

$json = file_get_contents($url);

$obj = json_decode($json,true);

$err = json_last_error();

if ($err) {
	echo 'Error while parsing json: ' . $err . '<br/>';
	die();
}

echo 'Rows count: '.sizeof($obj). '<br/>';


$i = 0;

$ids = array();

foreach ($obj as $key => $row) 
{

	$id = $row['id'];  // zoomos ID
	$shopsId = $row['shopsId'];
	$status = $row['status'];
	$price = $row['price'];

	echo $i . ' of '.sizeof($obj). ': ' . $id .  '<br/>';

	if ($shopsId) {

		$q = "update "._DB_PREFIX_."product set active = ".($status == 1 ? "1" : "0").", price = ".$price.", quantity = 100 where id_product = ".$shopsId;
		executeUpdate($q, $conn);
				
		$q = "update "._DB_PREFIX_."product_shop set price = ".$price.", wholesale_price = ".$price.", active = ".($status == 1 ? "1" : "0").", date_upd = current_timestamp where id_product = ".$shopsId;
		executeUpdate($q, $conn);
		
		$q = "update "._DB_PREFIX_."stock_available set quantity = 100 where id_product = ".$shopsId;
		executeUpdate($q, $conn);

		array_push($ids, $shopsId);
	}

	
	$i++;
}
	// disable other
	
	//$q = "update "._DB_PREFIX_."product set active = 0 where id_product not in (".implode(",", $ids).")";
	//executeUpdate($q, $conn);

	//$q = "update "._DB_PREFIX_."product_shop set active = 0 where id_product not in (".implode(",", $ids).")";
	//executeUpdate($q, $conn);



function executeUpdate($q, $conn) {

	echo $q .  '<br/>';

	if (mysqli_query($conn, $q)) {
	    echo "Record updated successfully";
	} else {
	    echo "Error updating record: " . mysql_error($conn);
	}

	echo '<br/>';


}

?>
