<?php

/**
 * File: Browser.php
 * Author: Chris Schuld (http://chrisschuld.com/)
 * Last Modified: July 22nd, 2016
 * @version 2.0
 * @package PegasusPHP
 *
 * Copyright (C) 2008-2010 Chris Schuld  (chris@chrisschuld.com)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details at:
 * http://www.gnu.org/copyleft/gpl.html
 *
 *
 * Typical Usage:
 *
 *   $browser = new Browser();
 *   if( $browser->getBrowser() == Browser::BROWSER_FIREFOX && $browser->getVersion() >= 2 ) {
 *    echo 'You have FireFox version 2 or greater';
 *   }
 *
 * User Agents Sampled from: http://www.useragentstring.com/
 *
 * This implementation is based on the original work from Gary White
 * http://apptools.com/phptools/browser/
 *
 */

class Browser
{
    private $_agent = '';
    private $_browser_name = '';
    private $_version = '';
    private $_platform = '';
    private $_os = '';
    private $_is_aol = false;
    private $_is_mobile = false;
    private $_is_tablet = false;
    private $_is_robot = false;
    private $_is_facebook = false;
    private $_aol_version = '';

    const BROWSER_UNKNOWN = 'unknown';
    const VERSION_UNKNOWN = 'unknown';

    const BROWSER_OPERA = 'Opera'; // http://www.opera.com/
    const BROWSER_OPERA_MINI = 'Opera Mini'; // http://www.opera.com/mini/
    const BROWSER_WEBTV = 'WebTV'; // http://www.webtv.net/pc/
    const BROWSER_EDGE = 'Edge'; // https://www.microsoft.com/edge
    const BROWSER_IE = 'Internet Explorer'; // http://www.microsoft.com/ie/
    const BROWSER_POCKET_IE = 'Pocket Internet Explorer'; // http://en.wikipedia.org/wiki/Internet_Explorer_Mobile
    const BROWSER_KONQUEROR = 'Konqueror'; // http://www.konqueror.org/
    const BROWSER_ICAB = 'iCab'; // http://www.icab.de/
    const BROWSER_OMNIWEB = 'OmniWeb'; // http://www.omnigroup.com/applications/omniweb/
    const BROWSER_FIREBIRD = 'Firebird'; // http://www.ibphoenix.com/
    const BROWSER_FIREFOX = 'Firefox'; // http://www.mozilla.com/en-US/firefox/firefox.html
    const BROWSER_ICEWEASEL = 'Iceweasel'; // http://www.geticeweasel.org/
    const BROWSER_SHIRETOKO = 'Shiretoko'; // http://wiki.mozilla.org/Projects/shiretoko
    const BROWSER_MOZILLA = 'Mozilla'; // http://www.mozilla.com/en-US/
    const BROWSER_AMAYA = 'Amaya'; // http://www.w3.org/Amaya/
    const BROWSER_LYNX = 'Lynx'; // http://en.wikipedia.org/wiki/Lynx
    const BROWSER_SAFARI = 'Safari'; // http://apple.com
    const BROWSER_IPHONE = 'iPhone'; // http://apple.com
    const BROWSER_IPOD = 'iPod'; // http://apple.com
    const BROWSER_IPAD = 'iPad'; // http://apple.com
    const BROWSER_CHROME = 'Chrome'; // http://www.google.com/chrome
    const BROWSER_ANDROID = 'Android'; // http://www.android.com/
    const BROWSER_GOOGLEBOT = 'GoogleBot'; // http://en.wikipedia.org/wiki/Googlebot
    const BROWSER_SLURP = 'Yahoo! Slurp'; // http://en.wikipedia.org/wiki/Yahoo!_Slurp
    const BROWSER_W3CVALIDATOR = 'W3C Validator'; // http://validator.w3.org/
    const BROWSER_BLACKBERRY = 'BlackBerry'; // http://www.blackberry.com/
    const BROWSER_ICECAT = 'IceCat'; // http://en.wikipedia.org/wiki/GNU_IceCat
    const BROWSER_NOKIA_S60 = 'Nokia S60 OSS Browser'; // http://en.wikipedia.org/wiki/Web_Browser_for_S60
    const BROWSER_NOKIA = 'Nokia Browser'; // * all other WAP-based browsers on the Nokia Platform
    const BROWSER_MSN = 'MSN Browser'; // http://explorer.msn.com/
    const BROWSER_MSNBOT = 'MSN Bot'; // http://search.msn.com/msnbot.htm
    const BROWSER_BINGBOT = 'Bing Bot'; // http://en.wikipedia.org/wiki/Bingbot
    const BROWSER_VIVALDI = 'Vivalidi'; // preserving original tag even if misspelled

    const BROWSER_NETSCAPE_NAVIGATOR = 'Netscape Navigator'; // (DEPRECATED)
    const BROWSER_GALEON = 'Galeon'; // (DEPRECATED)
    const BROWSER_NETPOSITIVE = 'NetPositive'; // (DEPRECATED)
    const BROWSER_PHOENIX = 'Phoenix'; // (DEPRECATED)
    const BROWSER_PLAYSTATION = "PlayStation";

