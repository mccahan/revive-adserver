<?php

/*
+---------------------------------------------------------------------------+
| OpenX v${RELEASE_MAJOR_MINOR}                                                                |
| =======${RELEASE_MAJOR_MINOR_DOUBLE_UNDERLINE}                                                                |
|                                                                           |
| Copyright (c) 2003-2008 OpenX Limited                                     |
| For contact details, see: http://www.openx.org/                           |
|                                                                           |
| This program is free software; you can redistribute it and/or modify      |
| it under the terms of the GNU General Public License as published by      |
| the Free Software Foundation; either version 2 of the License, or         |
| (at your option) any later version.                                       |
|                                                                           |
| This program is distributed in the hope that it will be useful,           |
| but WITHOUT ANY WARRANTY; without even the implied warranty of            |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
| GNU General Public License for more details.                              |
|                                                                           |
| You should have received a copy of the GNU General Public License         |
| along with this program; if not, write to the Free Software               |
| Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA |
+---------------------------------------------------------------------------+
$Id$
*/


require_once MAX_PATH . '/lib/OA/Dal.php';
require_once MAX_PATH . '/lib/OA/Permission.php';
require_once MAX_PATH . '/lib/OA/Permission/User.php';
require_once MAX_PATH . '/lib/OA/Admin/Template.php';

/**
 * A class to deal with user authentication
 *
 */
class OA_Auth
{
    /**
     * Returns authentication plugin
     *
     * @static
     * @param string $authType
     * @return Plugins_Authentication
     */
    function &staticGetAuthPlugin($authType = null)
    {
        static $authPlugin;
        static $authPluginType;

        if (is_null($authPlugin) || $authPluginType != $authType) {
            if (!empty($authType)) {
                $authPlugin = &MAX_Plugin::factory('authentication', $authType);
            } else {
                $authPlugin = &MAX_Plugin::factoryPluginByModuleConfig('authentication');
            }
            if (!$authPlugin) {
                OA::debug('Error while including authentication plugin', PEAR_LOG_ERR);
            }
            $authPluginType = $authType;
        }
        return $authPlugin;
    }

    /**
     * Logs in an user
     *
     * @static
     *
     * @param callback $redirectCallback
     * @return mixed Array on success
     */
    function login($redirectCallback = null)
    {
        $aConf = $GLOBALS['_MAX']['CONF'];

        if (!is_callable($redirectCallback)) {
            // Set the default callback
            $redirectCallback = array('OA_Auth', 'checkRedirect');
        }

        if (call_user_func($redirectCallback)) {
            header('location: http://'.$aConf['webpath']['admin']);
            exit();
        }

        if (defined('OA_SKIP_LOGIN')) {
            return OA_Auth::getFakeSessionData();
        }

        if (OA_Auth::suppliedCredentials()) {
            $doUser = OA_Auth::authenticateUser();

            if (!$doUser) {
                OA_Auth::restart($GLOBALS['strUsernameOrPasswordWrong']);
            }

            return OA_Auth::getSessionData($doUser);
        }

        OA_Auth::restart();
    }

    /**
     * A method to logout and redirect to the correct URL
     *
     * @static
     *
     * @todo Fix when preferences are ready and logout url is stored into the
     * preferences table
     */
    function logout()
    {
        $authPlugin = &OA_Auth::staticGetAuthPlugin();
        $authPlugin->logout();
    }

    /**
     * A method to check if the login credential were supplied as POST parameters
     *
     * @static
     *
     * @return bool
     */
    function suppliedCredentials()
    {
        $authPlugin = &OA_Auth::staticGetAuthPlugin();
        return $authPlugin->suppliedCredentials();
    }

    /**
     * A method to authenticate user
     *
     * @static
     *
     * @return bool
     */
    function authenticateUser()
    {
        $authPlugin = &OA_Auth::staticGetAuthPlugin();
        return $authPlugin->authenticateUser();
    }

    /**
     * A method to test if the user is logged in
     *
     * @return boolean
     */
    function isLoggedIn()
    {
        return is_a(OA_Permission::getCurrentUser(), 'OA_Permission_User');
    }

    /**
     * A static method to return the data to be stored in the session variable
     *
     * @static
     *
     * @param DataObjects_Users $doUser
     * @param bool $skipDatabaseAccess True if the OA_Permission_User constructor should
     *                                 avoid performing some checks accessing the database
     * @return array
     */
    function getSessionData($doUser, $skipDatabaseAccess = false)
    {
        return array(
            'user' => new OA_Permission_User($doUser, $skipDatabaseAccess)
        );
    }

    /**
     * A static method to return fake data to be stored in the session variable
     *
     * @static
     *
     * @return array
     */
    function getFakeSessionData()
    {
        return array(
            'user' => false
        );
    }

    /**
     * A static method to restart with a login screen, eventually displaying a custom message
     *
     * @static
     *
     * @param string $sMessage Optional message
     */
    function restart($sMessage = '')
    {
        $_COOKIE['sessionID'] = phpAds_SessionStart();
        OA_Auth::displayLogin($sMessage, $_COOKIE['sessionID']);
    }

    /**
     * A static method to restart with a login screen, displaying an error message
     *
     * @static
     *
     * @param PEAR_Error $oError
     */
    function displayError($oError)
    {
        OA_Auth::restart($oError->getMessage());
    }

    /**
     * A static method to display a login screen
     *
     * @static
     *
     * @param string $sMessage
     * @param string $sessionID
     * @param bool $inlineLogin
     */
    function displayLogin($sMessage = '', $sessionID = 0, $inLineLogin = false)
    {
        $authLogin = &OA_Auth::staticGetAuthPlugin();
        $authLogin->displayLogin($sMessage, $sessionID, $inLineLogin);
    }

    /**
     * Check if application is running from appropriate dir
     *
     * @static
     *
     * @param string $location
     * @return boolean True if a redirect is needed
     */
    function checkRedirect($location = 'admin')
    {
        $redirect = false;
        // Is it possible to detect that we are NOT in the admin directory
        // via the URL the user is accessing OpenXwith?
        if (!preg_match('#/'. $location .'/?$#', $_SERVER['REQUEST_URI'])) {
            $dirName = dirname($_SERVER['REQUEST_URI']);
            if (!preg_match('#/'. $location .'$#', $dirName)) {
                // The user is not in the "admin" folder directly. Are they
                // in the admin folder as a result of a "full" virtual host
                // configuration?
                if ($GLOBALS['_MAX']['CONF']['webpath']['admin'] != getHostName()) {
                    // Not a "full" virtual host setup, so re-direct
                    $redirect = true;
                }
            }
        }

        return $redirect;
    }

}

?>
