<?php 

error_reporting(E_ALL | E_STRICT) ;
ini_set('display_errors', 'On');

use Tygh\Bootstrap;
use Tygh\Debugger;
use Tygh\Registry;

// Register autoloader
$this_dir = dirname(__FILE__);
$classLoader = require($this_dir . '/app/lib/vendor/autoload.php');
$classLoader->add('Tygh', $this_dir . '/app');
class_alias('\Tygh\Tygh', 'Tygh');

// Prepare environment and process request vars
list($_REQUEST, $_SERVER, $_GET, $_POST) = Bootstrap::initEnv($_GET, $_POST, $_SERVER, $this_dir);

require "config.php";

echo 'Connecting to DB'. '<br/>';

$conn=mysql_connect($config['db_host'], $config['db_user'], $config['db_password']) or die ("Íå ìîãó ñîçäàòü ñîåäèíåíèå");
mysql_select_db($config['db_name']) or die (mysql_error());

$ZMS_KEY = $_GET['zmsKey']; //zoomos api key
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
$shopsIds = array();

foreach ($obj as $key => $row) 
{

	$id = $row['id'];
	$shopsId = $row['shopsId'];
	$status = $row['status'];
	$price = $row['price'];

	echo $i . ' of '.sizeof($obj). ': ' . $id .  '<br/>';
	
	if ($id) {
		array_push($ids, $id);
	}

	if ($shopsId) {

		$q = "update ".$config['table_prefix']."product_prices set price = ".$price." where product_id = ".$shopsId;
		executeUpdate($q, $conn);

		$q = "update ".$config['table_prefix']."products set status='A' where product_id = ".$shopsId;
		executeUpdate($q, $conn);
				
//		$q = "update product_shop set price = ".$price.", wholesale_price = ".$price.", active = ".($status == 1 && $price > 0 ? "1" : "0").", date_upd = current_timestamp where zoomos_id = ".$id;
//		executeUpdate($q, $conn);

		array_push($shopsIds, $shopsId);
	}

	
	$i++;
}

	$q = "update ".$config['table_prefix']."products set status = 'D' where product_id not in (".implode(",", $shopsIds).")";
	executeUpdate($q, $conn);

/*

	$q = "update ".$config['table_prefix']."product_shop set status = 0 where zoomos_id not in (".implode(",", $ids).")";
	executeUpdate($q, $conn);

	$q = "update ".$config['table_prefix']."product set status = 0, quantity = 0 where product_id not in (".implode(",", $shopsIds).")";
	executeUpdate($q, $conn);
	$q = "update ".$config['table_prefix']."product_shop set status = 0 where product_id not in (".implode(",", $shopsIds).")";
	executeUpdate($q, $conn);
*/




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
