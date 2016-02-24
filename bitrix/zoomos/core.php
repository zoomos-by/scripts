<?
	if(isset($_GET[ZMS_SECTION_CODE])) $GLOBALS['R'][ZMS_SECTION_CODE] = $_GET[ZMS_SECTION_CODE];
	if(isset($_GET[ZMS_ELEMENT_CODE])) $GLOBALS['R'][ZMS_ELEMENT_CODE] = $_GET[ZMS_ELEMENT_CODE];

	class ZmsJson {
		public static function get($link='',$cache=true,$filter=false){
			
			if($filter) 
				$link = ZMS_JSON_LINK.$link.'&key='.ZMS_KEY;
			else 
				$link = ZMS_JSON_LINK.$link.'?key='.ZMS_KEY;
			
			#if($GLOBALS['USER']->isAdmin()) echo $link;
			
			if($cache){
				$cache_id = md5(serialize($link));
				$cache_dir = 'zoomos';
				$obCache = new \CPHPCache;
				if($obCache->initCache(ZMS_CACHE_JSON, $cache_id, $cache_dir)){
					$array = $obCache->getVars();
				}elseif($obCache->startDataCache()){
					$json = file_get_contents($link);
					$array = json_decode($json);
					$obCache->endDataCache($array);
				}
				\ZmsApp::log($link);
			}else{
				$json = file_get_contents($link);
				$array = json_decode($json);
			}
			return $array;
		}
		public static function clearCache($directory=false){
			if($directory == false) $directory = $_SERVER['DOCUMENT_ROOT'].'/bitrix/cache';
			if($directory == false and defined('ZMS_CACHE_DIR') and ZMS_CACHE_DIR != false){
				$directory .= '/' . ZMS_CACHE_DIR;
			}
			$dir = opendir($directory);
			if($dir) while(($file = readdir($dir))){
				if(is_file($directory."/".$file)){
					unlink($directory."/".$file);
				}elseif(is_dir($directory."/".$file) && ($file != ".") && ($file != "..")){
					static::clearCache($directory."/".$file);
					rmdir($directory."/".$file);
				}
			}
			closedir($dir);
		}
		public static function getPricelist(){
			return static::get('pricelist');
		}
		public static function getSections(){
			return static::get('categories');
		}
		public static function getSection($id){
			return static::get('category/'.$id.'/offers');
		}
		public static function getFilter($id){
			return static::get('category/'.$id.'/filters');
		}
		public static function getFilterList($id,$p){
			return static::get('category/'.$id.'/filter?'.$p,true,true);
		}
		public static function getItem($id){
			return static::get('item/'.$id);
		}
		public static function getOffer($id){
			return static::get('offer/'.$id);
		}
		public static function getSearch($s){
			return static::get('offers/search?q='.str_replace(' ',"%20",$s),true,true);
		}
	}
