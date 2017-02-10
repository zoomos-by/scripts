<?php 

error_reporting(E_ALL | E_STRICT) ;
ini_set('display_errors', 'On');

define( 'BASEPATH', true );

//error_reporting(E_ALL | E_STRICT) ;
//ini_set('display_errors', 'On');

require_once('api/Config.php');
$config = new Config();

echo 'Connecting to DB'. '<br/>';

$conn=mysql_connect($config->db_server, $config->db_user, $config->db_password) or die ("couldn't connect to database");
mysql_select_db($config->db_name) or die (mysql_error());

$ZMS_KEY = ''; //zoomos api key
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

	//$id = $row['id'];
	$shopsId = $row['shopsId'];
	$status = $row['status'];
	$price = $row['price'];

	echo $i . ' of '.sizeof($obj). ': ' . $shopsId .  '<br/>';

	if ($shopsId) {

		$q = "update ".$config->db_prefix."variants set price = ".$price." where product_id = ".$shopsId;
		//active = ".($status == 1 && $price > 0 ? "1" : "0").", 
		executeUpdate($q, $conn);
				
//		$q = "update product_shop set price = ".$price.", wholesale_price = ".$price.", active = ".($status == 1 && $price > 0 ? "1" : "0").", date_upd = current_timestamp where zoomos_id = ".$id;
		
//		executeUpdate($q, $conn);
		

		//array_push($ids, $id);
	}

	
	$i++;
}

//	$q = "update product set active = 0 where zoomos_id not in (".implode(",", $ids).")";
//	executeUpdate($q, $conn);

//	$q = "update product_shop set active = 0 where zoomos_id not in (".implode(",", $ids).")";
//	executeUpdate($q, $conn);



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