    const PLATFORM_UNKNOWN = 'unknown';
    const PLATFORM_WINDOWS = 'Windows';
    const PLATFORM_WINDOWS_CE = 'Windows CE';
    const PLATFORM_APPLE = 'Apple';
    const PLATFORM_LINUX = 'Linux';
    const PLATFORM_OS2 = 'OS/2';
    const PLATFORM_BEOS = 'BeOS';
    const PLATFORM_IPHONE = 'iPhone';
    const PLATFORM_IPOD = 'iPod';
    const PLATFORM_IPAD = 'iPad';
    const PLATFORM_BLACKBERRY = 'BlackBerry';
    const PLATFORM_NOKIA = 'Nokia';
    const PLATFORM_FREEBSD = 'FreeBSD';
    const PLATFORM_OPENBSD = 'OpenBSD';
    const PLATFORM_NETBSD = 'NetBSD';
    const PLATFORM_SUNOS = 'SunOS';
    const PLATFORM_OPENSOLARIS = 'OpenSolaris';
    const PLATFORM_ANDROID = 'Android';
    const PLATFORM_PLAYSTATION = "Sony PlayStation";

    const OPERATING_SYSTEM_UNKNOWN = 'unknown';

    /**
     * Class constructor
     * ONE-FILE CHANGE: automatically sends Accept-CH headers so Chrome/Edge
     * will include full UA-CH on subsequent requests (no plugin edits needed).
     */
    public function __construct($userAgent = "")
    {
        self::sendAcceptCHHeaders(); // one-file opt-in for UA-CH

        $this->reset();
        if ($userAgent != "") {
            $this->setUserAgent($userAgent);
        } else {
            $this->determine();
        }
    }

    /**
     * Reset all properties
     */
    public function reset()
    {
        $this->_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";
        $this->_browser_name = self::BROWSER_UNKNOWN;
        $this->_version = self::VERSION_UNKNOWN;
        $this->_platform = self::PLATFORM_UNKNOWN;
        $this->_os = self::OPERATING_SYSTEM_UNKNOWN;
        $this->_is_aol = false;
        $this->_is_mobile = false;
        $this->_is_tablet = false;
        $this->_is_robot = false;
        $this->_is_facebook = false;
        $this->_aol_version = self::VERSION_UNKNOWN;
    }

    /**
     * Check to see if the specific browser is valid
     */
    function isBrowser($browserName)
    {
        return (0 == strcasecmp($this->_browser_name, trim($browserName)));
    }

    public function getBrowser() { return $this->_browser_name; }
    public function setBrowser($browser) { $this->_browser_name = $browser; }
    public function getPlatform() { return $this->_platform; }
    public function setPlatform($platform) { $this->_platform = $platform; }
    public function getVersion() { return $this->_version; }
    public function setVersion($version) { $this->_version = preg_replace('/[^0-9,.,a-z,A-Z-]/', '', $version); }
    public function getAolVersion() { return $this->_aol_version; }
    public function setAolVersion($version) { $this->_aol_version = preg_replace('/[^0-9,.,a-z,A-Z]/', '', $version); }
    public function isAol() { return $this->_is_aol; }
    public function isMobile() { return $this->_is_mobile; }
    public function isTablet() { return $this->_is_tablet; }
    public function isRobot() { return $this->_is_robot; }
    public function isFacebook() { return $this->_is_facebook; }
    public function setAol($isAol) { $this->_is_aol = $isAol; }
    protected function setMobile($value = true) { $this->_is_mobile = $value; }
    protected function setTablet($value = true) { $this->_is_tablet = $value; }
    protected function setRobot($value = true) { $this->_is_robot = $value; }
    protected function setFacebook($value = true) { $this->_is_facebook = $value; }
    public function getUserAgent() { return $this->_agent; }

    public function setUserAgent($agent_string)
    {
        $this->reset();
        $this->_agent = $agent_string;
        $this->determine();
    }

    public function isChromeFrame()
    {
        return (strpos($this->_agent, "chromeframe") !== false);
    }

    public function __toString()
    {
        return "{$this->getBrowser()}<br/>\n" .
        "{$this->getVersion()}<br/>\n" .
        "{$this->getUserAgent()}<br/>";
    }

    /**
     * Calculate and determine the browser (including platform).
     * NEW: prefer UA-CH first.
     */
    protected function determine()
    {
        $this->checkPlatform();
        if (!$this->checkClientHints()) {
            $this->checkBrowsers();
        }
        $this->checkForAol();
    }

