
<?php 

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

error_reporting(E_ALL | E_STRICT) ;
ini_set('display_errors', 'On');

require "config.php";

 // Параметры базы 
$charset='utf8';



	$query="	SELECT p.product_id AS id, cat.category, pdescr.product as model, pprice.price as price
				FROM ".$config['table_prefix']."products AS p
				JOIN ".$config['table_prefix']."product_descriptions as pdescr ON pdescr.product_id=p.product_id
				LEFT JOIN ".$config['table_prefix']."products_categories AS prod_c ON prod_c.product_id = p.product_id
				LEFT JOIN ".$config['table_prefix']."category_descriptions AS cat ON cat.category_id = prod_c.category_id
				LEFT JOIN ".$config['table_prefix']."product_prices AS pprice ON pprice.product_id = p.product_id
				JOIN ".$config['table_prefix']."companies AS comp ON comp.company_id = p.company_id
				where comp.storefront = '".$_GET['shop']."'
				group by p.product_id 
	";


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
	"quantity"			=>	"quantity"
);


// Создаем соединение 
$conn=mysql_connect($config['db_host'], $config['db_user'], $config['db_password']) or die ("Не могу создать соединение");
// Ставим чарсет на UTF8
mysql_query("SET character_set_results = '$charset', character_set_client = '$charset', character_set_connection = '$charset', character_set_database = '$charset', character_set_server = '$charset'", $conn);

// Выбираем базу данных. Если произойдет ошибка - вывести ее 
mysql_select_db($config['db_name']) or die (mysql_error());



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
			//if ($key=="image_url"){$row[$value]=HTTP_SERVER."image/".$row[$value];}
			//if ($key=="url"){$row[$value]=trim(HTTP_SERVER.($row['url2'] ? $row['url2'].'/' : '').$row['url']).'?product_id='.$row['id'];}

			if ($key=='price') {
				$csv_doc.= intval($row[$value]).';';
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
	 header ( "Content-Disposition: attachment; filename=export.csv" );

	 echo $csv_doc;


mysql_close($conn);

 ?>
