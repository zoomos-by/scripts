<?php 


//error_reporting(E_ALL | E_STRICT) ;
//ini_set('display_errors', 'On');

require "manager/includes/config.inc.php";

$DB_PREFIX = $table_prefix;
$DB_DATABASE = str_replace("`", "", $dbase);
$DB_USERNAME = $database_user;
$DB_PASSWORD = $database_password;
$DB_HOSTNAME = $database_server;

 // Параметры базы 
$charset='utf8';



	$query="	SELECT v.id,  concat(rtrim(p.pagetitle), ' ', v.name)  as model, v.sku, v.price as price, v.price2 as price2, v.pricebelrub, v.realprice, p.published as status, 
			       sc.longtitle as category, b.menutitle as vendor, i.filename as image_url
 			FROM ".$DB_PREFIX."shop_catalog AS p
 			left join ".$DB_PREFIX."shop_variants as v on p.id = v.product_id
 			left join ".$DB_PREFIX."site_content as sc on p.parent = sc.id
 			left join ".$DB_PREFIX."site_content as b on b.id = p.brand
 			left join ".$DB_PREFIX."shop_images as i on i.product_id = p.id
 			group by v.id
 			order by sc.longtitle, p.pagetitle
	";


 // Имя таблицы 
$TableName="export.csv";

 // Настройки выходного файла 
$Fields=array(
	/* Формат заполнения:  "Название столбца"	=>	"Имя поля в запросе" */
	/* !!! --- В каком порядке здесь идут столбцы, в таком (слева на право) они и будут на выходе --- !!! */
	"id"				=>	"id",
	"category"			=>	"category",
	"vendor"			=>	"vendor",
	"model"				=>	"model",
	"sku"				=>	"sku",
//	"model_full"			=>	"model_full",
	"price"				=>	"price",
	"price2"			=>	"price2",
	"pricebelrub"			=>	"pricebelrub",
	"realprice"			=>	"realprice",
	"status"			=>	"status",
	"url"				=>	"url",
	"image_url"			=>	"image_url",
	"full_name"			=>	"full_name",
	"full_model"			=>	"full_model",
	"stock_status_id"		=>	"stock_status_id",
	"quantity"			=>	"quantity"
);


// Создаем соединение 
$conn=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD) or die ("Не могу создать соединение");
// Ставим чарсет на UTF8
mysql_query("SET character_set_results = '$charset', character_set_client = '$charset', character_set_connection = '$charset', character_set_database = '$charset', character_set_server = '$charset'", $conn);
// Выбираем базу данных. Если произойдет ошибка - вывести ее 
mysql_select_db($DB_DATABASE) or die (mysql_error());

$result=mysql_query($query); 

echo mysql_error($conn);


$csv_doc = '';

while ($row = mysql_fetch_array($result)) {$arr[]=$row;}


$i=1;
$urls=array();
foreach ($arr as $key => $row) 
{
	//  Раскидываем по полям всё что нужно 
	foreach ($Fields as $key => $value) 
		{
			if ($key=="image_url"){$row[$value]=MODX_SITE_URL."image/".$row[$value];}
			if ($key=="url"){$row[$value]=trim(MODX_SITE_URL.($row['url2'] ? $row['url2'].'/' : '').$row['url']).'?product_id='.$row['id'];}

			$csv_doc.= '"'.str_replace('"', '""', trim($row[$value]) ).'";';
		}
	$csv_doc[strlen($csv_doc)-1]="\n";
}

	// Выводим HTTP-заголовки
	 header ( "Expires: Mon, 1 Apr 1974 05:00:00 GMT" );
	 header ( "Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT" );
	 header ( "Cache-Control: no-cache, must-revalidate" );
	 header ( "Pragma: no-cache" );
	 header ( "Content-type: text/csv" );
	 header ( "Content-Disposition: attachment; filename=$TableName" );

	 echo $csv_doc;


mysql_close($conn);

 ?>
