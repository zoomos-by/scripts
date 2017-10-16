<?php 
require "configuration.php";

 // Параметры базы 
$charset='utf8';

 // Пишем запросик для канала 		
if (isset($_GET['active']))
{
	if ($_GET['active']==1) {$filter=" WHERE p.status=1";} else {$filter="";}
} else {$filter="";}

	$query="	
				select item.id, '', item.name as model, elements, item.type, item.alias from jos_zoo_item item where state > 0
	";

 // Имя таблицы 
$TableName="export.csv";

 // Настройки выходного файла 
$Fields=array(
	/* Формат заполнения:  "Название столбца"	=>	"Имя поля в запросе" */
	/* !!! --- В каком порядке здесь идут столбцы, в таком (слева на право) они и будут на выходе --- !!! */
	"id"				=>	"id",
	"category"			=>	"category",
	"model"				=>	"model",
	"price"				=>	"price",
	"status"			=>	"status",
	"url"				=>	"url",
	"image_url"			=>	"image_url"

);

$config = new JConfig();

 // Создаем соединение 
$conn=mysql_connect('localhost', $config->user, $config->password) or die ("Не могу создать соединение");
// Ставим чарсет на UTF8
mysql_query("SET character_set_results = '$charset', character_set_client = '$charset', character_set_connection = '$charset', character_set_database = '$charset', character_set_server = '$charset'", $conn);
// Выбираем базу данных. Если произойдет ошибка - вывести ее 
mysql_select_db($config->db) or die (mysql_error());


$tableResult = mysql_list_tables($config->db);

/* Записываем оглавление */
$i=0;
$csv_doc="";
foreach ($Fields as $key => $value) {$csv_doc.=$key.";";}
$csv_doc[strlen($csv_doc)-1]="\n";


//$query_entries="SELECT p.product_id, c.category_id, p.product_name, p.product_publish, pp.product_price, c.category_name, 
//		concat('http://snoopy.by/components/com_virtuemart/shop_image/product/', p.product_full_image) as product_img_url , product_url
//		FROM klzc5_virtuemart_products p 
//		left join klzc5_k2_tags_xref pc on p.product_id = pc.product_id 
//		left join klzc5_categories c on c.category_id = pc.category_id 
//		left join klzc5_virtuemart_product_prices pp on pp.product_id = p.product_id";
		
$query_entries="

SELECT p.virtuemart_product_id as product_id, c.virtuemart_category_id as category_id, pl.product_name, p.published, pp.product_price, cl.category_name, m.file_url as img_url,
#sku_csv as sku 
concat(cl.slug, '/', pl.slug, '.php') as product_url
FROM ".$config->dbprefix."virtuemart_products p 
left join ".$config->dbprefix."virtuemart_product_categories pc on p.virtuemart_product_id = pc.virtuemart_product_id 
left join ".$config->dbprefix."virtuemart_products_ru_ru pl on pl.virtuemart_product_id = p.virtuemart_product_id 
left join ".$config->dbprefix."virtuemart_categories c on c.virtuemart_category_id = pc.virtuemart_category_id 
left join ".$config->dbprefix."virtuemart_categories_ru_ru cl on cl.virtuemart_category_id = pc.virtuemart_category_id 
left join ".$config->dbprefix."virtuemart_product_prices pp on pp.virtuemart_product_id = p.virtuemart_product_id
left join ".$config->dbprefix."virtuemart_product_medias pm on pm.virtuemart_product_id = p.virtuemart_product_id
left join ".$config->dbprefix."virtuemart_medias m on m.virtuemart_media_id = pm.virtuemart_media_id and file_type = 'product'
group by p.virtuemart_product_id 

";
//cl.slug, '/', 
//left join ".$config->dbprefix."virtuemart_products_ru_ru pl on pl.virtuemart_product_id = p.virtuemart_product_id 
//left join ".$config->dbprefix."virtuemart_categories_ru_ru cl on c.virtuemart_category_id = pc.virtuemart_category_id 


$result=mysql_query($query_entries); 

echo mysql_error();

while ($row = mysql_fetch_array($result)) {$arr[]=$row;}

// echo "<h1 style='color:red;'>DATA :</h1><pre>";print_r($arr);echo "</pre><hr>";exit();
$i=1;
$urls=array();
foreach ($arr as $key => $row) 
{
	$csv_doc.= $row['product_id'].';';
	$csv_doc.= str_replace('"', '""', iconv($charset, 'cp1251', $row['category_name'] )).';';
	$csv_doc.= str_replace('"', '""', iconv($charset, 'cp1251', $row['product_name'] )).';';
	$csv_doc.= $row['product_price'].';';
	$csv_doc.= $row['published'].';';

	$flypage = '';
	//$myURL = 'index.php?option=com_virtuemart&Itemid='.$vmitemid.'&page=shop.product_details&flypage='.$flypage.'&product_id='.$row['product_id'].'&category_id='.$row['category_id'];
	//$product_url = getSiteRoute($myURL);
	
	$product_url = $row['product_url'];
	
	$csv_doc.= 'http://yourshop.com/store/'.$product_url.';';

	$csv_doc.= 'http://yourshop.com/'.$row['img_url'].';';

	if (isset($imgElement['file']) && strlen($imgElement['file']) > 0) {
		$csv_doc.= 'http://yourshop.com/'.$imgElement['file'].';';
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


	function getSiteRoute($url) {
		if (file_exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_sh404sef'.DS.'sh404sef.class.php')) {
			require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_sh404sef'.DS.'sh404sef.class.php');
			
			$sefConfig = shRouter::shGetConfig();
			if (!$sefConfig->Enabled) return $url;
			$sefConfig->shSecEnableSecurity = 0;
			
			require_once(JPATH_ROOT.DS.'components'.DS.'com_sh404sef'.DS.'shCache.php');
			require_once(JPATH_ROOT.DS.'components'.DS.'com_sh404sef'.DS.'shSec.php');
			
			$shRouter = new shRouter();
			
			include_once(JPATH_ROOT.DS.'components'.DS.'com_sh404sef'.DS.'shInit.php');
			
			$uri = $shRouter->build($url);
			$parsed_url = $uri->toString();
			$adminpos = strpos($parsed_url,'/administrator/');
			if ($adminpos === false) {
			} else {
			  $parsed_url = substr($parsed_url,$adminpos+15);
			}
			return $parsed_url;
		}
		else return $url;
	}

 ?>
