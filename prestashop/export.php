<?php 
require "config/settings.inc.php";

 // Параметры базы 
$charset='utf8';

$filter="";
 // Пишем запросик для канала 		
 if (isset($_GET['active']) && $_GET['active']==1) {
 	$filter=" WHERE (p.available_for_order=1 or p.online_only = 1) "; 
}

	$query="	
		select p.id_product as id, cl.name as category, m.name as vendor, pl.name as model, p.price, p.active as status, 
				cl.link_rewrite as clr, pl.link_rewrite as plr , ean13, img.id_image as image_url, p.reference, p.ean13
		       from "._DB_PREFIX_."product p
		       left join "._DB_PREFIX_."product_lang pl on p.id_product = pl.id_product and pl.id_lang = 1
		       left join "._DB_PREFIX_."category_lang cl on p.id_category_default = cl.id_category and pl.id_lang = 1
		       left join "._DB_PREFIX_."manufacturer m on p.id_manufacturer = m.id_manufacturer
		       left join "._DB_PREFIX_."image as img on p.id_product = img.id_product
		       
		       ";
		       
		$query .= $filter;       
		       
		$query .= " group by p.id_product order by category, vendor, model
	";

	

 // Имя таблицы 
$TableName="export.csv";

 // Настройки выходного файла 
$Fields=array(
	/* Формат заполнения:  "Название столбца"	=>	"Имя поля в запросе" */
	/* !!! --- В каком порядке здесь идут столбцы, в таком (слева на право) они и будут на выходе --- !!! */
	"id"				    =>	"id",
	"category"			=>	"category",
	"vendor"		    =>	"vendor",
	"model"		      =>	"model",
	"price"				  =>	"price",
	"status"			  =>	"status",
	"url"				    =>	"url",
	"image_url"			=>	"image_url",
	"reference"			=>	"reference",
	"ean13"				  =>	"ean13"

);

// Создаем соединение 
$conn=mysql_connect(_DB_SERVER_, _DB_USER_, _DB_PASSWD_) or die ("Не могу создать соединение");
// Ставим чарсет на UTF8
mysql_query("SET character_set_results = '$charset', character_set_client = '$charset', character_set_connection = '$charset', character_set_database = '$charset', character_set_server = '$charset'", $conn);
// Выбираем базу данных. Если произойдет ошибка - вывести ее 
mysql_select_db(_DB_NAME_) or die (mysql_error());

/* Записываем оглавление */
$i=0;
$csv_doc="";
foreach ($Fields as $key => $value) {$csv_doc.=$key.";";}
$csv_doc[strlen($csv_doc)-1]="\n";

$result=mysql_query($query); 

if (mysql_errno()) { 
  echo "MySQL error ".mysql_errno().": ".mysql_error()."\n<br>When executing:<br>\n$query\n<br>"; 
} 

while ($row = mysql_fetch_array($result)) {$arr[]=$row;}

$i=1;
$urls=array();
foreach ($arr as $key => $row) {

	$reduct_query="SELECT * FROM "._DB_PREFIX_."specific_price WHERE id_product =".$row['id'];
	$reduct_result=mysql_query($reduct_query); 
	while ($reduct_row = mysql_fetch_array($reduct_result))
	{
		if ((int)$reduct_row['reduction']>0)
			{$row['price']-=(int)$reduct_row['reduction'];}

		if ((int)$reduct_row['price']>0)
			{$row['price']=(int)$reduct_row['price'];}
	}

	//  Раскидываем по полям всё что нужно 
	foreach ($Fields as $key => $value) 
		{
			if ( ($key=="image_url") && ($row['image_url']!="") ){$row[$value]="http://yourshop.com/".$row['image_url'].'-large_default/'.$row['plr'].".jpg";}
	
			if ($key=="url"){$row[$value]="http://yourshop.com/".$row['clr'].'/'.$row['id'].'-'.$row['plr'].($row['ean13'] ? '-'.$row['ean13'] : '').'.html';}

			if ($key=='price') {
			  $csv_doc.= intval($row[$value]).';';
			} else {
			  $csv_doc.= '"'.str_replace('"', '""', iconv($charset, 'cp1251', $row[$value] )).'";';
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
