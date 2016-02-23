<?	
	define('ZMS_SITE_UPDATER','http://zoomos.clu.by/get.php');

	if(isset($_GET['clear_zms']) and $_GET['clear_zms'] == 'Y'){
		if($GLOBALS['USER']->IsAdmin()){
			define('ZMS_NEED_UPDATE',true);
		}
	}
	
	include('params.php');
	include('version.php');

	class ZmsModule {
		public static function update(){
			if(static::isNeedUpdate()){
				$link = ZMS_SITE_UPDATER.'?name='.$_SERVER['HTTP_HOST'].'&type=update&key='.static::getKey();
				$a = json_decode(file_get_contents($link));
				if(is_object($a)){
					if($a->result == 1 and ((!defined('ZMS_VERSION') or $a->version != ZMS_VERSION) or defined('ZMS_NEED_UPDATE'))){
						foreach($a->files as $name => $content){
							if($content != false){
								$file = fopen($_SERVER['DOCUMENT_ROOT'].'/'.$name, "w");
                                if(substr($name,-11) != 'include.php'){
                                    $content = str_replace("%DOCUMENT_ROOT%",$_SERVER['DOCUMENT_ROOT'],$content);
                                }
								fwrite($file, $content);
								fclose($file);
							}
						}
					}
				}
				$file = fopen($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/zoomos/version.php', "w");
				fwrite($file, '<?define("ZMS_LAST",'.(time()+60*60*24).');?>');
				fclose($file);
				
			}
		}
		private static function isNeedUpdate(){
			if(defined('ZMS_NEED_UPDATE') and ZMS_NEED_UPDATE == true) return true;
			if(!defined('ZMS_UPDATE') or ZMS_UPDATE != false){
				if(!defined('ZMS_LAST') or ZMS_LAST < time()){
					return true;
				}
			}
			return false;
		}
		private static function getKey(){
			return md5('ZMS');
		}
	}
	
	\ZmsModule::update();
	include('main.php');
?>