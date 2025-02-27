<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use REDCap;

class SecurityHandler
{
    const SESSION_TOKEN_STRING = 'token';
    const SESSION_OPTION_STRING = 'option';
    const SESSION_OUT_STRING = 'sout';
    const CREDENTIALS_PATH = "/app001/credentials/Harmonist-Hub/";
    const CREDENTIALS_AWS_FILENAME = "_aws_s3.php";
    const CREDENTIALS_ENCRYPTION_FILENAME = "_down_crypt.php";
    private $module;
    private $projectId;
    private $pidsArray = [];
    private $isAuthorized = false;
    private $hubName;
    private $token;
    private $settings;
    private $tokenSessionName;
    private $requestToken;
    private $requestOption;
    private $hasNoauth;
    private $requestUrl;

    public function __construct(HarmonistHubExternalModule $module, $projectId)
    {
        $this->module = $module;
        $this->projectId = $projectId;
        $this->pidsArray = self::getPidsArray();
        $this->settings = self::getSettingsData();
        $this->tokenSessionName = $this->hubName . $this->pidsArray['PROJECTS'];
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function setRequestToken($requestToken): void
    {
        $this->requestToken = $requestToken;
    }

    public function getRequestUrl(): array
    {
        return $this->requestUrl;
    }

    public function setRequestUrl($requestUrl): void
    {
        $this->requestUrl = $requestUrl;
        self::setHasNoauthOnUrl();
    }

    public function setRequestOption($requestOption): void
    {
        $this->requestOption = $requestOption;
    }

    public function getRequestOption(): ?string
    {
        return $this->requestOption;
    }

    public function getTokenSessionName(): string
    {
        return $this->tokenSessionName;
    }

    public function setHasNoauthOnUrl(): void
    {
        $this->hasNoauth = true;
        if (!array_key_exists('NOAUTH', $this->requestUrl)) {
            $this->hasNoauth = false;
        }
    }

    public function isAuthorizedPage(): bool
    {
        $this->isAuthorized = false;
        if (($this->requestOption == 'dnd' || $this->requestOption == 'lge' || $this->requestOption == '') && !$this->hasNoauth) {
            if ($this->projectId == $this->getPidsArray()['DATADOWNLOADUSERS']) {
                $this->isAuthorized = true;
            }
        }
        return $this->isAuthorized;
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

    public function getSettingsData($projectId = null): array
    {
        if (empty($this->settings)) {
            if ($projectId == null) {
                $projectId = $this->pidsArray['SETTINGS'];
            }
            $settings = REDCap::getData($projectId, 'json-array', null)[0];

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
                $projectId,
                [0 => "harmonist_text"],
                $settings
            );

            $this->hubName = $this->settings['hub_name'];
        }
        if ($this->isAuthorized) {
            $this->token = self::getTokenSession();
        }

        return $this->settings;
    }

    public function getTokenSession(): ?string
    {
        if (!self::retrieveSessionData()) {
            return null;
        }

        if (self::isSessionOut() || $this->logOut) {
            return null;
        }

        $token = self::getREDCapUserToken();
        $this->token = !is_null($token) ? $token : self::getToken();
        $this->logOut = false;
        return $this->token;
    }

    public function retrieveSessionData(): bool
    {
        #Retrieve session (with session_start) on other pages
        if (!array_key_exists(self::SESSION_TOKEN_STRING, $this->requestUrl) && !array_key_exists(
                'request',
                $this->requestUrl
            ) && !empty($_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName]) && !array_key_exists(
                self::SESSION_OPTION_STRING,
                $this->requestUrl
            )) {
            #Login page
            return false;
        } else {
            if (empty($_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName]) || $this->isAuthorized) {
                session_start();
                return true;
            }
        }
        return false;
    }

    public function isSessionOut(): bool
    {
        if (array_key_exists(self::SESSION_OUT_STRING, $this->requestUrl)) {
            self::logOut();
            return true;
        }
        return false;
    }

    public function logOut(): void
    {
        unset($_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName]);
        unset($_SESSION[$this->tokenSessionName]);
        $this->token = null;
        $this->tokenSessionName = null;
        $this->logOut = true;
    }

    public function getREDCapUserToken(): ?string
    {
        $this->isAuthorized = self::isAuthorizedPage();
        #We check user first than token to ensure the hub refreshes to that user's account. Just in case someone tries to log in with someone else's token.
        if (
            ($this->isAuthorized && defined("USERID") && !empty(
                self::getTokenByUserId(
                    USERID
                )
                ))
            ||
            (!$this->isAuthorized && defined("USERID") && !array_key_exists(
                    self::SESSION_TOKEN_STRING,
                    $this->requestUrl
                ) && !array_key_exists(
                    'request',
                    $this->requestUrl
                ) && ((array_key_exists('option', $this->requestUrl) && $this->getRequestOption() === 'dnd')))
        ) {
            #If it's an Authorized page, user is logged in REDCap and user has a token
            #If it's a NOAUTH page, user is logged in REDCap and token is not in url and is Downloads page
            $_SESSION[self::SESSION_TOKEN_STRING] = [];
            $_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName] = self::getTokenByUserId(
                USERID
            );
            $token = $_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName];
            return $token;
        }
        return null;
    }

    public function getToken(): ?string
    {
        if (array_key_exists(
                self::SESSION_TOKEN_STRING,
                $this->requestUrl
            ) && !empty($this->requestUrl[self::SESSION_TOKEN_STRING]) && self::isTokenCorrect(
                $this->requestUrl[self::SESSION_TOKEN_STRING]
            )) {
            #Token is in url and is correct
            $token = $this->requestUrl[self::SESSION_TOKEN_STRING];
            $_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName] = $this->requestUrl[self::SESSION_TOKEN_STRING];
            return $token;
        } else {
            if (!empty($_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName]) && self::isTokenCorrect(
                    $_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName]
                )) {
                #Token is in session and is correct
                $token = $_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName];
                return $token;
            }
        }
        return null;
    }

    function getTokenByUserId($userid): ?string
    {
        $people = REDCap::getData(
            $this->pidsArray['PEOPLE'],
            'json-array',
            null,
            array('access_token'),
            null,
            null,
            false,
            false,
            false,
            "[redcap_name] = '" . $userid . "'"
        )[0];
        if (!empty($people)) {
            return $people['access_token'];
        }
        return null;
    }

    public function isTokenCorrect($token)
    {
        $people = REDCap::getData(
            $this->pidsArray['PEOPLE'],
            'json-array',
            null,
            array('token_expiration_d'),
            null,
            null,
            false,
            false,
            false,
            "[access_token] = '" . $token . "'"
        )[0];
        if (!empty($people)) {
            if (strtotime($people['token_expiration_d']) > strtotime(date('Y-m-d'))) {
                return true;
            }
        }
        return false;
    }

    public function getCredentialsServerVars($type): ?string
    {
        if (file_exists(
            self::CREDENTIALS_PATH . $this->getPidsArray()['PROJECTS'] . constant(
                "self::CREDENTIALS_" . strtoupper($type) . "_FILENAME"
            )
        )) {
            return self::CREDENTIALS_PATH . $this->getPidsArray()['PROJECTS'] . constant(
                    "self::CREDENTIALS_" . strtoupper($type) . "_FILENAME"
                );
        }
        return null;
    }
}

?>
