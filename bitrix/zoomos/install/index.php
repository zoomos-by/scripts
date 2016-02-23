<?php
IncludeModuleLangFile(__FILE__);

if(class_exists("zoomos")) return;
class zoomos extends CModule {
    var $MODULE_ID = "zoomos";
    var $MODULE_VERSION = "1.0.0";
    var $MODULE_VERSION_DATE = "2013-09-19 12:00:00";
    var $MODULE_NAME = 'zoomos';
    var $MODULE_DESCRIPTION = 'zoomos';
    var $MODULE_GROUP_RIGHTS = "Y";

    function zoomos()
    {
        $arModuleVersion = array();

        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));

		$arModuleVersion = array(
			"VERSION" => "1.0.0",
			"VERSION_DATE" => "2013-09-19 12:00:00" );

        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }
        else
        {
            $this->MODULE_VERSION = '1.000';
            $this->MODULE_VERSION_DATE = '0000';
        }

        $this->MODULE_NAME = 'zoomos';
        $this->MODULE_DESCRIPTION = '';
    }

    function DoInstall()
    {
        RegisterModule("zoomos");
    }

    function InstallDB()
    {
        global $DB, $APPLICATION;

        $this->errors = false;
        
        RegisterModule("zoomos");
        return true;
    }

    function InstallFiles()
    {
        return true;
    }

    function InstallEvents()
    {
        return true;
    }

    function DoUninstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION, $step;

        UnRegisterModule("zoomos");
        \CAgent::RemoveModuleAgents('zoomos');
    }

    function UnInstallDB($arParams = Array())
    {
        global $APPLICATION, $DB, $errors;

        $this->errors = false;

        UnRegisterModule("zoomos");

        return true;
    }

    function UnInstallFiles($arParams = array())
    {
        global $DB;
        return true;
    }

    function UnInstallEvents()
    {
        return true;
    }

    function GetModuleRightList()
    {
        global $MESS;
        $arr = array(
            "reference_id" => array("D","R","W"),
            "reference" => array(
                "[D] ".GetMessage("SEO_DENIED"),
                "[R] ".GetMessage("SEO_OPENED"),
                "[W] ".GetMessage("SEO_FULL"))
            );
        return $arr;
    }
}