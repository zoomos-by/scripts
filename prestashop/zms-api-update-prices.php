<?php 

error_reporting(E_ALL | E_STRICT) ;
ini_set('display_errors', 'On');

require "config/settings.inc.php";

echo 'Connecting to DB'. '<br/>';

$conn=mysql_connect(_DB_SERVER_, _DB_USER_, _DB_PASSWD_) or die ("Не могу создать соединение");
mysql_select_db(_DB_NAME_) or die (mysql_error());


$url = 'http://export.zoomos.by/api/pricelist?key=';

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

	$id = $row['id'];
	$status = $row['status'];
	$price = $row['price'];

	echo $i . ' of '.sizeof($obj). ': ' . $id .  '<br/>';

	if ($id) {

		$q = "update product set active = ".($status == 1 ? "1" : "0").", price = ".$price." where zoomos_id = ".$id;
		
		executeUpdate($q, $conn);
				
		$q = "update product_shop set price = ".$price.", wholesale_price = ".$price.", active = ".($status == 1 ? "1" : "0").", date_upd = current_timestamp where zoomos_id = ".$id;
		
		executeUpdate($q, $conn);
		

		array_push($ids, $id);
	}

	
	$i++;
}

	$q = "update product set active = 0 where zoomos_id not in (".implode(",", $ids).")";
	executeUpdate($q, $conn);

	$q = "update product_shop set active = 0 where zoomos_id not in (".implode(",", $ids).")";
	executeUpdate($q, $conn);



function executeUpdate($q, $conn) {

	echo $q .  '<br/>';

	if (mysql_query($q, $conn)) {
	    echo "Record updated successfully";
	} else {
	    echo "Error updating record: " . mysql_error($conn);
	}

	echo '<br/>';


}

?>