<?php 


//error_reporting(E_ALL | E_STRICT) ;
//ini_set('display_errors', 'On');

require "config.php";

 // Параметры базы 
$charset='utf8';



	$query="	SELECT p.product_id AS id,  m.name as vendor, p.model, coalesce(ps.price, p.price ) as price,   
			       p.image AS image_url, p.status, cat.name AS category, ua.keyword as url, ua2.keyword as url2, pdescr.name as full_name, concat_ws(' ', pdescr.name, m.name, p.model) as full_model,
			       p.stock_status_id, quantity 
				FROM  product AS p
				JOIN product_description as pdescr ON pdescr.product_id=p.product_id
				LEFT JOIN product_to_category AS prod_c ON prod_c.product_id = p.product_id
				LEFT JOIN category_description AS cat ON cat.category_id = prod_c.category_id
				LEFT JOIN url_alias as ua ON ua.query=CONCAT('category_id=',prod_c.category_id)
				LEFT JOIN category as cat2 ON prod_c.category_id=cat2.category_id
				LEFT JOIN url_alias as ua2 ON ua2.query=CONCAT('category_id=',cat2.parent_id)
				LEFT JOIN manufacturer AS m ON m.manufacturer_id = p.manufacturer_id
				LEFT JOIN product_special AS ps ON ps.product_id = p.product_id and ps.price > 0 and date_start <= now() and date_end >= now()
				where cat.name not like '%ПРЕДЗАКАЗ%' and pdescr.name not like '%СКИДКА ВСЕМ%'  and quantity > 0
				group by p.product_id 
	";


	// die($query);

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
	"quantity"			=>	"quantity"
	

);



// Подключаем класс для работы с excel
// require_once('./Classes/PHPExcel.php');
// Подключаем класс для вывода данных в формате excel
// require_once('./Classes/PHPExcel/Writer/Excel5.php');
 // Создаем соединение 
$conn=mysql_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD) or die ("Не могу создать соединение");
// Ставим чарсет на UTF8
mysql_query("SET character_set_results = '$charset', character_set_client = '$charset', character_set_connection = '$charset', character_set_database = '$charset', character_set_server = '$charset'", $conn);
// Выбираем базу данных. Если произойдет ошибка - вывести ее 
mysql_select_db(DB_DATABASE) or die (mysql_error());

//echo $query;

$result=mysql_query($query); 


//echo mysql_error();

//$tableResult = mysql_list_tables(DB_DATABASE);
//echo '!'..'!';

//$tableResult=mysql_query('select currency_id, code, value from currency'); 
$tableResult=mysql_query("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS`  WHERE `TABLE_SCHEMA`='".DB_DATABASE."' AND `TABLE_NAME`='product'"); 
while ($row = mysql_fetch_row($tableResult)) {
//	echo $row[0].' '.$row[1].$row[2];
}

$csv_doc = '';

while ($row = mysql_fetch_array($result)) {$arr[]=$row;}

// echo "<h1 style='color:red;'>DATA :</h1><pre>";print_r($arr);echo "</pre><hr>";exit();
$i=1;
$urls=array();
foreach ($arr as $key => $row) 
{
	//  Раскидываем по полям всё что нужно 
	foreach ($Fields as $key => $value) 
		{
			if ($key=="image_url"){$row[$value]="http://XXX.by/image/".$row[$value];}
			if ($key=="url"){$row[$value]=trim("http://XXX.by/".($row['url2'] ? $row['url2'].'/' : '').$row['url']).'?product_id='.$row['id'];}
			if ( ($key=='url') || ($key=='image_url') || $key=='category' || ($key=='model') || ($key=='model_full') || ($key=='full_name') || ($key=='vendor') || $key == 'full_model' ) {
				$csv_doc.= '"'.str_replace('"', '""', iconv($charset, 'cp1251', $row[$value] ) ).'";';
			} 
			else {
				if ($key=='price') {$csv_doc.= intval($row[$value]).';';}
					else {$csv_doc.= $row[$value].';';}
			}
		}
	$csv_doc[strlen($csv_doc)-1]="\n";
}

// Сохраняем лист и предлагаем загрузить 
	// Выводим HTTP-заголовки
	 header ( "Expires: Mon, 1 Apr 1974 05:00:00 GMT" );
	 header ( "Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT" );
	 header ( "Cache-Control: no-cache, must-revalidate" );
	 header ( "Pragma: no-cache" );
	 header ( "Content-type: text/csv" );
	 header ( "Content-Disposition: attachment; filename=$TableName" );

	 // Выводим содержимое файла
	  // $objWriter = new PHPExcel_Writer_Excel5($xls);
	  // $objWriter->save('php://output');
	 echo $csv_doc;

mysql_close($conn);

 ?>