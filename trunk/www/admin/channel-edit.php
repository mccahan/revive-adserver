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
require_once MAX_PATH . '/www/admin/config.php';
require_once MAX_PATH . '/lib/max/other/html.php';
require_once MAX_PATH .'/lib/OA/Admin/UI/component/Form.php';


// Register input variables
phpAds_registerGlobalUnslashed('name', 'description', 'comments', 'submit', 
    'affiliateid','agencyid', 'channelid');

/*-------------------------------------------------------*/
/* Affiliate interface security                          */
/*-------------------------------------------------------*/

OA_Permission::enforceAccount(OA_ACCOUNT_MANAGER);
OA_Permission::enforceAccessToObject('channel', $channelid, true);

// Initalise data
$doChannel = OA_Dal::factoryDO('channel');
if (!empty($channelid)) {
    $doChannel->get($channelid);
    $channel = $doChannel->toArray();    
}
else {
    //for ne channels set affiliate id (if any)
    $channel['affiliateid'] = $affiliateid;    
}



/*-------------------------------------------------------*/
/* MAIN REQUEST PROCESSING                               */
/*-------------------------------------------------------*/
//build form
$channelForm = buildChannelForm($channel);

if ($channelForm->validate()) {
    //process submitted values
    processForm($channelForm);
}
else { //either validation failed or form was not submitted, display the form
    displayPage($channel, $channelForm);
}

/*-------------------------------------------------------*/
/* Build form                                            */
/*-------------------------------------------------------*/
function buildChannelForm($channel)
{
    $form = new OA_Admin_UI_Component_Form("channelform", "POST", $_SERVER['PHP_SELF']);
    $form->forceClientValidation(true);
    
    $form->addElement('hidden', 'agencyid', OA_Permission::getAgencyId());
    $form->addElement('hidden', 'affiliateid', $channel['affiliateid']);
    $form->addElement('hidden', 'channelid', $channel['channelid']);

    $form->addElement('header', 'header_basic', $GLOBALS['strBasicInformation']);
    $form->addElement('text', 'name', $GLOBALS['strName']);
    $form->addElement('text', 'description', $GLOBALS['strDescription']);
    $form->addElement('textarea', 'comments', $GLOBALS['strComments']);
        
    $form->addElement('controls', 'form-controls');
    $form->addElement('submit', 'submit', $GLOBALS['strSaveChanges']);
    
    //set form values 
    $form->setDefaults($channel);
    
    //validation rules
    $translation = new OA_Translation();
    $nameRequiredMsg = $translation->translate($GLOBALS['strXRequiredField'], array($GLOBALS['strName'])); 
    $form->addRule('name', $nameRequiredMsg, 'required');

    
    return $form;    
    
}


/*-------------------------------------------------------*/
/* Process submitted form                                */
/*-------------------------------------------------------*/
function processForm($form) 
{
    $aFields = $form->exportValues();

    if (empty($aFields['affiliateid'])) {
        $aFields['affiliateid'] = 0;
    }
    if ($aFields['$channelid']) {
        $doChannel = OA_Dal::factoryDO('channel');
        $doChannel->get($aFields['channelid']);
        $doChannel->name = $aFields['name'];
        $doChannel->description = $aFields['description'];
        $doChannel->comments = $aFields['comments'];
        $ret = $doChannel->update();
    } 
    else {
        $doChannel = OA_Dal::factoryDO('channel');
        $doChannel->agencyid = $aFields['agencyid'];
        $doChannel->affiliateid = $aFields['affiliateid'];
        $doChannel->name = $aFields['name'];
        $doChannel->description = $aFields['description'];
        $doChannel->comments = $aFields['comments'];
        $doChannel->compiledlimitation = 'true';
        $doChannel->acl_plugins = 'true';
        $doChannel->active = 1;
        $ret = $aFields['channelid'] = $doChannel->insert();
    }

    if ($ret) {
        if (!empty($aFields['affiliateid'])) {
            header("Location: channel-acl.php?affiliateid=".$aFields['affiliateid']."&channelid=".$aFields['channelid']);
        } else {
            header("Location: channel-acl.php?channelid=".$aFields['channelid']);
        }
        exit;
    }
}

/*-------------------------------------------------------*/
/* Display page                                          */
/*-------------------------------------------------------*/
function displayPage($channel, $form)
{
    $pageName = basename($_SERVER['PHP_SELF']);
    $agencyId = OA_Permission::getAgencyId();
    
    // Obtain the needed data
    if (!empty($channel['affiliateid'])) {
        $aEntities = array('agencyid' => $agencyid, 'affiliateid' => $channel['affiliateid'], 'channelid' => $channel['channelid']);
        // Editing a channel at the publisher level; Only use the
        // channels at this publisher level for the navigation bar
        $aOtherChannels = Admin_DA::getChannels(array('publisher_id' => $channel['affiliateid']));
    } else {
        $aEntities = array('agencyid' => $agencyid, 'channelid' => $channel['channelid']);
        // Editing a channel at the agency level; Only use the
        // channels at this agency level for the navigation bar
        $aOtherChannels = Admin_DA::getChannels(array('agency_id' => $agencyId, 'channel_type' => 'agency'));
    }
    //show header and breadcrumbs
    MAX_displayNavigationChannel($pageName, $aOtherChannels, $aEntities);

    
    //get template and display form
    $oTpl = new OA_Admin_Template('channel-edit.html');
    $oTpl->assign('form', $form->serialize());
    $oTpl->assign('formId', $form->getId());
    $oTpl->display();
    
    
    //show footer
    phpAds_PageFooter();
}
?>