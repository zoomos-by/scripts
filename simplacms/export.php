<?php 


//error_reporting(E_ALL | E_STRICT) ;
//ini_set('display_errors', 'On');

define( 'BASEPATH', true );
require_once('api/Config.php');
$config = new Config();


 // Параметры базы 
$charset='utf8';



	$query="	SELECT p.id, c.name as category, b.name as vendor, p.name as model, v.price, c.url as url2, p.url, v.sku
				FROM ".$config->db_prefix."products AS p
				LEFT JOIN ".$config->db_prefix."brands AS b ON p.brand_id = b.id
				LEFT JOIN ".$config->db_prefix."variants AS v ON v.product_id = p.id
				LEFT JOIN ".$config->db_prefix."products_categories AS pc ON pc.product_id = p.id
				LEFT JOIN ".$config->db_prefix."categories AS c ON c.id = pc.category_id				
				GROUP BY p.id
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
//	"model_full"			=>	"model_full",
	"price"				=>	"price",
	"status"			=>	"status",
	"url"				=>	"url",
	"image_url"			=>	"image_url",
	"full_name"			=>	"full_name",
	"full_model"			=>	"full_model",
	"stock_status_id"		=>	"stock_status_id",
	"quantity"			=>	"quantity",
	"sku"				=>	"sku"
);


// Создаем соединение 
$conn=mysql_connect($config->db_server, $config->db_user, $config->db_password) or die ("Не могу создать соединение");
// Ставим чарсет на UTF8
mysql_query("SET character_set_results = '$charset', character_set_client = '$charset', character_set_connection = '$charset', character_set_database = '$charset', character_set_server = '$charset'", $conn);
// Выбираем базу данных. Если произойдет ошибка - вывести ее 
mysql_select_db($config->db_name) or die (mysql_error());



$result=mysql_query($query); 

echo mysql_error();

$csv_doc = '';

while ($row = mysql_fetch_array($result)) {$arr[]=$row;}


$i=1;
$urls=array();
foreach ($arr as $key => $row) 
{
	//  Раскидываем по полям всё что нужно 
	foreach ($Fields as $key => $value) 
		{
			//if ($key=="image_url"){$row[$value]=$config->root_url."/"."image/".$row[$value];}
			if ($key=="url"){$row[$value]=trim($config->root_url."/"."products/".$row['url']);}

			if ($key=='price') {
				$csv_doc.= doubleval($row[$value]).';';
			} else {
				$csv_doc.= '"'.str_replace('"', '""', iconv($charset, 'cp1251', $row[$value] ) ).'";';
			}
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