    /**
     * Try UA-CH headers first (full versions for Chrome/Edge/Opera/Vivaldi/Brave).
     * Returns true if it set browser+version so UA parsing can be skipped.
     */
    protected function checkClientHints()
    {
        $chBrands   = isset($_SERVER['HTTP_SEC_CH_UA']) ? $_SERVER['HTTP_SEC_CH_UA'] : null;
        $chMobile   = isset($_SERVER['HTTP_SEC_CH_UA_MOBILE']) ? $_SERVER['HTTP_SEC_CH_UA_MOBILE'] : null;
        $chPlatform = isset($_SERVER['HTTP_SEC_CH_UA_PLATFORM']) ? $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] : null;

        $chFullList = isset($_SERVER['HTTP_SEC_CH_UA_FULL_VERSION_LIST']) ? $_SERVER['HTTP_SEC_CH_UA_FULL_VERSION_LIST'] : null;
        $chPlatVer  = isset($_SERVER['HTTP_SEC_CH_UA_PLATFORM_VERSION']) ? $_SERVER['HTTP_SEC_CH_UA_PLATFORM_VERSION'] : null;
        $chArch     = isset($_SERVER['HTTP_SEC_CH_UA_ARCH']) ? $_SERVER['HTTP_SEC_CH_UA_ARCH'] : null;
        $chBitness  = isset($_SERVER['HTTP_SEC_CH_UA_BITNESS']) ? $_SERVER['HTTP_SEC_CH_UA_BITNESS'] : null;
        $chModel    = isset($_SERVER['HTTP_SEC_CH_UA_MODEL']) ? $_SERVER['HTTP_SEC_CH_UA_MODEL'] : null;

        if (!$chBrands && !$chFullList && !$chPlatform) {
            return false;
        }

        $pairs = $this->parseCHBrandList($chFullList ?: $chBrands);
        $this->applyCHPlatform($chPlatform, $chPlatVer, $chMobile);

        if (!$pairs) {
            return false;
        }

        $pref = array('Google Chrome','Microsoft Edge','Opera','Vivaldi','Brave','Chromium','Chrome');
        $brand = null; $ver = null;
        foreach ($pref as $want) {
            if (isset($pairs[$want])) { $brand = $want; $ver = $pairs[$want]; break; }
        }
        if (!$brand) { $keys = array_keys($pairs); $brand = $keys ? $keys[0] : null; $ver = $brand ? $pairs[$brand] : null; }
        if (!$brand) return false;

        $name = self::BROWSER_UNKNOWN;
        if (stripos($brand, 'Chrome') !== false && stripos($brand, 'Google') !== false) $name = self::BROWSER_CHROME;
        elseif (stripos($brand, 'Edge') !== false) $name = self::BROWSER_EDGE;
        elseif (stripos($brand, 'Opera') !== false) $name = self::BROWSER_OPERA;
        elseif (stripos($brand, 'Vivaldi') !== false) $name = self::BROWSER_VIVALDI;
        elseif (stripos($brand, 'Brave') !== false) $name = self::BROWSER_CHROME; // keep tag compatibility
        elseif (strcasecmp($brand,'Chromium') === 0 || strcasecmp($brand,'Chrome') === 0) $name = self::BROWSER_CHROME;

