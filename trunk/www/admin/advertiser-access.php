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
require_once MAX_PATH . '/lib/OA/Dal.php';
require_once MAX_PATH . '/lib/max/Admin/Languages.php';
require_once MAX_PATH . '/www/admin/config.php';
require_once MAX_PATH . '/www/admin/lib-statistics.inc.php';
require_once MAX_PATH . '/lib/OA/Session.php';
require_once MAX_PATH . '/lib/OA/Admin/Menu.php';
require_once MAX_PATH . '/lib/max/other/html.php';

// Security check
OA_Permission::enforceAccount(OA_ACCOUNT_MANAGER, OA_ACCOUNT_ADVERTISER);
OA_Permission::enforceAccountPermission(OA_ACCOUNT_ADVERTISER, OA_PERM_SUPER_ACCOUNT);
OA_Permission::enforceAccessToObject('clients', $clientid);

/*-------------------------------------------------------*/
/* HTML framework                                        */
/*-------------------------------------------------------*/

if (!empty($clientid)) {
	if (OA_Permission::isAccount(OA_ACCOUNT_MANAGER)) {
		OA_Admin_Menu::setAdvertiserPageContext($clientid, 'advertiser-access.php');
		phpAds_PageShortcut($strClientHistory, 'stats.php?entity=advertiser&breakdown=history&clientid='.$clientid, 'images/icon-statistics.gif');
        MAX_displayAdvertiserBreadcrumbs($clientid);
		phpAds_PageHeader("4.1.5");
		$aTabSections = array("4.1.2", "4.1.3");
        // Conditionally display conversion tracking values
		if ($conf['logging']['trackerImpressions']) {
		    $aTabSections[] = "4.1.4";
		}
		$aTabSections[] = "4.1.5";
		phpAds_ShowSections($aTabSections);
	} else {
		phpAds_PageHeader('2.3');
		MAX_displayAdvertiserBreadcrumbs($clientid);
        $sections = array();
    	if (OA_Permission::hasPermission(OA_PERM_BANNER_ACTIVATE) || OA_Permission::hasPermission(OA_PERM_BANNER_EDIT)) {
        	$sections[] = '2.2';
    	}
        $sections[] = '2.3';
    	phpAds_ShowSections($sections);
	}
} else {
    MAX_displayInventoryBreadcrumbs(array(array("name" => phpAds_getClientName($clientid))), 
                                    "advertiser");
    phpAds_PageHeader("4.1.1");
	phpAds_ShowSections(array("4.1.1"));
}
$tabindex = 1;


/*-------------------------------------------------------*/
/* Main code                                             */
/*-------------------------------------------------------*/

require_once MAX_PATH . '/lib/OA/Admin/Template.php';

$oTpl = new OA_Admin_Template('advertiser-access.html');

$oTpl->assign('infomessage', OA_Session::getMessage());

$oTpl->assign('entityIdName', 'clientid');
$oTpl->assign('entityIdValue', $clientid);
$oTpl->assign('editPage', 'advertiser-user.php');
$oTpl->assign('unlinkPage', 'advertiser-user-unlink.php');

$doUsers = OA_Dal::factoryDO('users');
$oTpl->assign('users', array('aUsers' => $doUsers->getAccountUsersByEntity('clients', $clientid)));
$oTpl->display();

/*-------------------------------------------------------*/
/* HTML framework                                        */
/*-------------------------------------------------------*/

phpAds_PageFooter();

?>