<?php 
require "configuration.php";

echo 'Connecting to DB'. '<br/>';

$config = new JConfig();
$conn=mysql_connect('localhost', $config->user, $config->password) or die ("Íå ìîãó ñîçäàòü ñîåäèíåíèå");
mysql_select_db($config->db) or die (mysql_error());



$ZMS_KEY=''; //zoomos api key

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

	$id = $row['shopsId'];
	$status = $row['status'];
	$price = $row['price'];

	echo $i . ' of '.sizeof($obj). ': ' . $id .  '<br/>';

	if ($id) {

		$q = "update ".$config->dbprefix."virtuemart_products set published = '".($status == 1 ? "1" : "0")."' where virtuemart_product_id = ".$id;
		
		executeUpdate($q, $conn);
				
		$q = "update ".$config->dbprefix."virtuemart_product_prices set product_price = ".$price." where virtuemart_product_id = ".$id;
		
		executeUpdate($q, $conn);

		$priceRowQ="SELECT * FROM ".$config->dbprefix."virtuemart_product_prices where virtuemart_product_id = ".$id;

		$result=mysql_query($priceRowQ); 
		
		if (mysql_num_rows($result) == 0) {
		
			echo 'not found in virtuemart_product_prices <br/>';

			$q = "insert into ".$config->dbprefix."virtuemart_product_prices (virtuemart_product_id, product_price, product_currency, virtuemart_shoppergroup_id) 
			      		values (".$id.", ".$price.", '194' , 0)"; // !!!!!!!!!!
		
			executeUpdate($q, $conn);
		}

		

		array_push($ids, $id);
	}

	
	$i++;
}

	// $q = "update ".$config->dbprefix."virtuemart_products set published = '0' where virtuemart_product_id not in (".implode(",", $ids).")";
	// executeUpdate($q, $conn);



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
