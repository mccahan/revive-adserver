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

/**
 * @package    OpenXDll
 * @author     Andriy Petlyovanyy <apetlyovanyy@lohika.com>
 *
 */

// Require the following classes:
require_once MAX_PATH . '/lib/OA/Dll.php';
require_once MAX_PATH . '/lib/OA/Dll/CampaignInfo.php';
require_once MAX_PATH . '/lib/OA/Dal/Statistics/Campaign.php';


/**
 * The OA_Dll_Campaign class extends the base OA_Dll class.
 *
 */

class OA_Dll_Campaign extends OA_Dll
{
    /**
     * This method sets the CampaignInfo from a data array.
     *
     * @access private
     *
     * @param OA_Dll_CampaignInfo &$oCampaign
     * @param array $campaignData
     *
     * @return boolean
     */
    function _setCampaignDataFromArray(&$oCampaign, $campaignData)
    {
        $campaignData['campaignId']   = $campaignData['campaignid'];
        $campaignData['campaignName'] = $campaignData['campaignname'];
        $campaignData['advertiserId'] = $campaignData['clientid'];
        $campaignData['startDate']    = $campaignData['activate'];
        $campaignData['endDate']      = $campaignData['expire'];
        $campaignData['impressions']  = $campaignData['views'];

        $oCampaign->readDataFromArray($campaignData);
        return  true;
    }

    /**
     * This method performs data validation for a campaign, for example to check
     * that an email address is an email address. Where necessary, the method connects
     * to the OA_Dal to obtain information for other business validations.
     *
     * @access private
     *
     * @param OA_Dll_CampaignInfo $oCampaign
     *
     * @return boolean
     *
     */
    function _validate(&$oCampaign)
    {
        if (isset($oCampaign->campaignId)) {
            // When modifying a campaign, check correct field types are used and the campaignID exists.
            if (!$this->checkStructureRequiredIntegerField($oCampaign, 'campaignId') ||
                !$this->checkIdExistence('campaigns', $oCampaign->campaignId)) {
                return false;
            }

            if (!$this->checkStructureNotRequiredIntegerField($oCampaign, 'advertiserId')) {
                return false;
            }

            if (isset($oCampaign->advertiserId) &&
                !$this->checkIdExistence('clients', $oCampaign->advertiserId)) {
                return false;
            }
        } else {
            // When adding a campaign, check that the required field 'advertiserId' is correct.
            if (!$this->checkStructureRequiredIntegerField($oCampaign, 'advertiserId') ||
                !$this->checkIdExistence('clients', $oCampaign->advertiserId)) {
                return false;
            }
        }

        // If the campaign has a start date and end date, check the date order is correct.
        if (is_object($oCampaign->startDate) && is_object($oCampaign->endDate)) {
            if (!$this->checkDateOrder($oCampaign->startDate, $oCampaign->endDate)) {
                return false;
            }
        }
    /**
     * @todo The error message is awkward - suggest changing to "High or medium priority
     * campaigns cannot have a weight that is greater than zero."
     */
        // Check that the campaign priority and weight are consistent.
        // High priority is between 1 and 10.
        if (isset($oCampaign->priority) &&
            (($oCampaign->priority >= 1) && ($oCampaign->priority <= 10)) &&
            isset($oCampaign->weight) && ($oCampaign->weight > 0)) {

            $this->raiseError('The weight could not be greater than zero for'.
                                ' high or medium priority campaigns');
            return false;
        }

        if (!$this->checkStructureNotRequiredStringField($oCampaign, 'campaignName', 255) ||
            !$this->checkStructureNotRequiredIntegerField($oCampaign, 'impressions') ||
            !$this->checkStructureNotRequiredIntegerField($oCampaign, 'clicks') ||
            !$this->checkStructureNotRequiredIntegerField($oCampaign, 'priority') ||
            !$this->checkStructureNotRequiredIntegerField($oCampaign, 'weight')) {

            return false;
        } else {
            return true;
        }

    }

