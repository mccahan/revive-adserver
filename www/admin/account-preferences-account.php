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

// Require the initialisation file
require_once '../../init.php';

// Required files
require_once MAX_PATH . '/lib/OA/Admin/Option.php';
require_once MAX_PATH . '/lib/OA/Admin/UI/UserAccess.php';

require_once MAX_PATH . '/lib/max/Admin/Languages.php';
require_once MAX_PATH . '/lib/max/Admin/Redirect.php';
require_once MAX_PATH . '/lib/max/Plugin/Translation.php';
require_once MAX_PATH . '/www/admin/config.php';

// Security check
OA_Permission::enforceAccount(OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER, OA_ACCOUNT_ADVERTISER, OA_ACCOUNT_TRAFFICKER);

// Create a new option object for displaying the setting's page's HTML form
$oOptions = new OA_Admin_Option('preferences');

// Prepare an array for storing error messages
$aErrormessage = array();

// If the settings page is a submission, deal with the form data
if (isset($_POST['submitok']) && $_POST['submitok'] == 'true') {
    // Register input variables
    phpAds_registerGlobalUnslashed(
        'pwold',
        'pw',
        'pw2',
        'contact_name',
        'email_address'
    );
    // Get the DB_DataObject for the current user
    $doUsers = OA_Dal::factoryDO('users');
    $doUsers->get(OA_Permission::getUserId());

    // Set defaults
    $changeEmail    = isset($email_address);
    $changePassword = false;

    // Get the current authentication plugin instance
    $oPlugin = OA_Auth::staticGetAuthPlugin();

    // Check password
    if (!isset($pwold) || !$oPlugin->checkPassword(OA_Permission::getUsername(), $pwold)) {
        $aErrormessage[2][] = $GLOBALS['strPasswordWrong'];
    }

    if (isset($contact_name)) {
        $doUsers->contact_name = $contact_name;
    }

    if (isset($pw) && strlen($pw) || isset($pw2) && strlen($pw2)) {
        if (!strlen($pw)  || strstr("\\", $pw)) {
            $aErrormessage[1][] = $GLOBALS['strInvalidPassword'];
        } elseif (strcmp($pw, $pw2)) {
            $aErrormessage[1][] = $GLOBALS['strNotSamePasswords'];
        } else {
            $changePassword = true;
        }
    }

    if (!count($aErrormessage) && $changeEmail) {
        $result = $oPlugin->changeEmail($doUsers, $email_address, $pwold);
        if (PEAR::isError($result)) {
            $aErrormessage[0][] = $result->getMessage();
        }
    }
    if (!count($aErrormessage) && $changePassword) {
        $result = $oPlugin->changePassword($doUsers, $pw, $pwold);
        if (PEAR::isError($result)) {
            $aErrormessage[1][] = $result->getMessage();
        }
    }
    if (!count($aErrormessage)) {
        if ($doUsers->update() === false) {
            // Unable to update the preferences
            $aErrormessage[0][] = $strUnableToWritePrefs;
        } else {
            // The "preferences" were written correctly saved to the database,
            // go to the "next" preferences page from here
            MAX_Admin_Redirect::redirect('account-preferences-banner.php');
        }
    }
}

// Display the settings page's header and sections
phpAds_PageHeader("5.1");
if (OA_Permission::isAccount(OA_ACCOUNT_ADMIN)) {
    // Show all "My Account" sections
    phpAds_ShowSections(array("5.1", "5.2", "5.4", "5.5", "5.3"));
} else if (OA_Permission::isAccount(OA_ACCOUNT_MANAGER)) {
    // Show the "Preferences", "User Log" and "Channel Management" sections of the "My Account" sections
    phpAds_ShowSections(array("5.1", "5.3", "5.7"));
} else if (OA_Permission::isAccount(OA_ACCOUNT_TRAFFICKER)) {
    // Show the "Preferences" section of the "My Account" sections
    phpAds_ShowSections(array("5.1"));
} else if (OA_Permission::isAccount(OA_ACCOUNT_ADVERTISER)) {
    // Show the "Preferences" section of the "My Account" sections
    phpAds_ShowSections(array("5.1"));
}

// Set the correct section of the preference pages and display the drop-down menu
$oOptions->selection("account");

// Get the current logged in user details
$oUser = OA_Permission::getCurrentUser();
$aUser = $oUser->aUser;

// Prepare an array of HTML elements to display for the form, and
// output using the $oOption object
$aSettings = array (
    array (
        'text'  => $strUserDetails,
        'items' => array (
            array (
                'type'    => 'text',
                'name'    => 'contact_name',
                'value'   => $aUser['contact_name'],
                'text'    => $strFullName,
                'size'    => 35
            ),
            array (
                'type'    => 'break'
            ),
            array (
                'type'    => 'text',
                'name'    => 'email_address',
                'value'   => $aUser['email_address'],
                'text'    => $strEmailAddress,
                'size'    => 35,
                'check'   => 'email'
            )
        )
    ),
    array (
        'text'  => $strNewPassword,
        'items' => array (
            array (
                'type'    => 'password',
                'name'    => 'pw',
                'text'    => $strNewPassword,
            ),
            array (
                'type'    => 'break'
            ),
            array (
                'type'    => 'password',
                'name'    => 'pw2',
                'text'    => $strRepeatPassword,
                'depends' => 'pw!=""',
                'check'   => 'compare:pw'
            )
        )
    ),
    array (
        'text'  => $strPassword,
        'items' => array (
            array (
                'type'    => 'password',
                'name'    => 'pwold',
                'text'    => $strPassword,
                'req'     => true
            )
        )
    ),
);
$oOptions->show($aSettings, $aErrormessage);

// Display the page footer
phpAds_PageFooter();

?>
