<?php 

error_reporting(E_ALL | E_STRICT) ;
ini_set('display_errors', 'On');

define( 'BASEPATH', true );

//error_reporting(E_ALL | E_STRICT) ;
//ini_set('display_errors', 'On');

require "application/config/database.php";

define('DB_PREFIX', $db['default']['dbprefix']);
define('DB_DATABASE', $db['default']['database']);
define('DB_USERNAME', $db['default']['username']);
define('DB_PASSWORD', $db['default']['password']);
define('DB_HOSTNAME', $db['default']['hostname']);
define('HTTP_SERVER', $_SERVER['HTTP_HOST']);

echo 'Connecting to DB'. '<br/>';

$conn=mysql_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD) or die ("Íå ìîãó ñîçäàòü ñîåäèíåíèå");
mysql_select_db(DB_DATABASE) or die (mysql_error());

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

		$q = "update ".DB_PREFIX."variants set active = ".($status == 1 && $price > 0 ? "1" : "0").", price = ".$price." where id = ".$shopsId;
		
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
