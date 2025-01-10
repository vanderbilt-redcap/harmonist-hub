<?php
namespace Vanderbilt\HarmonistHubExternalModule;
use phpDocumentor\Reflection\Types\Boolean;
use REDCap;

class DataManagement {
    private $module;
    private $pidsArray = [];
    private $isAuthorized = false;
    private $hub_name;
    private $token;

    public function __construct(HarmonistHubExternalModule $module)
    {
        $this->module = $module;
    }

    public function isAuthorizedPage($project_id):bool
    {
        $hub_mapper = $this->module->getProjectSetting('hub-mapper');
        if($hub_mapper != "") {
            $pidsArray = REDCapManagement::getPIDsArray($hub_mapper);
            if ($project_id == $pidsArray['DATADOWNLOADUSERS']) {
                $this->pidsArray = $pidsArray;
                $this->isAuthorized = true;
                return true;
            }
        }
        $this->isAuthorized = false;
        return false;
    }

    public function getPidsArray():array
    {
        return $this->pidsArray;
    }

    public function getSetttingsData($project_id = null):array
    {
        if($project_id == null) {
            $project_id =$this->pidsArray['SETTINGS'];
        }
        $settings = \REDCap::getData($project_id, 'json-array', null)[0];

        if(!empty($settings)){
            $settings = $this->module->escape($settings);
        }else{
            $settings = htmlspecialchars($settings,ENT_QUOTES);
        }

        #Escape name just in case they add quotes
        if(!empty($settings["hub_name"])) {
            $settings["hub_name"] = addslashes($settings["hub_name"]);
        }

        #Sanitize text title and descrition for pages
        $settings = ProjectData::sanitizeALLVariablesFromInstrument($this->module,$project_id,array(0=>"harmonist_text"),$settings);

        if($this->isAuthorized){
            $this->hub_name = $settings['hub_name'];
            self::setAuthorizedTokenSession();
        }

        return $settings;
    }

    public function setAuthorizedTokenSession(){
        session_start();
        $token_session_name = $this->hub_name.$this->pidsArray['PROJECTS'];
        if(defined("USERID") && !empty(getToken(USERID, $this->pidsArray['PEOPLE']))){
            $_SESSION['token'] = array();
            $_SESSION['token'][$token_session_name] = getToken(USERID, $this->pidsArray['PEOPLE']);
            $token = $_SESSION['token'][$token_session_name];
        }else if(array_key_exists('token', $_REQUEST)  && !empty($_REQUEST['token']) && isTokenCorrect($_REQUEST['token'],$this->pidsArray['PEOPLE'])){
            $token = $_REQUEST['token'];
        }else if(!empty($_SESSION['token'][$token_session_name])&& isTokenCorrect($_SESSION['token'][$token_session_name],$this->pidsArray['PEOPLE'])) {
            $token = $_SESSION['token'][$token_session_name];
        }
        if(array_key_exists('token', $_REQUEST)  && !empty($_REQUEST['token']) && isTokenCorrect($_REQUEST['token'],$this->pidsArray['PEOPLE'])) {
            $_SESSION['token'][$token_session_name] = $_REQUEST['token'];
        }
        $this->token = $token;
    }

    public function getToken():string
    {
        return $this->token;
    }
}
?>

