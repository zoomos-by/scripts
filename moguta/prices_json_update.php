<?php 

require "mg-core/lib/mg.php";
MG::getConfigIni();

error_reporting(E_ALL);
ini_set('display_errors', '1');

ini_set('memory_limit', '500M');
set_time_limit(60*60);

ini_set('max_execution_time', 900);

ini_set('default_socket_timeout', 900);

$link = mysql_connect(HOST, USER, PASSWORD);
if (!$link) 
	{die('Not connected : ' . mysql_error());}
$db_selected = mysql_select_db(NAME_BD, $link);
if (!$db_selected) {    die ('Can\'t use foo : ' . mysql_error());}
mysql_set_charset("utf-8",$link);

$sql_log="";

//---------------------- IMPORT SETTINGS -----------------------------
$zms_key=""; //zoomos api key
$zms_base_url="http://api.export.zoomos.by/pricelist?key=";

$debug_mode=false;
$table_prefix=TABLE_PREFIX;


//----------------------- GET ZOOMOS ARRAY --------------------------
$to_import=json_decode(file_get_contents($zms_base_url.$zms_key));

if ($debug_mode) 
	{echo "<h1 style='color:red;'>to_import :</h1><pre>";print_r($to_import);echo "</pre><hr>";}
// die();

$fields['activity']='0';
UpdateTable($fields, $table_prefix."product", '' );


foreach ($to_import as $key => $prod) 
{
	$fields['activity']=$prod->status;

	$fields['price']=$prod->price;
	$fields['price_course']=$prod->price;

	$where=array('code' => $prod->id);

	UpdateTable($fields, $table_prefix."product", $where );

	$ids.=$prod->id.",";
}


//--- DEBUG INPUT ARRAY ----
// echo "<h1 style='color:green;'>ZMS import url<br>".$zms_base_url.$zms_key."<br>DATA:</h1><pre>";print_r($to_import);echo "</pre><hr>";

echo $sql_log;

echo $ids;

mysql_close($link);
echo "<hr>DONE";







//--------------------------------------------------- FUNCTIONS ---------------------------------------------------


function query($str, $include_status=false)
{
	global $sql_log;
	global $debug_mode;	
	$res_arr=array();
	if (!$debug_mode) 
		{$res=mysql_query($str);}
	else {$res=false;}
	$sql_log.=$str;
	if ($include_status) 
		{$sql_log.=" &nbsp; STATUS:".(int)$res;}
	$sql_log.="<hr>";
	if (is_resource($res) )
	{
			$i=0;
			while ( $row=mysql_fetch_array($res) )
			{
				$res_arr[]=$row;
				$i++;
			}
			return $res_arr;
	} else {return $res;}	

}

function UpdateTable($upd_array, $table, $wh_array)
{
	$fld_str="";
	foreach ($upd_array as $key => $value) 
		{$fld_str.="  `".$key.'`="'.$value.'",';}
	$fld_str=trim($fld_str,",");

	$wh_str="";
	foreach ($wh_array as $key => $value) 
		{$wh_str.="AND  `".$key.'`="'.$value.'"';}
	$wh_str=trim($wh_str,"AND");
	if ($wh_str!="") 
		{$wh_str=" WHERE ".$wh_str;}

	$query="UPDATE $table SET $fld_str $wh_str";

	return query($query, true);
}
 ?>
