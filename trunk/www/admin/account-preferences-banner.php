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
require_once MAX_PATH . '/lib/OA/Preferences.php';

require_once MAX_PATH . '/lib/max/Admin/Redirect.php';
require_once MAX_PATH . '/lib/max/Plugin/Translation.php';
require_once MAX_PATH . '/www/admin/config.php';

// Security check
OA_Permission::enforceAccount(OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER, OA_ACCOUNT_ADVERTISER, OA_ACCOUNT_TRAFFICKER);

// Load the account's preferences, with additional information, into a specially named array
$GLOBALS['_MAX']['PREF_EXTRA'] = OA_Preferences::loadPreferences(true, true);

// Create a new option object for displaying the setting's page's HTML form
$oOptions = new OA_Admin_Option('preferences');

// Prepare an array for storing error messages
$aErrormessage = array();

// If the settings page is a submission, deal with the form data
if (isset($_POST['submitok']) && $_POST['submitok'] == 'true') {
    // Prepare an array of the HTML elements to process, and which
    // of the preferences are checkboxes
    $aElements   = array();
    $aCheckboxes = array();
    // Default Banners
    $aElements[] = 'default_banner_image_url';
    $aElements[] = 'default_banner_destination_url';
    // HTML Banner Options
    $aElements[] = 'auto_alter_html_banners_for_click_tracking';
    $aCheckboxes['auto_alter_html_banners_for_click_tracking'] = true;
    // Default Weight
    $aElements[] = 'default_banner_weight';
    $aElements[] = 'default_campaign_weight';
    // Save the preferences
    $result = OA_Preferences::processPreferencesFromForm($aElements, $aCheckboxes);
    if ($result) {
        // The preferences were written correctly saved to the database,
        // go to the "next" preferences page from here
        MAX_Admin_Redirect::redirect('account-preferences-campaign-email-reports.php');
    }
    // Could not write the preferences to the database, store this
    // error message and continue
    $aErrormessage[0][] = $strUnableToWritePrefs;
}

// Display the preference page's header and sections
phpAds_PageHeader("account-preferences-index");

// Set the correct section of the preference pages and display the drop-down menu
$oOptions->selection("banner");

// Prepare an array of HTML elements to display for the form, and
// output using the $oOption object
$aSettings = array (
    array (
        'text'  => $strDefaultBanners,
        'items' => array (
            array (
                'type'    => 'text',
                'name'    => 'default_banner_image_url',
                'text'    => $strDefaultBannerUrl,
                'size'    => 35,
                'check'   => 'url'
            ),
            array (
                'type'    => 'break'
            ),
            array (
                'type'    => 'text',
                'name'    => 'default_banner_destination_url',
                'text'    => $strDefaultBannerDestination,
                'size'    => 35,
                'check'   => 'url'
            )
        )
    ),
    array (
        'text'  => $strTypeHtmlSettings,
        'items' => array (
            array (
                'type'    => 'checkbox',
                'name'    => 'auto_alter_html_banners_for_click_tracking',
                'text'    => $strTypeHtmlAuto
            )
        )
    ),
    array (
        'text'  => $strWeightDefaults,
        'items' => array (
            array (
                'type'  => 'text',
                'name'  => 'default_banner_weight',
                'text'  => $strDefaultBannerWeight,
                'size'  => 12,
                'check' => 'wholeNumber'
            ),
            array (
                'type'  => 'break'
            ),
            array (
                'type'  => 'text',
                'name'  => 'default_campaign_weight',
                'text'  => $strDefaultCampaignWeight,
                'size'  => 12,
                'check' => 'wholeNumber'
            )
        )
    )
);
$oOptions->show($aSettings, $aErrormessage);

// Display the page footer
phpAds_PageFooter();

?>