?><?
	class ZmsDefaultApp {

		public static function component($nameSpace=false,$name,$tempName){
			if(!$nameSpace || $nameSpace == '') $nameSpace = ZmsInterface::getDefaultNameSpace();
			if($tempName == false) $tempName = '.default';
			$name = explode('/',$name);
			if(is_array($name)) $name = $name[count($name)-1];
			$method = str_replace('.','',$name);
			$method = $nameSpace.'_'.$method;
			
			if(method_exists('ZmsInterface',$method)){
				\ZmsInterface::$method($nameSpace,$name,$tempName);
			}
		}
		public static function pageNotFound(){
			@define("REDIRECT_TO_404", "Y");
			global $APPLICATION;
			$APPLICATION->RestartBuffer();
			@define("ERROR_404", "Y");
			CHTTP::SetStatus("404 Not Found");
		}
        public static function getFlagName($a){
            $file = '__'.$a.'flg';
			$dir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/zoomos/';
            return $dir.$file;
        }
        public static function addFlag($a){
            if(file_exists(static::getFlagName($a))){
                $s = \file_get_contents(static::getFlagName($a));
                if(shell_exec('date "+%y%m%d%H%M%S";') < ($s + 10000)){
                    return false;
                }
                static::removeFlag($a);
            }
            fwrite(fopen(static::getFlagName($a),'w+'), shell_exec('date "+%y%m%d%H%M%S";'));
            return true;
        }
        public static function removeFlag($a){
            unlink(static::getFlagName($a));
        }
		public static function Log($text) {
			$dir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/zoomos/';
			fwrite(fopen($dir . '_' . date('m-d') . '.log', "a"), date('d-m G:i:s') . ' U:' . $GLOBALS['USER'] -> GetID() . ' ' . $text . "\n");
		}
		public static function unlinkLastLog(){
			$list = glob($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/zoomos/'.'*.log');
			if(is_array($list) and count($list)>3){
				$lastFile = false;
				foreach($list as $file){
					if (!isset($time) or $time > filemtime($file)){
						$time = filemtime($file);
						$lastFile = $file;
					}
				}
				if($lastFile) unlink($lastFile);
			}
		}
		public static function isShell(){
			if(defined('ZMS_SHELL')) return ZMS_SHELL;
			if(strlen(shell_exec('echo "it work!";'))>5){
				define('ZMS_SHELL',true);
				return true;
			}
			define('ZMS_SHELL',true);
			return false;
		}
		public static function Shell($filePath){
			if(static::isShell()){
				shell_exec('/usr/bin/php '.$_SERVER['DOCUMENT_ROOT'].$filePath.' > /dev/null & echo $!');
				return true;
			}
			return false;
		}
		public static function setServerLimit(){
			set_time_limit(0);
			ob_implicit_flush();
			ignore_user_abort();
			ini_set('memory_limit', '512M');
            ini_set('allow_url_fopen', '1');
		}
		public static function getTranslite($t){
			return strtolower(\CUtil::translit($t,"ru"));
		}


		public static function addToCatalog($id){
			CModule::IncludeModule("iblock");
			$a = \ZmsJson::getOffer($id);
			$el = new \CIBlockElement;

			$arLoadProductArray = Array(
				"IBLOCK_SECTION_ID" => false,
				"IBLOCK_ID"      => static::getBlockID(),
				"NAME"           => $a->typePrefix . ' ' . $a->vendor->name . ' ' . $a->model,
				'CODE'			 => $a->id,
				"ACTIVE"         => "Y"
			);

			$PRODUCT_ID = $el->Add($arLoadProductArray);
			$arFields = array(
				"ID" => $PRODUCT_ID, 
				"VAT_ID" => 1,
				"VAT_INCLUDED" => "Y",
				'QUANTITY' => 1,
			);
			if(CModule::IncludeModule("catalog")){
				\CCatalogProduct::Add($arFields);
				\CPrice::SetBasePrice($PRODUCT_ID,$a->price,$a->priceCurrency);
			}else{
				$el->update($PRODUCT_ID,array('PREVIEW_TEXT'=>'Цена: ' . $a->price . ' ' . $a->priceCurrency));
			}
			return $PRODUCT_ID;
		}
		public static function addToBasket($id){}
		public static function getBasePrice(){
			if(!isset($GLOBALS['ZMS_DEFAULT_PRICE_ID'])){
				CModule::IncludeModule("catalog");
				if(!class_exists('CCatalogGroup')){
					$GLOBALS['ZMS_DEFAULT_PRICE_ID'] = 1;
				}else{
					$a = \CCatalogGroup::GetBaseGroup();
					$GLOBALS['ZMS_DEFAULT_PRICE_ID'] = $a['ID'];
				}
			}
			return $GLOBALS['ZMS_DEFAULT_PRICE_ID'];
		}
        
        public static function getImageById($id){
            if($id == false) return '';
            $a = \CIBlockElement::GetList(Array("SORT"=>"ASC"),Array('ID'=>$id),false,array('nTopCount'=>1),Array('ID','CODE','IBLOCK_ID','XML_ID'))->fetch();
            return 'http://export.zoomos.by/api/img/item/'.$a['CODE'].'/0';
        }
        public static function getDetailText($id){
            $i = ZmsJson::getItem($id);
			
			return $i->fullDescriptionHTML;
        }
        public static function getImage($id){
            return 'http://export.zoomos.by/api/img/item/'.$id.'/0';
        }
        public static function getImages($id){
            $i = ZmsJson::getItem($id);
            if(isset($i->images)){
                return $i->images;
            }
            return array();
        }
		public static function getBlockID(){
			if(defined('ZMS_IBLOCK_ID') and ZMS_IBLOCK_ID > 0) return ZMS_IBLOCK_ID;
			if(isset($GLOBALS['ZMS_IBLOCK_ID'])) return $GLOBALS['ZMS_IBLOCK_ID'];
			
			$ZMS_TYPE = 'zms_type_block';
			$ZMS_IBLOCK_CODE = 'zms_catalog_'.SITE_ID;

			$ZOOMOS_BLOCK = \CIBlock::GetList(Array("SORT"=>"ASC"),Array('IBLOCK_TYPE_ID'=>$ZMS_TYPE,'CODE'=>$ZMS_IBLOCK_CODE))->fetch();
			if($ZOOMOS_BLOCK == false){
				$ZOOMOS_BLOCK_TYPE = \CIBlockType::GetList(Array(),Array('ID'=>$ZMS_TYPE))->fetch();
				if($ZOOMOS_BLOCK_TYPE == false){
					$obBlocktype = new \CIBlockType;
					$obBlocktype->Add(Array(
						'ID'=> $ZMS_TYPE,
						'SECTIONS'=>'Y',
						'IN_RSS'=>'N',
						'SORT'=>100,
						'LANG'=>Array(
							LANGUAGE_ID => Array(
								'NAME'=>'ZMS Catalog',
								'SECTION_NAME'=>'Sections',
								'ELEMENT_NAME'=>'Products'
							)
						)
					));
				}
				$arFields = Array(
				  "ACTIVE" => 'Y',
				  "NAME" => 'ZOOMOS site '.SITE_ID,
				  "CODE" => $ZMS_IBLOCK_CODE,
				  "LIST_PAGE_URL" => '/catalog/',
				  "DETAIL_PAGE_URL" => '/catalog/',
				  "IBLOCK_TYPE_ID" => $ZMS_TYPE,
				  "SITE_ID" => Array(SITE_ID),
				  "SORT" => 100,
				  "GROUP_ID" => Array("2"=>"R")
				);
				$ib = new \CIBlock;
				$GLOBALS['ZMS_IBLOCK_ID'] = $ib->Add($arFields);
				\CCatalog::Add(array('IBLOCK_ID'=>$GLOBALS['ZMS_IBLOCK_ID'],'YANDEX_EXPORT'=>'N'));
			}else{
				$GLOBALS['ZMS_IBLOCK_ID'] = $ZOOMOS_BLOCK['ID'];
			}
			return $GLOBALS['ZMS_IBLOCK_ID'];
		}
		

		### CURRENCY
		public static function getCurrency(){
			if(!isset($GLOBALS['ZMS_BASE_CURRENCY'])){
				\CModule::IncludeModule("iblock");
				\CModule::IncludeModule("catalog");
				\CModule::IncludeModule("currency");

				$GLOBALS['ZMS_BASE_CURRENCY'] = \CCurrency::GetBaseCurrency();
			}

			return $GLOBALS['ZMS_BASE_CURRENCY'];
		}
		public static function getConvertCurrency($val,$cur='BYR'){
			$base = static::getCurrency();
			if($cur == $base) return $val;
 			return CCurrencyRates::ConvertCurrency($val, $cur, $base);
		}
		public static function getCurrencyFormat($a, $b){
			if(!function_exists('CurrencyFormat')){
				return $a;
			}
			return \CurrencyFormat($a,$b);
		}
		

		public static function allPriceUpdate(){
			\ZmsSync::offersUpdate();
		}
		public static function fileTransfer($file) {
			if (file_exists($file)) {
				if (ob_get_level())
					ob_end_clean();
				header('X-SendFile: ' . realpath($file));
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename=' . basename($file));
				readfile($file);
				exit;
			}
		}
		public static function exportFile(){
			$result = \ZmsJson::get('pricelist');

			$fileName = $_SERVER['DOCUMENT_ROOT'].'/_export.csv';
			$file = fopen ($fileName,"w+");
			$a = ';';
			$d = "\r\n";
			$t .= 'id;category;vendor;model;price;status;url;image_url'.$d;
			fputs ($file, $t);
			foreach($result as $line){
				if(!empty($line->vendor->name)){
					$t = '';
					$link = 'http://'.$_SERVER['HTTP_HOST'].'/catalog/'.$line->category->id.'/'.$line->id.'.html';
					$t.= $line->id.$a
					.'"'.str_replace('"','',$line->category->name).'"'.$a
					.'"'.str_replace('"','',$line->vendor->name).'"'.$a
					.'"'.str_replace('"','',$line->model).'"'.$a
					.$line->price.$a
					.$line->status.$a
					.$link.$a
					.$line->image.$d;
					fputs($file, $t);
				}
			}
			fclose ($file);
			static::fileTransfer($fileName);
		}
        
		public static function exportExtFile(){
			$priceId = static::getBasePrice();
            
			$lDb = \CIBlockSection::GetList(array(),array('IBLOCK_ID'=>PRODUCTS_BLOCK_ID),false,array('IBLOCK_ID','ID','NAME'));
			while($s = $lDb->fetch()) $sections[$s['ID']] = $s['NAME'];
			
			//'IBLOCK_ID'=>static::getBlockID()
			$db = \CIBlockElement::GetList(Array("SORT"=>"ASC"),Array('IBLOCK_ID'=>PRODUCTS_BLOCK_ID),false,false,Array('ID','IBLOCK_ID','NAME','IBLOCK_SECTION_ID','CODE','CATALOG_QUANTITY','DETAIL_PAGE_URL','CATALOG_GROUP_'.$priceId,'PREVIEW_PICTURE','PROPERTY_MANUFACTURER','DETAIL_PICTURE'));
			$fileName = $_SERVER['DOCUMENT_ROOT'].'/_export.csv';
			$file = fopen ($fileName,"w+");
			$a = ';';
			$d = "\r\n";
			$t .= 'id;category;vendor;model;price;status;url;image_url'.$d;
			fputs ($file, $t);
			while($ob = $db->GetNextElement()){
				$line = $ob->GetFields();
				$line['CATALOG_QUANTITY'] = ($line['CATALOG_QUANTITY'] > 0)?1:0;
				if($line['PREVIEW_PICTURE'] == false) $line['PREVIEW_PICTURE'] = $line['DETAIL_PICTURE'];
				$t = '';
				$link = 'http://'.$_SERVER['HTTP_HOST'].$line['DETAIL_PAGE_URL'];
				$t .= $line['ID'].$a
				.'"'.$sections[$line['IBLOCK_SECTION_ID']].'"'.$a
				.'"'.$brands[(int)$line['PROPERTY_MANUFACTURER_VALUE']].'"'.$a
				.'"'.str_replace('"','',$line['NAME']).'"'.$a
				.static::getConvertCurrency($line['CATALOG_PRICE_'.$priceId],$line['CATALOG_CURRENCY_'.$priceId]).$a
				.$line['CATALOG_QUANTITY'].$a
				.$link.$a
				.'http://'.$_SERVER['HTTP_HOST'].\CFile::GetPath($line['PREVIEW_PICTURE']).''.$d;
				fputs($file, $t);
			}
			fclose ($file);
			static::fileTransfer($fileName);
		}
		public static function clearCache(){
			static::setServerLimit();
			static::unlinkLastLog();
			\ZmsJson::clearCache();
			if(defined('ZMS_GLOBALS_EXT') and ZMS_GLOBALS_EXT){
				if(!\ZmsSync::doIt()){ //InBackground
					\ZmsSync::doIt();
                    #static::allPriceUpdate();
				}
			}
		}
	}
	
	/*class BXZoomCached {
	}*/

	class ZmsDefaultSync {
		public static function doIt(){
			if(defined('ZMS_GLOBALS_BASE') and ZMS_GLOBALS_BASE){
				static::sectionsUpdate();
			}
			static::offersUpdate();
            \ZmsApp::removeFlag('sync');
		}
		public static function doItInBackground(){
            if(\ZmsApp::addFlag('sync')){
                return \ZmsApp::Shell('/bitrix/modules/zoomos/_sync.php');
            }
        }
		public static function offersUpdate(){
			CModule::IncludeModule("iblock");
			CModule::IncludeModule("catalog");
			CModule::IncludeModule("sale");

			$priceId = ZmsApp::getBasePrice();
			
			$db = \CIBlockElement::GetList(Array("SORT"=>"ASC"),Array('IBLOCK_ID'=>PRODUCTS_BLOCK_ID),false,false,Array('ID','XML_ID','IBLOCK_ID'));
			
			echo 'offersUpdate start<br/>';
			
			while($line = $db->fetch()){
				$arBase[$line['ID']] = array(  //$line[ZMS_XML_ID]
					'ID' => $line['ID']
				);
			}
			
			//print_r($arBase);
			
			foreach(\ZmsJson::getPricelist() as $a){
				if(isset($arBase[$a->shopsId])){
				
					echo 'update '.$a->shopsId,', price = '.$a->price.', <br/>';
				
					\CPrice::SetBasePrice($arBase[$a->shopsId]['ID'],$a->price,$a->priceCurrency);
					\CCatalogProduct::Update($arBase[$a->shopsId]['ID'],array('QUANTITY'=>1));
					unset($arBase[$a->shopsId]);
				}
			}
			
			echo 'not found:<br/>';
			print_r($arBase);
			
			foreach((array)$arBase as $a){
				\CCatalogProduct::Update($a['ID'],array('QUANTITY'=>0));
			}
			echo '<br/>';
			echo 'offersUpdate finish<br/>';
		}
		public static function sectionsUpdate(){
			CModule::IncludeModule("iblock");
			CModule::IncludeModule("catalog");

			$bxDBSections = \CIBlockSection::GetList(Array(),Array('IBLOCK_ID'=>ZmsApp::getBlockId()));
			$bxSections = array();
			while($bxSection = $bxDBSections->fetch()){
				$bxSections[$bxSection['XML_ID']] = $bxSection;
			}
			$sections = ZmsJson::getSections();
			static::sectionsListUpdate($sections,$bxSections);
		}
		public static function sectionsListUpdate($sections,$bxSections){
			$arParams = array("replace_space"=>"-","replace_other"=>"-");
			foreach((array)$sections as $section){
				if(!isset($bxSections[$section->id])){
					$bSection = new \CIBlockSection;

					$code = $section->id;
					#$code = Cutil::translit($section['NAME'],"ru",$arParams);

					$arFields = Array(
						"ACTIVE" => 'Y',
						"IBLOCK_SECTION_ID" => (int)$bxSections[$section->parentId]['ID'],
						"IBLOCK_ID" => ZmsApp::getBlockId(),
						"NAME" => $section->name,
						"XML_ID" => $section->id,
						'CODE' => $code,
						"SORT" => 100
					);
					$bxSections[$section->id]['ID'] = $bSection->Add($arFields);
					#if(!empty($bSection->LAST_ERROR)) ZoomosApi::call()->ERRORS[] = $bSection->LAST_ERROR;
				}
				if(isset($section->children) and count($section->children)>0){
					static::sectionsListUpdate($section->children,$bxSections);
				}
				static::elementsUpdate($bxSections[$section->id]['ID'],$section->id);
				#static::filter($bxSections[$xml]['ID'],str_replace('ZMS_','',$xml));
			}
		}
		public static function elementsUpdate($sect,$xml){
			$dbElements = CIBlockElement::getList(array(),Array('IBLOCK_ID'=>ZmsApp::getBlockId(),'SECTION_ID'=>$sect),false,false,array('ID','DETAIL_PICTURE','IBLOCK_ID','XML_ID'));
			$bxList1 = array();
			while($element = $dbElements->fetch()){
				$bxList1[$element['XML_ID']] = $element;
			}
			$list = ZmsJson::getSection($xml);
			
			foreach($list as $e){
				if(!isset($bxList1[$e->id])){
					$name = $e->typePrefix . ' ' . $e->vendor->name . ' ' . $e->model;
					$code = $e->id;
                    $img = false;
                    
					$el = new \CIBlockElement;
					$arLoadProductArray = Array(
						"IBLOCK_SECTION_ID" => $sect,
						"IBLOCK_ID" => ZmsApp::getBlockId(),
						"XML_ID" => $e->id,
						"NAME" => $name,
						"ACTIVE" => "Y",
						"CODE" => $code,
						'DETAIL_PICTURE' => $img,
						'PREVIEW_PICTURE' => $img,
					);

					$idElement = $el->Add($arLoadProductArray);
					\CCatalogProduct::Add(array('ID'=>$idElement,'QUANTITY'=>$element['QUANTITY']));
				}
			}
		}
	}

?><?

	class ZmsDefaultInterface {
		
		public static $__folder = '';
		
		public static function getDefaultNameSpace(){
			return 'bitrix';
		}

		public static function bitrix_catalogSections(){
			$arResult['SECTIONS'] = \ZmsJson::getSections();
			
			include(static::getTempPath($nameSpace,$name,$tempName));
		}
		
		public static function bitrix_componentTemp($nameSpace=false,$name,$tempName){
			include(static::getTempPath($nameSpace,$name,$tempName));
		}
		
		
		public static function bitrix_menuRecursive($a,$b=false){
			foreach($a as $i){
				$isParent = (isset($i->children))?1:'';
				$depthLevel = ($b == false)?1:($b['DEPTH_LEVEL']+1);
				$itemsCount = ($i->itemsCount > 0)?$i->itemsCount:false;
		
				$m = array(
					'TEXT' => $i->name,
					'LINK' => '/catalog/'.$i->id.'/',
					'SELECTED' => '',
					'DEPTH_LEVEL' => $depthLevel,
					'IS_PARENT' => $isParent,
					'PARAMS'=> array(
						'ELEMENT_CNT' => $itemsCount,
					)
				);
				$list[] = $m;
				if(isset($i->children)){
					foreach(static::bitrix_menuRecursive($i->children,$m) as $d){
						$list[] = $d;
					}
				}
			}
			
			return $list;
		}
		
		public static function bitrix_menuExtRecursive($a,$b=false,$maxLvl=false){
			foreach($a as $i){
				$isParent = (isset($i->children))?1:'';
				$depthLevel = ($b == false)?1:($b['3']['DEPTH_LEVEL']+1);
				if($maxLvl > 0 and $depthLevel > $maxLvl){
					return array();
				}
				$itemsCount = ($i->itemsCount > 0)?$i->itemsCount:false;
		
				$m = array(
					'0' => $i->name,
					'1' => '/catalog/'.$i->id.'/',
					'2' => array(),
					'3' => array(
						'SELECTED' => '',
						'DEPTH_LEVEL' => $depthLevel,
						'IS_PARENT' => $isParent,
						'ELEMENT_CNT' => $itemsCount,
					)
				);
				$list[] = $m;
				if(isset($i->children)){
					foreach(static::bitrix_menuExtRecursive($i->children,$m,$maxLvl) as $d){
						$list[] = $d;
					}
				}
			}
			
			return $list;
		}
		
		public static function bitrix_menu($nameSpace=false,$name,$tempName){
			
			$arResult = static::bitrix_menuRecursive(\ZmsJson::getSections());
	
			global $APPLICATION;
			include(static::getTempPath($nameSpace,$name,$tempName));
		}
		
		public static function bitrix_catalogSection($nameSpace=false,$name,$tempName){
			$id = $_REQUEST[ZMS_SECTION_CODE];

			$arResult = array(
				'SECTION' => array(),
				'PRICES' => array(
					Array('ID' => \ZmsApp::getBasePrice(), 'TITLE' => 'Розница', 'SELECT' => 'CATALOG_GROUP_'.\ZmsApp::getBasePrice(), 'CAN_BUY' => 1 )
				),
				'ITEMS' => array(), 
				'ITEMS_COUNT' => 1,
				'NAV_STRING' => '',
				'SORT_LINKS' => array(),
				'SHOW_NUMBERS' => array(),
			);
			$arParams["SHOW_NUMBERS"] = array();
			$arParams["PAGE_ELEMENT_COUNT"] = 100000;
			$get = array();
			$z = false;

			if(isset($_GET['f']['MIN_PRICE']) and $_GET['f']['MIN_PRICE'] > 0){
				$get[] = 'priceMin='.$_GET['f']['MIN_PRICE'].'';
			}
			if(isset($_GET['f']['MAX_PRICE']) and $_GET['f']['MAX_PRICE'] > 0){
				$get[] = 'priceMax='.$_GET['f']['MAX_PRICE'].'';
			}
			if(isset($_GET['f']['1'])){
				$get[] = 'vendorId='.implode(',',$_GET['f']['1']);
			}
			foreach((array)$GLOBALS['ZMS_FILTER_GET'] as $key => $v){
				$get[] = 'f'.$key.'='.implode(',', $v);
			}
				
			if($get != false){
				$z = \ZmsJson::getFilterList($id,implode('&',$get));
			}

			if($z === false) $z = \ZmsJson::getSection($id);
			foreach($z as $a){
				if($a->id == false) continue;
				#$b = \ZmsJson::getItem($a->id);
				$p = array();$i = 1;
				$k = '';
				foreach((array)$b->details->featuresBlocks as $a1){
					foreach($a1->features as $a2){
						if($i>4){
							if($i>9) continue;
							$k .= $a2->name . ': ' . $a2->value . '; ';
							$i++;
							continue;
						}
						$p[] = array('NAME'=>$a2->name,'VALUE'=>$a2->value);
						$i++;
					}
				}
				$i = array(
					'ID' => $a->id,
					'NAME' => $a->typePrefix . ' ' . $a->vendor->name . ' ' . $a->model,
					'CODE' => $a->id,
					'IBLOCK_ID' => ZMS_IBLOCK_ID,
					'DETAIL_PAGE_URL' => '/catalog/detail/'.$a->id.'.html',
					'CATALOG_PRICE_ID_5' => 100,
					'CATALOG_QUANTITY' => 1,
					'CATALOG_AVAILABLE' => 'Y',
					'PREVIEW_TEXT' => $k,
					'PREVIEW_PICTURE' => array(
						'SRC' => 'http://export.zoomos.by/api/img/item/'.$a->id.'/main/resize/'.ZMS_SECTION_IMG_SIZE.'.jpg',	  	
					),
					'PRICES' => array(
						array(
							'ID' => \ZmsApp::getBasePrice(),
							'PRINT_VALUE' => \ZmsApp::getCurrencyFormat($a->price,$a->priceCurrency),
						)
					),
				);
				if($a->price != false){
					$arResult['ITEMS'][] = $i;
				}
			}
			$arResult["IN_BASKET"] = array();

			global $APPLICATION;
			include(static::getTempPath($nameSpace,$name,$tempName));
		}
		
		public static function bitrix_catalogElement($nameSpace=false,$name,$tempName){
			$id = $GLOBALS['R'][ZMS_ELEMENT_CODE];
			$a = \ZmsJson::getItem($id);
			if($a->id == false){
				\ZmsApp::PageNotFound();
				return false;
			}
			$o = \ZmsJson::getOffer($id);

			$p = array();
			$images = array();
			foreach($a->images as $img){
				$images[] = $img;
				$p['MORE_PHOTO']['VALUE'][] = $img;
			}

			foreach($a->details->featuresBlocks as $a1){
				foreach($a1->features as $a2){
					$p[$a2->name] = array(
						'NAME' => $a2->name,
						'VALUE' => $a2->value
					);
					$d[$a2->name] = array(
						'NAME' => $a2->name,
						'VALUE' => $a2->value
					);
				}
			}

			$arResult = array(
				'ID' => $a->id,
				'NAME' => $a->typePrefix . ' ' . $a->vendor->name . ' ' . $a->model,
				'DETAIL_PICTURE' => $images['0'],
				'MIDDLE_PICTURE' => $images['0'],
				'PREVIEW_PICTURE' => $images['0'],
				'DETAIL_TEXT' => $a->fullDescriptionHTML.'<br/><br/>'.$a->warrantyInfoHTML,
				'MORE_PHOTO' => $images,
				'CATALOG_AVAILABLE' => 'Y',
				'CATALOG_QUANTITY' => 1,
				'PRICES' => array(
					array(
						'ID' => \ZmsApp::getBasePrice(),
						'PRINT_VALUE' => \ZmsApp::getCurrencyFormat($o->price,$o->priceCurrency),
						'VALUE' => $o->price,
						'DISCOUNT_VALUE' => $o->price,
					)
				),
				'PROPERTIES' => $p,
				'PRODUCT_PROPERTIES' => $d
			);
			
			global $APPLICATION;
			$APPLICATION->AddChainItem($arResult['NAME'],'/catalog/detail/'.$a->id.'/');
			$APPLICATION->setTitle($arResult['NAME']);

			include(static::getTempPath($nameSpace,$name,$tempName));
		}
		
		public static function getTempPath($nameSpace=false,$name,$tempName){
			global $MESS;
			foreach(array(
				$_SERVER['DOCUMENT_ROOT'].'/bitrix/templates/'.SITE_TEMPLATE_ID.'/components/'.$nameSpace.'/'.$name.'/'.$tempName.'/template.php',
				$_SERVER['DOCUMENT_ROOT'].'/bitrix/templates/.default/components/'.$nameSpace.'/'.$name.'/'.$tempName.'/template.php',
				$_SERVER['DOCUMENT_ROOT'].'/bitrix/components/'.$nameSpace.'/'.$name.'/templates/'.$tempName.'/template.php',
			) as $a){
				if(file_exists(str_replace('template.php','lang/'.LANGUAGE_ID.'/template.php',$a))){
					include(str_replace('template.php','lang/'.LANGUAGE_ID.'/template.php',$a));
				}
				if(file_exists($a)){
					if(file_exists(str_replace('template.php','script.js',$a))){
						$GLOBALS['APPLICATION']->AddHeadScript(str_replace($_SERVER['DOCUMENT_ROOT'],'',str_replace('template.php','script.js',$a)));
					}
					if(file_exists(str_replace('template.php','style.css',$a))){
						$GLOBALS['APPLICATION']->SetAdditionalCSS(str_replace($_SERVER['DOCUMENT_ROOT'],'',str_replace('template.php','style.css',$a)));
					}
					
					return $a;
				}
			}
		}
	}
	
?>
