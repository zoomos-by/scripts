<?
	class ZmsApp extends ZmsDefaultApp {
		public static function clearCache(){
			\ZmsJson::clearCache();
			static::allPriceUpdate();
		}
		public static function getBlockID(){
			return ZMS_IBLOCK_ID;
		}
		public static function exportFile(){
			$priceId = static::getBasePrice();
			
			$db = \CIBlockElement::GetList(Array("SORT"=>"ASC"),Array('IBLOCK_ID'=>1),false,false,Array('ID','IBLOCK_ID','NAME'));
			$brands['0'] = '';
			while($ar = $db->fetch()){
				$brands[$ar['ID']] = $ar['NAME'];
			}
			$lDb = \CIBlockSection::GetList(array(),array('IBLOCK_ID'=>static::getBlockID()),false,array('IBLOCK_ID','ID','NAME'));
			while($s = $lDb->fetch()) $sections[$s['ID']] = $s['NAME'];
			$db = \CIBlockElement::GetList(Array("SORT"=>"ASC"),Array('IBLOCK_ID'=>static::getBlockID()),false,false,Array('ID','IBLOCK_ID','NAME','IBLOCK_SECTION_ID','CODE','CATALOG_QUANTITY','DETAIL_PAGE_URL','CATALOG_GROUP_'.$priceId,'PREVIEW_PICTURE','PROPERTY_MANUFACTURER','DETAIL_PICTURE'));
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
				.'"'.str_replace('"','',$sections[$line['IBLOCK_SECTION_ID']]).'"'.$a
				.'"'.str_replace('"','',$brands[(int)$line['PROPERTY_MANUFACTURER_VALUE']]).'"'.$a
				.'"'.str_replace('"','',$line['NAME']).'"'.$a
				.static::getConvertCurrency($line['CATALOG_PRICE_'.$priceId],$line['CATALOG_CURRENCY_'.$priceId]).$a
				.$line['CATALOG_QUANTITY'].$a
				.$link.$a
				.'http://'.$_SERVER['HTTP_HOST'].CFile::GetPath($line['PREVIEW_PICTURE']).''.$d;
				fputs($file, $t);
			}
			fclose ($file);
			static::fileTransfer($fileName);
		}
	}
	class ZmsInterface extends ZmsDefaultInterface {}
	class ZmsSync extends ZmsDefaultSync {}
?>