        if ($name !== self::BROWSER_UNKNOWN) {
            $this->setBrowser($name);
            if ($ver) $this->setVersion($ver);
            return true;
        }
        return false;
    }

    /**
     * Parse CH brand list header into associative array
     * Example: "Chromium";v="139.0.7258.127", "Google Chrome";v="139.0.7258.127"
     */
    protected function parseCHBrandList($header)
    {
        if (!$header) return array();
        $out = array();
        $parts = preg_split('/\s*,\s*/', $header);
        foreach ($parts as $p) {
            if (preg_match('/"([^"]+)";\s*v="?([^",]+)"?/i', $p, $m)) {
                $brand = trim($m[1]);
                $ver   = trim($m[2]);
                $out[$brand] = $ver;
            }
        }
        return $out;
    }

    /**
     * Apply platform + mobile flags from UA-CH
     */
    protected function applyCHPlatform($chPlatform, $chPlatVer, $chMobile)
    {
        if ($chPlatform) {
            $plat = trim($chPlatform, "\"'");
            switch ($plat) {
                case 'Windows': $this->setPlatform(self::PLATFORM_WINDOWS); break;
                case 'macOS':   $this->setPlatform(self::PLATFORM_APPLE);  break; // keep original "Apple" tag
                case 'Linux':   $this->setPlatform(self::PLATFORM_LINUX);  break;
                case 'Android': $this->setPlatform(self::PLATFORM_ANDROID); $this->setMobile(true); break;
                case 'iOS':     $this->setPlatform(self::PLATFORM_IPHONE);  $this->setMobile(true); break;
                case 'Chrome OS':
                case 'ChromeOS': $this->setPlatform(self::PLATFORM_LINUX);  break; // no ChromeOS enum; safest mapping
                default: break;
            }
        }
        if ($chMobile) {
            $mob = trim($chMobile, "\"'");
            if ($mob === '?1' || $mob === '1' || strcasecmp($mob,'true') === 0) $this->setMobile(true);
        }
        // $chPlatVer exists but class doesn't expose OS version, so we do nothing.
    }

    /**
     * Determine the browser type via UA string fallback
     */
    protected function checkBrowsers()
    {
        return (
            $this->checkBrowserWebTv() ||
            $this->checkBrowserEdge() ||
            $this->checkBrowserInternetExplorer() ||
            $this->checkBrowserOpera() ||
            $this->checkBrowserGaleon() ||
            $this->checkBrowserNetscapeNavigator9Plus() ||
            $this->checkBrowserVivaldi() ||
            $this->checkBrowserFirefox() ||
            $this->checkBrowserChrome() ||
            $this->checkBrowserOmniWeb() ||
            $this->checkBrowserAndroid() ||
            $this->checkBrowseriPad() ||
            $this->checkBrowseriPod() ||
            $this->checkBrowseriPhone() ||
            $this->checkBrowserBlackBerry() ||
            $this->checkBrowserNokia() ||
            $this->checkBrowserGoogleBot() ||
            $this->checkBrowserMSNBot() ||
            $this->checkBrowserBingBot() ||
            $this->checkBrowserSlurp() ||
            $this->checkFacebookExternalHit() ||
            $this->checkBrowserSafari() ||
            $this->checkBrowserNetPositive() ||
            $this->checkBrowserFirebird() ||
            $this->checkBrowserKonqueror() ||
            $this->checkBrowserIcab() ||
            $this->checkBrowserPhoenix() ||
            $this->checkBrowserAmaya() ||
            $this->checkBrowserLynx() ||
            $this->checkBrowserShiretoko() ||
            $this->checkBrowserIceCat() ||
            $this->checkBrowserIceweasel() ||
            $this->checkBrowserW3CValidator() ||
            $this->checkBrowserPlayStation() ||
            $this->checkBrowserMozilla()
        );
    }

    protected function checkBrowserBlackBerry()
    {
        if (stripos($this->_agent, 'blackberry') !== false) {
            $aresult = explode("/", stristr($this->_agent, "BlackBerry"));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->_browser_name = self::BROWSER_BLACKBERRY;
                $this->setMobile(true);
                return true;
            }
        }
        return false;
    }

    protected function checkForAol()
    {
        $this->setAol(false);
        $this->setAolVersion(self::VERSION_UNKNOWN);

        if (stripos($this->_agent, 'aol') !== false) {
            $aversion = explode(' ', stristr($this->_agent, 'AOL'));
            if (isset($aversion[1])) {
                $this->setAol(true);
                $this->setAolVersion(preg_replace('/[^0-9\.a-z]/i', '', $aversion[1]));
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserGoogleBot()
    {
        if (stripos($this->_agent, 'googlebot') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'googlebot'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion(str_replace(';', '', $aversion[0]));
                $this->_browser_name = self::BROWSER_GOOGLEBOT;
                $this->setRobot(true);
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserMSNBot()
    {
        if (stripos($this->_agent, "msnbot") !== false) {
            $aresult = explode("/", stristr($this->_agent, "msnbot"));
            if (isset($aresult[1])) {
                $aversion = explode(" ", $aresult[1]);
                $this->setVersion(str_replace(";", "", $aversion[0]));
                $this->_browser_name = self::BROWSER_MSNBOT;
                $this->setRobot(true);
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserBingBot()
    {
        if (stripos($this->_agent, "bingbot") !== false) {
            $aresult = explode("/", stristr($this->_agent, "bingbot"));
            if (isset($aresult[1])) {
                $aversion = explode(" ", $aresult[1]);
                $this->setVersion(str_replace(";", "", $aversion[0]));
                $this->_browser_name = self::BROWSER_BINGBOT;
                $this->setRobot(true);
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserW3CValidator()
    {
        if (stripos($this->_agent, 'W3C-checklink') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'W3C-checklink'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->_browser_name = self::BROWSER_W3CVALIDATOR;
                return true;
            }
        } else if (stripos($this->_agent, 'W3C_Validator') !== false) {
            $ua = str_replace("W3C_Validator ", "W3C_Validator/", $this->_agent);
            $aresult = explode('/', stristr($ua, 'W3C_Validator'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->_browser_name = self::BROWSER_W3CVALIDATOR;
                return true;
            }
        } else if (stripos($this->_agent, 'W3C-mobileOK') !== false) {
            $this->_browser_name = self::BROWSER_W3CVALIDATOR;
            $this->setMobile(true);
            return true;
        }
        return false;
    }

    protected function checkBrowserSlurp()
    {
        if (stripos($this->_agent, 'slurp') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'Slurp'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->_browser_name = self::BROWSER_SLURP;
                $this->setRobot(true);
                $this->setMobile(false);
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserEdge()
    {
        if( stripos($this->_agent,'Edge/') !== false ) {
            $aresult = explode('/', stristr($this->_agent, 'Edge'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->setBrowser(self::BROWSER_EDGE);
                if(stripos($this->_agent, 'Windows Phone') !== false || stripos($this->_agent, 'Android') !== false) {
                    $this->setMobile(true);
                }
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserInternetExplorer()
    {
        if( stripos($this->_agent,'Trident/7.0; rv:11.0') !== false ) {
            $this->setBrowser(self::BROWSER_IE);
            $this->setVersion('11.0');
            return true;
        }
        else if (stripos($this->_agent, 'microsoft internet explorer') !== false) {
            $this->setBrowser(self::BROWSER_IE);
            $this->setVersion('1.0');
            $aresult = stristr($this->_agent, '/');
            if (preg_match('/308|425|426|474|0b1/i', $aresult)) {
                $this->setVersion('1.5');
            }
            return true;
        }
        else if (stripos($this->_agent, 'msie') !== false && stripos($this->_agent, 'opera') === false) {
            if (stripos($this->_agent, 'msnb') !== false) {
                $aresult = explode(' ', stristr(str_replace(';', '; ', $this->_agent), 'MSN'));
                if (isset($aresult[1])) {
                    $this->setBrowser(self::BROWSER_MSN);
                    $this->setVersion(str_replace(array('(', ')', ';'), '', $aresult[1]));
                    return true;
                }
            }
            $aresult = explode(' ', stristr(str_replace(';', '; ', $this->_agent), 'msie'));
            if (isset($aresult[1])) {
                $this->setBrowser(self::BROWSER_IE);
                $this->setVersion(str_replace(array('(', ')', ';'), '', $aresult[1]));
                if(stripos($this->_agent, 'IEMobile') !== false) {
                    $this->setBrowser(self::BROWSER_POCKET_IE);
                    $this->setMobile(true);
                }
                return true;
            }
        }
        else if(stripos($this->_agent, 'trident') !== false) {
            $this->setBrowser(self::BROWSER_IE);
            $result = explode('rv:', $this->_agent);
            if (isset($result[1])) {
                $this->setVersion(preg_replace('/[^0-9.]+/', '', $result[1]));
                $this->_agent = str_replace(array("Mozilla", "Gecko"), "MSIE", $this->_agent);
            }
        }
        else if (stripos($this->_agent, 'mspie') !== false || stripos($this->_agent, 'pocket') !== false) {
            $aresult = explode(' ', stristr($this->_agent, 'mspie'));
            if (isset($aresult[1])) {
                $this->setPlatform(self::PLATFORM_WINDOWS_CE);
                $this->setBrowser(self::BROWSER_POCKET_IE);
                $this->setMobile(true);

                if (stripos($this->_agent, 'mspie') !== false) {
                    $this->setVersion($aresult[1]);
                } else {
                    $aversion = explode('/', $this->_agent);
                    if (isset($aversion[1])) {
                        $this->setVersion($aversion[1]);
                    }
                }
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserOpera()
    {
        if (stripos($this->_agent, 'opera mini') !== false) {
            $resultant = stristr($this->_agent, 'opera mini');
            if (preg_match('/\//', $resultant)) {
                $aresult = explode('/', $resultant);
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion($aversion[0]);
                }
            } else {
                $aversion = explode(' ', stristr($resultant, 'opera mini'));
                if (isset($aversion[1])) {
                    $this->setVersion($aversion[1]);
                }
            }
            $this->_browser_name = self::BROWSER_OPERA_MINI;
            $this->setMobile(true);
            return true;
        } else if (stripos($this->_agent, 'opera') !== false) {
            $resultant = stristr($this->_agent, 'opera');
            if (preg_match('/Version\/(1*.*)$/', $resultant, $matches)) {
                $this->setVersion($matches[1]);
            } else if (preg_match('/\//', $resultant)) {
                $aresult = explode('/', str_replace("(", " ", $resultant));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion($aversion[0]);
                }
            } else {
                $aversion = explode(' ', stristr($resultant, 'opera'));
                $this->setVersion(isset($aversion[1]) ? $aversion[1] : "");
            }
            if (stripos($this->_agent, 'Opera Mobi') !== false) {
                $this->setMobile(true);
            }
            $this->_browser_name = self::BROWSER_OPERA;
            return true;
        } else if (stripos($this->_agent, 'OPR') !== false) {
            $resultant = stristr($this->_agent, 'OPR');
            if (preg_match('/\//', $resultant)) {
                $aresult = explode('/', str_replace("(", " ", $resultant));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion($aversion[0]);
                }
            }
            if (stripos($this->_agent, 'Mobile') !== false) {
                $this->setMobile(true);
            }
            $this->_browser_name = self::BROWSER_OPERA;
            return true;
        }
        return false;
    }

    protected function checkBrowserChrome()
    {
        if (stripos($this->_agent, 'Chrome') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'Chrome'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->setBrowser(self::BROWSER_CHROME);
                if (stripos($this->_agent, 'Android') !== false) {
                    if (stripos($this->_agent, 'Mobile') !== false) {
                        $this->setMobile(true);
                    } else {
                        $this->setTablet(true);
                    }
                }
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserWebTv()
    {
        if (stripos($this->_agent, 'webtv') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'webtv'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->setBrowser(self::BROWSER_WEBTV);
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserNetPositive()
    {
        if (stripos($this->_agent, 'NetPositive') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'NetPositive'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion(str_replace(array('(', ')', ';'), '', $aversion[0]));
                $this->setBrowser(self::BROWSER_NETPOSITIVE);
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserGaleon()
    {
        if (stripos($this->_agent, 'galeon') !== false) {
            $aresult = explode(' ', stristr($this->_agent, 'galeon'));
            $aversion = explode('/', $aresult[0]);
            if (isset($aversion[1])) {
                $this->setVersion($aversion[1]);
                $this->setBrowser(self::BROWSER_GALEON);
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserKonqueror()
    {
        if (stripos($this->_agent, 'Konqueror') !== false) {
            $aresult = explode(' ', stristr($this->_agent, 'Konqueror'));
            $aversion = explode('/', $aresult[0]);
            if (isset($aversion[1])) {
                $this->setVersion($aversion[1]);
                $this->setBrowser(self::BROWSER_KONQUEROR);
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserIcab()
    {
        if (stripos($this->_agent, 'icab') !== false) {
            $aversion = explode(' ', stristr(str_replace('/', ' ', $this->_agent), 'icab'));
            if (isset($aversion[1])) {
                $this->setVersion($aversion[1]);
                $this->setBrowser(self::BROWSER_ICAB);
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserOmniWeb()
    {
        if (stripos($this->_agent, 'omniweb') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'omniweb'));
            $aversion = explode(' ', isset($aresult[1]) ? $aresult[1] : "");
            $this->setVersion($aversion[0]);
            $this->setBrowser(self::BROWSER_OMNIWEB);
            return true;
        }
        return false;
    }

    protected function checkBrowserPhoenix()
    {
        if (stripos($this->_agent, 'Phoenix') !== false) {
            $aversion = explode('/', stristr($this->_agent, 'Phoenix'));
            if (isset($aversion[1])) {
                $this->setVersion($aversion[1]);
                $this->setBrowser(self::BROWSER_PHOENIX);
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserFirebird()
    {
        if (stripos($this->_agent, 'Firebird') !== false) {
            $aversion = explode('/', stristr($this->_agent, 'Firebird'));
            if (isset($aversion[1])) {
                $this->setVersion($aversion[1]);
                $this->setBrowser(self::BROWSER_FIREBIRD);
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserNetscapeNavigator9Plus()
    {
        if (stripos($this->_agent, 'Firefox') !== false && preg_match('/Navigator\/([^ ]*)/i', $this->_agent, $matches)) {
            $this->setVersion($matches[1]);
            $this->setBrowser(self::BROWSER_NETSCAPE_NAVIGATOR);
            return true;
        } else if (stripos($this->_agent, 'Firefox') === false && preg_match('/Netscape6?\/([^ ]*)/i', $this->_agent, $matches)) {
            $this->setVersion($matches[1]);
            $this->setBrowser(self::BROWSER_NETSCAPE_NAVIGATOR);
            return true;
        }
        return false;
    }

    protected function checkBrowserShiretoko()
    {
        if (stripos($this->_agent, 'Mozilla') !== false && preg_match('/Shiretoko\/([^ ]*)/i', $this->_agent, $matches)) {
            $this->setVersion($matches[1]);
            $this->setBrowser(self::BROWSER_SHIRETOKO);
            return true;
        }
        return false;
    }

    protected function checkBrowserIceCat()
    {
        if (stripos($this->_agent, 'Mozilla') !== false && preg_match('/IceCat\/([^ ]*)/i', $this->_agent, $matches)) {
            $this->setVersion($matches[1]);
            $this->setBrowser(self::BROWSER_ICECAT);
            return true;
        }
        return false;
    }

    protected function checkBrowserNokia()
    {
        if (preg_match("/Nokia([^\/]+)\/([^ SP]+)/i", $this->_agent, $matches)) {
            $this->setVersion($matches[2]);
            if (stripos($this->_agent, 'Series60') !== false || strpos($this->_agent, 'S60') !== false) {
                $this->setBrowser(self::BROWSER_NOKIA_S60);
            } else {
                $this->setBrowser(self::BROWSER_NOKIA);
            }
            $this->setMobile(true);
            return true;
        }
        return false;
    }

    protected function checkBrowserFirefox()
    {
        if (stripos($this->_agent, 'safari') === false) {
            if (preg_match("/Firefox[\/ \(]([^ ;\)]+)/i", $this->_agent, $matches)) {
                $this->setVersion($matches[1]);
                $this->setBrowser(self::BROWSER_FIREFOX);
                if (stripos($this->_agent, 'Android') !== false) {
                    if (stripos($this->_agent, 'Mobile') !== false) {
                        $this->setMobile(true);
                    } else {
                        $this->setTablet(true);
                    }
                }
                return true;
            } else if (preg_match("/Firefox$/i", $this->_agent, $matches)) {
                $this->setVersion("");
                $this->setBrowser(self::BROWSER_FIREFOX);
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserIceweasel()
    {
        if (stripos($this->_agent, 'Iceweasel') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'Iceweasel'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->setBrowser(self::BROWSER_ICEWEASEL);
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserMozilla()
    {
        if (stripos($this->_agent, 'mozilla') !== false && preg_match('/rv:[0-9].[0-9][a-b]?/i', $this->_agent) && stripos($this->_agent, 'netscape') === false) {
            $aversion = explode(' ', stristr($this->_agent, 'rv:'));
            preg_match('/rv:[0-9].[0-9][a-b]?/i', $this->_agent, $aversion);
            $this->setVersion(str_replace('rv:', '', $aversion[0]));
            $this->setBrowser(self::BROWSER_MOZILLA);
            return true;
        } else if (stripos($this->_agent, 'mozilla') !== false && preg_match('/rv:[0-9]\.[0-9]/i', $this->_agent) && stripos($this->_agent, 'netscape') === false) {
            $aversion = explode('', stristr($this->_agent, 'rv:'));
            $this->setVersion(str_replace('rv:', '', $aversion[0]));
            $this->setBrowser(self::BROWSER_MOZILLA);
            return true;
        } else if (stripos($this->_agent, 'mozilla') !== false && preg_match('/mozilla\/([^ ]*)/i', $this->_agent, $matches) && stripos($this->_agent, 'netscape') === false) {
            $this->setVersion($matches[1]);
            $this->setBrowser(self::BROWSER_MOZILLA);
            return true;
        }
        return false;
    }

    protected function checkBrowserLynx()
    {
        if (stripos($this->_agent, 'lynx') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'Lynx'));
            $aversion = explode(' ', (isset($aresult[1]) ? $aresult[1] : ""));
            $this->setVersion($aversion[0]);
            $this->setBrowser(self::BROWSER_LYNX);
            return true;
        }
        return false;
    }

    protected function checkBrowserAmaya()
    {
        if (stripos($this->_agent, 'amaya') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'Amaya'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->setBrowser(self::BROWSER_AMAYA);
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserSafari()
    {
        if (stripos($this->_agent, 'Safari') !== false
            && stripos($this->_agent, 'iPhone') === false
            && stripos($this->_agent, 'iPod') === false) {

            $aresult = explode('/', stristr($this->_agent, 'Version'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            } else {
                $this->setVersion(self::VERSION_UNKNOWN);
            }
            $this->setBrowser(self::BROWSER_SAFARI);
            return true;
        }
        return false;
    }

    protected function checkFacebookExternalHit()
    {
        if(stristr($this->_agent,'FacebookExternalHit'))
        {
            $this->setRobot(true);
            $this->setFacebook(true);
            return true;
        }
        return false;
    }

    protected function checkForFacebookIos()
    {
        if(stristr($this->_agent,'FBIOS'))
        {
            $this->setFacebook(true);
            return true;
        }
        return false;
    }

    protected function getSafariVersionOnIos()
    {
        $aresult = explode('/',stristr($this->_agent,'Version'));
        if( isset($aresult[1]) )
        {
            $aversion = explode(' ',$aresult[1]);
            $this->setVersion($aversion[0]);
            return true;
        }
        return false;
    }

    protected function getChromeVersionOnIos()
    {
        $aresult = explode('/',stristr($this->_agent,'CriOS'));
        if( isset($aresult[1]) )
        {
            $aversion = explode(' ',$aresult[1]);
            $this->setVersion($aversion[0]);
            $this->setBrowser(self::BROWSER_CHROME);
            return true;
        }
        return false;
    }

    protected function checkBrowseriPhone() {
        if( stripos($this->_agent,'iPhone') !== false ) {
            $this->setVersion(self::VERSION_UNKNOWN);
            $this->setBrowser(self::BROWSER_IPHONE);
            $this->getSafariVersionOnIos();
            $this->getChromeVersionOnIos();
            $this->checkForFacebookIos();
            $this->setMobile(true);
            return true;
        }
        return false;
    }

    protected function checkBrowseriPad() {
        if( stripos($this->_agent,'iPad') !== false ) {
            $this->setVersion(self::VERSION_UNKNOWN);
            $this->setBrowser(self::BROWSER_IPAD);
            $this->getSafariVersionOnIos();
            $this->getChromeVersionOnIos();
            $this->checkForFacebookIos();
            $this->setTablet(true);
            return true;
        }
        return false;
    }

    protected function checkBrowseriPod() {
        if( stripos($this->_agent,'iPod') !== false ) {
            $this->setVersion(self::VERSION_UNKNOWN);
            $this->setBrowser(self::BROWSER_IPOD);
            $this->getSafariVersionOnIos();
            $this->getChromeVersionOnIos();
            $this->checkForFacebookIos();
            $this->setMobile(true);
            return true;
        }
        return false;
    }

    protected function checkBrowserAndroid()
    {
        if (stripos($this->_agent, 'Android') !== false) {
            $aresult = explode(' ', stristr($this->_agent, 'Android'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            } else {
                $this->setVersion(self::VERSION_UNKNOWN);
            }
            if (stripos($this->_agent, 'Mobile') !== false) {
                $this->setMobile(true);
            } else {
                $this->setTablet(true);
            }
            $this->setBrowser(self::BROWSER_ANDROID);
            return true;
        }
        return false;
    }

    protected function checkBrowserVivaldi()
    {
        if (stripos($this->_agent, 'Vivaldi') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'Vivaldi'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->setBrowser(self::BROWSER_VIVALDI);
                return true;
            }
        }
        return false;
    }

    protected function checkBrowserPlayStation()
    {
        if (stripos($this->_agent, 'PlayStation ') !== false) {
            $aresult = explode(' ', stristr($this->_agent, 'PlayStation '));
            $this->setBrowser(self::BROWSER_PLAYSTATION);
            if (isset($aresult[0])) {
                $aversion = explode(')', $aresult[2]);
                $this->setVersion($aversion[0]);
                if (stripos($this->_agent, 'Portable)') !== false || stripos($this->_agent, 'Vita') !== false) {
                    $this->setMobile(true);
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Determine the user's platform
     */
    protected function checkPlatform()
    {
        if (stripos($this->_agent, 'windows') !== false)
        {
            $this->_platform = self::PLATFORM_WINDOWS;
        }
        else if (stripos($this->_agent, 'iPad') !== false)
        {
            $this->_platform = self::PLATFORM_IPAD;
        }
        else if (stripos($this->_agent, 'iPod') !== false)
        {
            $this->_platform = self::PLATFORM_IPOD;
        }
        else if (stripos($this->_agent, 'iPhone') !== false)
        {
            $this->_platform = self::PLATFORM_IPHONE;
        }
        elseif (stripos($this->_agent, 'mac') !== false)
        {
            $this->_platform = self::PLATFORM_APPLE;
        }
        elseif (stripos($this->_agent, 'android') !== false)
        {
            $this->_platform = self::PLATFORM_ANDROID;
        }
        elseif (stripos($this->_agent, 'linux') !== false)
        {
            $this->_platform = self::PLATFORM_LINUX;
        }
        else if (stripos($this->_agent, 'Nokia') !== false)
        {
            $this->_platform = self::PLATFORM_NOKIA;
        }
        else if (stripos($this->_agent, 'BlackBerry') !== false)
        {
            $this->_platform = self::PLATFORM_BLACKBERRY;
        }
        elseif (stripos($this->_agent, 'FreeBSD') !== false)
        {
            $this->_platform = self::PLATFORM_FREEBSD;
        }
        elseif (stripos($this->_agent, 'OpenBSD') !== false)
        {
            $this->_platform = self::PLATFORM_OPENBSD;
        }
        elseif (stripos($this->_agent, 'NetBSD') !== false)
        {
            $this->_platform = self::PLATFORM_NETBSD;
        }
        elseif (stripos($this->_agent, 'OpenSolaris') !== false)
        {
            $this->_platform = self::PLATFORM_OPENSOLARIS;
        }
        elseif (stripos($this->_agent, 'SunOS') !== false)
        {
            $this->_platform = self::PLATFORM_SUNOS;
        }
        elseif (stripos($this->_agent, 'OS\/2') !== false)
        {
            $this->_platform = self::PLATFORM_OS2;
        }
        elseif (stripos($this->_agent, 'BeOS') !== false)
        {
            $this->_platform = self::PLATFORM_BEOS;
        }
        elseif (stripos($this->_agent, 'win') !== false)
        {
            $this->_platform = self::PLATFORM_WINDOWS;
        }
        elseif (stripos($this->_agent, 'Playstation') !== false)
        {
            $this->_platform = self::PLATFORM_PLAYSTATION;
        }
    }

    /**
     * Helper: encourage UA-CH on subsequent requests (HTTPS recommended).
     * Called automatically in __construct for the one-file solution.
     */
    public static function sendAcceptCHHeaders()
    {
        // Suppress warnings if headers already sent or environment restricts header()
        @header('Accept-CH: Sec-CH-UA, Sec-CH-UA-Mobile, Sec-CH-UA-Platform, Sec-CH-UA-Platform-Version, Sec-CH-UA-Arch, Sec-CH-UA-Model, Sec-CH-UA-Full-Version-List, Sec-CH-UA-Bitness');
        @header('Vary: User-Agent, Sec-CH-UA, Sec-CH-UA-Mobile, Sec-CH-UA-Platform, Sec-CH-UA-Platform-Version, Sec-CH-UA-Arch, Sec-CH-UA-Model, Sec-CH-UA-Full-Version-List, Sec-CH-UA-Bitness');
    }
}
