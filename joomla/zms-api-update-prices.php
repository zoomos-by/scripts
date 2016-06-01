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

		$q = "update jos_vm_product set product_publish = '".($status == 1 ? "Y" : "N")."' where product_id = ".$id;
		
		executeUpdate($q, $conn);
				
		$q = "update jos_vm_product_price set product_price = ".$price." where product_id = ".$id;
		
		executeUpdate($q, $conn);

		$priceRowQ="SELECT * FROM jos_vm_product_price where product_id = ".$id;

		$result=mysql_query($priceRowQ); 
		
		if (mysql_num_rows($result) == 0) {

			$q = "insert into jos_vm_product_price (product_id, product_price, product_currency, mdate, shopper_group_id, product_price_vdate, product_price_edate) 
			      		values (".$id.", ".$price.", 'BLR', '1415696144', 5, 0, 0)";
		
			executeUpdate($q, $conn);
		}

		

		array_push($ids, $id);
	}

	
	$i++;
}

	$q = "update jos_vm_product set product_publish = 'N' where product_id not in (".implode(",", $ids).")";
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