    /**
     * This method performs data validation for statistics methods (campaignId, date).
     *
     * @access private
     *
     * @param integer  $campaignId
     * @param date     $oStartDate
     * @param date     $oEndDate
     *
     * @return boolean
     *
     */
    function _validateForStatistics($campaignId, $oStartDate, $oEndDate)
    {
        if (!$this->checkIdExistence('campaigns', $campaignId) ||
            !$this->checkDateOrder($oStartDate, $oEndDate)) {

            return false;
        } else {
            return true;
        }
    }

    /**
     * This function calls a method in the OA_Dll class which checks permissions.
     *
     * @access public
     *
     * @param integer $campaignId  Campaign ID
     *
     * @return boolean  False if access is denied and true if allowed.
     */
    function checkStatisticsPermissions($campaignId)
    {
       if (!$this->checkPermissions($this->aAllowAdvertiserAndAbovePerm,
            'campaigns', $campaignId)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * This method modifies an existing campaign. Undefined fields do not change
     * and defined fields with a NULL value also remain unchanged.
     *
     * @access public
     *
     * @param OA_Dll_CampaignInfo &$oCampaign <br />
     *          <b>For adding</b><br />
     *          <b>Required properties:</b> advertiserId<br />
     *          <b>Optional properties:</b> campaignName, startDate, endDate, impressions, clicks, priority, weight<br />
     *
     *          <b>For modify</b><br />
     *          <b>Required properties:</b> campaignId<br />
     *          <b>Optional properties:</b> advertiserId, campaignName, startDate, endDate, impressions, clicks, priority, weight<br />
     *
     * @return boolean  True if the operation was successful
     *
     */
    function modify(&$oCampaign)
    {
        if (!isset($oCampaign->campaignId)) {
            // Add
            $oCampaign->setDefaultForAdd();
            if (!$this->checkPermissions(
                array(OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER),
                'clients', $oCampaign->advertiserId)) {

                return false;
            }
        } else {
            // Edit
            if (!$this->checkPermissions(
                array(OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER),
                'campaigns', $oCampaign->campaignId)) {

                return false;
            }
        }

        $oStartDate    = $oCampaign->startDate;
        $oEndDate      = $oCampaign->endDate;
        $campaignData  =  (array) $oCampaign;

        $campaignData['campaignid']   = $oCampaign->campaignId;
        $campaignData['campaignname'] = $oCampaign->campaignName;
        $campaignData['clientid']     = $oCampaign->advertiserId;
        if (is_object($oStartDate)) {
            $campaignData['activate'] = $oStartDate->format("%Y-%m-%d");
        }
        if (is_object($oEndDate)) {
            $campaignData['expire']   = $oEndDate->format("%Y-%m-%d");
        }

        $campaignData['views']        = $oCampaign->impressions;

        if ($this->_validate($oCampaign)) {
            $doCampaign = OA_Dal::factoryDO('campaigns');
            if (!isset($oCampaign->campaignId)) {
                $doCampaign->setFrom($campaignData);
                $oCampaign->campaignId = $doCampaign->insert();
            } else {
                $doCampaign->get($campaignData['campaignid']);
                $doCampaign->setFrom($campaignData);
                $doCampaign->update();
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * This method deletes an existing campaign.
     *
     * @access public
     *
     * @param integer $campaignId  The ID of the campaign to delete
     *
     * @return boolean  True if the operation was successful
     *
     */
    function delete($campaignId)
    {
        if (!$this->checkPermissions(
            array(OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER),
            'campaigns', $campaignId)) {

            return false;
        }

        if (!$this->checkIdExistence('campaigns', $campaignId)) {
            return false;
        }

        $doCampaign = OA_Dal::factoryDO('campaigns');
        $doCampaign->campaignid = $campaignId;
        $result = $doCampaign->delete();

        if ($result) {
            return true;
        } else {
            $this->raiseError('Unknown campaignId Error');
            return false;
        }
    }

    /**
     * This method returns CampaignInfo for a specified campaign.
     *
     * @access public
     *
     * @param int $campaignId
     * @param OA_Dll_CampaignInfo &$oCampaign
     *
     * @return boolean
     */
    function getCampaign($campaignId, &$oCampaign)
    {
        if ($this->checkIdExistence('campaigns', $campaignId)) {
            if (!$this->checkPermissions(null, 'campaigns', $campaignId)) {
                return false;
            }
            $doCampaign = OA_Dal::factoryDO('campaigns');
            $doCampaign->get($campaignId);
            $campaignData = $doCampaign->toArray();

            $oCampaign = new OA_Dll_CampaignInfo();

            $this->_setCampaignDataFromArray($oCampaign, $campaignData);
            return true;

        } else {

            $this->raiseError('Unknown campaignId Error');
            return false;
        }
    }

    /**
     * This method returns a list of campaigns for a specified advertiser.
     *
     * @access public
     *
     * @param int $advertiserId
     * @param array &$aCampaignList
     *
     * @return boolean
     */
    function getCampaignListByAdvertiserId($advertiserId, &$aCampaignList)
    {
        $aCampaignList = array();

        if (!$this->checkIdExistence('clients', $advertiserId)) {
                return false;
        }

        if (!$this->checkPermissions(null, 'clients', $advertiserId)) {
            return false;
        }

        $doCampaign = OA_Dal::factoryDO('campaigns');
        $doCampaign->clientid = $advertiserId;
        $doCampaign->find();

        while ($doCampaign->fetch()) {
            $campaignData = $doCampaign->toArray();

            $oCampaign = new OA_Dll_CampaignInfo();
            $this->_setCampaignDataFromArray($oCampaign, $campaignData);

            $aCampaignList[] = $oCampaign;
        }
        return true;
    }

    /**
     * This method returns daily statistics for a campaign for a specified period.
     *
     * @access public
     *
     * @param integer $campaignId The ID of the campaign to view statistics for
     * @param date $oStartDate The date from which to get statistics (inclusive)
     * @param date $oEndDate The date to which to get statistics (inclusive)
     * @param array &$rsStatisticsData The data returned by the function
     * <ul>
     *   <li><b>day date</b>  The day
     *   <li><b>requests integer</b>  The number of requests for the day
     *   <li><b>impressions integer</b>  The number of impressions for the day
     *   <li><b>clicks integer</b>  The number of clicks for the day
     *   <li><b>revenue decimal</b>  The revenue earned for the day
     * </ul>
     *
     * @return boolean  True if the operation was successful and false if not.
     *
     */
    function getCampaignDailyStatistics($campaignId, $oStartDate, $oEndDate, &$rsStatisticsData)
    {
        if (!$this->checkStatisticsPermissions($campaignId)) {
            return false;
        }

        if ($this->_validateForStatistics($campaignId, $oStartDate, $oEndDate)) {
            $dalCampaign = new OA_Dal_Statistics_Campaign;
            $rsStatisticsData = $dalCampaign->getCampaignDailyStatistics($campaignId,
                $oStartDate, $oEndDate);

            return true;
        } else {
            return false;
        }
    }

    /**
     * This method returns banner statistics for a campaign for a specified period.
     *
     * @access public
     *
     * @param integer $campaignId The ID of the campaign to view statistics for
     * @param date $oStartDate The date from which to get statistics (inclusive)
     * @param date $oEndDate The date to which to get statistics (inclusive)
     * @param array &$rsStatisticsData The data returned by the function
     * <ul>
     *   <li><b>advertiserID integer</b> The ID of the advertiser
     *   <li><b>advertiserName string (255)</b> The name of the advertiser
     *   <li><b>campaignID integer</b> The ID of the campaign
     *   <li><b>campaignName string (255)</b> The name of the campaign
     *   <li><b>bannerID integer</b> The ID of the banner
     *   <li><b>bannerName string (255)</b> The name of the banner
     *   <li><b>requests integer</b> The number of requests for the day
     *   <li><b>impressions integer</b> The number of impressions for the day
     *   <li><b>clicks integer</b> The number of clicks for the day
     *   <li><b>revenue decimal</b> The revenue earned for the day
     * </ul>
     *
     * @return boolean  True if the operation was successful and false if not.
     *
     */
    function getCampaignBannerStatistics($campaignId, $oStartDate, $oEndDate, &$rsStatisticsData)
    {
        if (!$this->checkStatisticsPermissions($campaignId)) {
            return false;
        }

        if ($this->_validateForStatistics($campaignId, $oStartDate, $oEndDate)) {
            $dalCampaign = new OA_Dal_Statistics_Campaign;
            $rsStatisticsData = $dalCampaign->getCampaignBannerStatistics($campaignId,
                $oStartDate, $oEndDate);

            return true;
        } else {
            return false;
        }
    }

    /**
     * This method returns publisher statistics for a campaign for a specified period.
     *
     * @access public
     *
     * @param integer $campaignId The ID of the campaign to view statistics for
     * @param date $oStartDate The date from which to get statistics (inclusive)
     * @param date $oEndDate The date to which to get statistics (inclusive)
     * @param array &$rsStatisticsData The data returned by the function
     * <ul>
     *   <li><b>publisherID integer</b> The ID of the publisher
     *   <li><b>publisherName string (255)</b> The name of the publisher
     *   <li><b>requests integer</b> The number of requests for the day
     *   <li><b>impressions integer</b> The number of impressions for the day
     *   <li><b>clicks integer</b> The number of clicks for the day
     *   <li><b>revenue decimal</b> The revenue earned for the day
     * </ul>
     *
     * @return boolean  True if the operation was successful and false if not.
     *
     */
    function getCampaignPublisherStatistics($campaignId, $oStartDate, $oEndDate, &$rsStatisticsData)
    {
        if (!$this->checkStatisticsPermissions($campaignId)) {
            return false;
        }

        if ($this->_validateForStatistics($campaignId, $oStartDate, $oEndDate)) {
            $dalCampaign = new OA_Dal_Statistics_Campaign;
            $rsStatisticsData = $dalCampaign->getCampaignpublisherStatistics($campaignId,
                $oStartDate, $oEndDate);

            return true;
        } else {
            return false;
        }



    }

    /**
     * This method returns zone statistics for a campaign for a specified period.
     *
     * @access public
     *
     * @param integer $campaignId The ID of the campaign to view statistics for
     * @param date $oStartDate The date from which to get statistics (inclusive)
     * @param date $oEndDate The date to which to get statistics (inclusive)
     * @param array &$rsStatisticsData The data returned by the function
     * <ul>
     *   <li><b>publisherID integer</b> The ID of the publisher
     *   <li><b>publisherName string (255)</b> The name of the publisher
     *   <li><b>zoneID integer</b> The ID of the zone
     *   <li><b>zoneName string (255)</b> The name of the zone
     *   <li><b>requests integer</b> The number of requests for the day
     *   <li><b>impressions integer</b> The number of impressions for the day
     *   <li><b>clicks integer</b> The number of clicks for the day
     *   <li><b>revenue decimal</b> The revenue earned for the day
     * </ul>
     *
     * @return boolean  True if the operation was successful and false if not.
     *
     */
    function getCampaignZoneStatistics($campaignId, $oStartDate, $oEndDate, &$rsStatisticsData)
    {
        if (!$this->checkStatisticsPermissions($campaignId)) {
            return false;
        }

        if ($this->_validateForStatistics($campaignId, $oStartDate, $oEndDate)) {
            $dalCampaign = new OA_Dal_Statistics_Campaign;
            $rsStatisticsData = $dalCampaign->getCampaignZoneStatistics($campaignId,
                $oStartDate, $oEndDate);

            return true;
        } else {
            return false;
        }
    }

}

?>