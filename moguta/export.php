<?php 
error_reporting(E_ALL);
ini_set('display_errors', '1');

ini_set('memory_limit', '500M');
set_time_limit(60*59);

define('HOST' , "localhost");
define('USER' , "agroupby_klimati");
define('PASSWORD' , "6382207");
define('NAME_BD' , "agroupby_klimatika");
define("TABLE_PREFIX", "mg_");

 // Параметры базы 
$charset='utf8';

 // Пишем запросик для канала 		
// if (isset($_GET['active']))
// {
// 	if ($_GET['active']==1) {$filter=" WHERE p.status=1";} else {$filter="";}
// } else {$filter="";}

	$query="	
	SELECT p.id, p.title, p.price, p.activity AS status , p.url, p.image_url, cat.title AS category, cat.url AS cat_url, cat.parent_url AS cat_parent, p.code as art, parent_cat.title AS parent
	FROM  `mg_product` AS p
	JOIN  `mg_category` AS cat ON cat.id = p.cat_id
	JOIN  `mg_category` AS parent_cat ON parent_cat.id = cat.parent
	";

	

 // Имя таблицы 
$TableName="export.csv";

 // Настройки выходного файла 
$Fields=array(
	/* Формат заполнения:  "Название столбца"	=>	"Имя поля в запросе" */
	/* !!! --- В каком порядке здесь идут столбцы, в таком (слева на право) они и будут на выходе --- !!! */
	"id"				=>	"id",
	"Производитель"			=>	"category",
	"Категория"			=>	"parent",
	"Модель"		=>	"title",
	"Цена"				=>	"price",
	"Активность"			=>	"status",
	"Ссылка на товар"				=>	"url",
	"Рисунок"			=>	"image_url",
	"Артикул"			=>	"art"

);



// Подключаем класс для работы с excel
// require_once('./Classes/PHPExcel.php');
// Подключаем класс для вывода данных в формате excel
// require_once('./Classes/PHPExcel/Writer/Excel5.php');
 // Создаем соединение 
$conn=mysql_connect(HOST, USER, PASSWORD) or die ("Не могу создать соединение");
// Ставим чарсет на UTF8
mysql_query("SET character_set_results = '$charset', character_set_client = '$charset', character_set_connection = '$charset', character_set_database = '$charset', character_set_server = '$charset'", $conn);
// Выбираем базу данных. Если произойдет ошибка - вывести ее 
mysql_select_db(NAME_BD) or die (mysql_error());

/* Инициализируем Excel 
$xls = new PHPExcel();				// Создаем объект класса PHPExcel
$xls->setActiveSheetIndex(0);		// Устанавливаем индекс активного листа
$sheet = $xls->getActiveSheet();	// Получаем активный лист
$sheet->setTitle($TableName);		// Подписываем лист
for ($i=0; $i < sizeof($XLSFields); $i++) {if ($i<=25) {$sheet->getColumnDimension(chr($i+65))->setAutoSize(true);} }  // Устанавливаем автоширину
*/

/* Записываем оглавление */
$i=0;
$csv_doc="";
foreach ($Fields as $key => $value) 
	{$csv_doc.='"'.iconv($charset, 'cp1251', $key)."\";";}

// echo "<h1 style='color:red;'>csv_doc :</h1><pre>";print_r($csv_doc);echo "</pre><hr>";die();

$csv_doc=rtrim($csv_doc,";")."\n";

$result=mysql_query($query); 


if (mysql_errno()) { 
  echo "MySQL error ".mysql_errno().": ".mysql_error()."\n<br>When executing:<br>\n$query\n<br>"; 
  
} 



while ($row = mysql_fetch_array($result)) {$arr[]=$row;}

// echo "<h1 style='color:red;'>DATA :</h1><pre>";print_r($arr);echo "</pre><hr>";exit();
$i=1;
foreach ($arr as $key => $row) 
{

	//  Раскидываем по полям всё что нужно 
	foreach ($Fields as $key => $value) 
	{
		// if (isset($row[$value])) 
		{
			if ( ($value=="image_url") && ($row['image_url']!="") )
			{
				if (strpos($row['image_url'], '|') !== false) {
					$row['image_url'] = substr($row['image_url'], 0, strpos($row['image_url'], '|'));
				}
				$row[$value]="http://home.agroup.by/uploads/".trim($row['image_url'],"/");
			}

		if ($value=="url")
			{$row[$value]="http://home.agroup.by/".trim($row['cat_parent'],"/").'/'.trim($row['cat_url'],"/").'/'.trim($row['url'],"/");}
		
		$csv_doc.= '"'.iconv($charset, 'cp1251', $row[$value]).'";';
		}
	}
	$csv_doc=rtrim($csv_doc,";")."\n";
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