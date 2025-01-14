<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use REDCap;

class SecurityHandler
{
    private $module;
    private $project_id;
    private $pidsArray = [];
    private $isAuthorized = false;
    private $hub_name;
    private $token;
    private $settings;
    private $token_session_name;

    public function __construct(HarmonistHubExternalModule $module, $project_id)
    {
        $this->module = $module;
        $this->project_id = $project_id;
        $this->pidsArray = self::getPidsArray();
        $this->settings = self::getSetttingsData();
        $this->token_session_name = $this->hub_name . $this->pidsArray['PROJECTS'];
    }

    public function getTokenSessionName(): string
    {
        return $this->token_session_name;
    }

    public function isAuthorizedPage(): bool
    {
        if ($this->project_id == $this->getPidsArray()['DATADOWNLOADUSERS']) {
            $this->isAuthorized = true;
            return true;
        }
        $this->isAuthorized = false;
        return false;
    }

    public function getPidsArray(): array
    {
        if (empty($this->pidsArray)) {
            $hub_mapper = $this->module->getProjectSetting('hub-mapper');
            if ($hub_mapper !== "") {
                $this->pidsArray = REDCapManagement::getPIDsArray($hub_mapper);
            }
        }
        return $this->pidsArray;
    }

    public function getSetttingsData($project_id = null): array
    {
        if (empty($this->settings)) {
            if ($project_id == null) {
                $project_id = $this->pidsArray['SETTINGS'];
            }
            $settings = REDCap::getData($project_id, 'json-array', null)[0];

            if (!empty($settings)) {
                $settings = $this->module->escape($settings);
            } else {
                $settings = htmlspecialchars($settings, ENT_QUOTES);
            }

            #Escape name just in case they add quotes
            if (!empty($settings["hub_name"])) {
                $settings["hub_name"] = addslashes($settings["hub_name"]);
            }

            #Sanitize text title and descrition for pages
            $this->settings = ProjectData::sanitizeALLVariablesFromInstrument(
                $this->module,
                $project_id,
                [0 => "harmonist_text"],
                $settings
            );

            $this->hub_name = $this->settings['hub_name'];
            if ($this->isAuthorized) {
                $this->token = self::getTokenSession();
            }
        }
        return $this->settings;
    }

    public function getTokenSession(): ?string
    {
        if (!self::retrieveSessionData()) {
            return null;
        }
        if (self::isSessionOut()) {
            return null;
        }
        $token = self::isREDCapUserToken();
        $this->token = !is_null($token) ? $token : self::isUrlTokenCorrect();
        return $this->token;
    }

    public function retrieveSessionData(): bool
    {
        #Retrieve session (with session_start) on other pages
        if (!array_key_exists('token', $_REQUEST) && !array_key_exists(
                'request',
                $_REQUEST
            ) && !empty($_SESSION['token'][$this->token_session_name]) && !array_key_exists('option', $_REQUEST)) {
            #Login page
            return false;
        } else {
            if (empty($_SESSION['token'][$this->token_session_name]) || $this->isAuthorized) {
                session_start();
                return true;
            }
        }
        return false;
    }

    public function isSessionOut(): bool
    {
        if (array_key_exists('sout', $_REQUEST)) {
            unset($_SESSION['token'][$this->token_session_name]);
            unset($_SESSION[$this->token_session_name]);
            $this->token = null;
            $this->token_session_name = null;
            return true;
        }
        return false;
    }

    public function isREDCapUserToken(): ?string
    {
        $this->isAuthorized = self::isAuthorizedPage();
        #We check user first than token to ensure the hub refreshes to that user's account. Just in case someone tries to log in with someone else's token.
        if (
            ($this->isAuthorized && defined("USERID") && !empty(getToken(USERID, $this->pidsArray['PEOPLE'])))
            ||
            (!$this->isAuthorized && defined("USERID") && !array_key_exists('token', $_REQUEST) && !array_key_exists(
                    'request',
                    $_REQUEST
                ) && ((array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'dnd')))
        ) {
            #If it's an Authorized page, user is logged in REDCap and user has a token
            #If it's a NOAUTH page, user is logged in REDCap and token is not in url and is Downloads page
            $_SESSION['token'] = [];
            $_SESSION['token'][$this->token_session_name] = getToken(USERID, $this->pidsArray['PEOPLE']);
            $token = $_SESSION['token'][$this->token_session_name];
            return $token;
        }
        return null;
    }

    public function isUrlTokenCorrect(): ?string
    {
        if (array_key_exists('token', $_REQUEST) && !empty($_REQUEST['token']) && isTokenCorrect(
                $_REQUEST['token'],
                $this->pidsArray['PEOPLE']
            )) {
            #Token is in url and is correct
            $token = $_REQUEST['token'];
            $_SESSION['token'][$this->token_session_name] = $_REQUEST['token'];
            return $token;
        } else {
            if (!empty($_SESSION['token'][$this->token_session_name]) && isTokenCorrect(
                    $_SESSION['token'][$this->token_session_name],
                    $this->pidsArray['PEOPLE']
                )) {
                #Token is in session and is correct
                $token = $_SESSION['token'][$this->token_session_name];
                return $token;
            }
        }
        return null;
    }

    public function getAwsCredentialsServerVars()
    {
        if (file_exists("/app001/credentials/Harmonist-Hub/" . $this->getPidsArray()['PROJECTS'] . "_aws_s3.php")) {
            require_once "/app001/credentials/Harmonist-Hub/" . $this->getPidsArray()['PROJECTS'] . "_aws_s3.php";
        }
    }

    public function getEncryptionCredentialsServerVars()
    {
        if (file_exists("/app001/credentials/Harmonist-Hub/" . $this->getPidsArray()['PROJECTS'] . "_down_crypt.php")) {
            require_once "/app001/credentials/Harmonist-Hub/" . $this->getPidsArray()['PROJECTS'] . "_down_crypt.php";
        }
    }
}

?>

