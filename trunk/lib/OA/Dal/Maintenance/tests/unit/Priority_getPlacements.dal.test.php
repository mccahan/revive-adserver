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

require_once MAX_PATH . '/lib/OA/Dal/DataGenerator.php';
require_once MAX_PATH . '/lib/OA/Dal/Maintenance/Priority.php';

// pgsql execution time before refactor: 132.40s
// pgsql execution time after refactor: 22.053s

/**
 * A class for testing the getPlacements() method of the non-DB specific
 * OA_Dal_Maintenance_Priority class.
 *
 * @package    OpenXDal
 * @subpackage TestSuite
 * @author     Monique Szpak <monique.szpak@openx.org>
 * @author     Andrew Hill <andrew.hill@openx.org>
 */
class Test_OA_Dal_Maintenance_Priority_getPlacements extends UnitTestCase
{
    /**
     * The constructor method.
     */
    function Test_OA_Dal_Maintenance_Priority_getPlacements()
    {
        $this->UnitTestCase();
    }

    /**
     * A method to test the getPlacements method.
     */
    function testGetPlacements()
    {
        /**
         * @TODO Locate where clean up doesn't happen before this test, and fix!
         */
        TestEnv::restoreEnv();

        $da = new OA_Dal_Maintenance_Priority();
        $this->_generateStatsOne();

        // Test 1 getPlacements method.
        $ret = $da->getPlacements();
        $this->assertTrue(is_array($ret));
        $this->assertTrue(count($ret) == 5);
        $campaign = $ret[0];
        $this->assertTrue(array_key_exists('campaignid', $campaign));
        $this->assertTrue(array_key_exists('views', $campaign));
        $this->assertTrue(array_key_exists('clicks', $campaign));
        $this->assertTrue(array_key_exists('conversions', $campaign));
        $this->assertTrue(array_key_exists('activate', $campaign));
        $this->assertTrue(array_key_exists('expire', $campaign));
        $this->assertTrue(array_key_exists('target_impression', $campaign));
        $this->assertTrue(array_key_exists('target_click', $campaign));
        $this->assertTrue(array_key_exists('target_conversion', $campaign));
        $this->assertTrue(array_key_exists('priority', $campaign));

        // Test 2 getPlacementData method.
        $ret = $da->getPlacementData(1);
        $this->assertTrue(is_array($ret));
        $this->assertTrue(count($ret) == 1);
        $campaign = $ret[0];
        $this->assertTrue(array_key_exists('advertiser_id', $campaign));
        $this->assertTrue(array_key_exists('placement_id', $campaign));
        $this->assertTrue(array_key_exists('name', $campaign));
        $this->assertTrue(array_key_exists('status', $campaign));
        $this->assertTrue(array_key_exists('num_children', $campaign));

        // Test 3 getPlacementStats method.
        $ret = $da->getPlacementStats(1);
        $this->assertTrue(is_array($ret));
        $this->assertTrue(count($ret) == 9);
        $this->assertTrue(array_key_exists('advertiser_id', $ret));
        $this->assertTrue(array_key_exists('placement_id', $ret));
        $this->assertTrue(array_key_exists('name', $ret));
        $this->assertTrue(array_key_exists('status', $ret));
        $this->assertTrue(array_key_exists('num_children', $ret));
        $this->assertTrue(array_key_exists('sum_requests', $ret));
        $this->assertTrue(array_key_exists('sum_views', $ret));
        $this->assertTrue(array_key_exists('sum_clicks', $ret));
        $this->assertTrue(array_key_exists('sum_conversions', $ret));

        DataGenerator::cleanUp();
    }

    /**
     * A method to generate data for testing.
     *
     * @access private
     */
    function _generateStatsOne()
    {
        $oDate = new Date();
        $oDate->addSeconds((SECONDS_PER_DAY * 4));
        $expiryDate1 = $oDate->getYear() . "-" . $oDate->getMonth() . "-" . $oDate->getDay();

        $oDate->addSeconds((SECONDS_PER_DAY * 4));
        $expiryDate2 = $oDate->getYear() . "-" . $oDate->getMonth() . "-" . $oDate->getDay();

        $oDate->subtractSeconds((SECONDS_PER_DAY * 2));
        $expiryDateLessTwoDays = $oDate->getYear() . "-" . $oDate->getMonth() . "-" . $oDate->getDay();

        $doCampaigns = OA_Dal::factoryDO('campaigns');
        $doAcls      = OA_Dal::factoryDO('acls');

        $doBanners   = OA_Dal::factoryDO('banners');
        // default values
        $doBanners->status = OA_ENTITY_STATUS_RUNNING;
        $doBanners->contenttype = 'gif';
        $doBanners->pluginversion = 0;
        $doBanners->storagetype = 'sql';
        $doBanners->filename = '468x60_4.gif';
        $doBanners->imageurl = '';
        $doBanners->htmltemplate = '';
        $doBanners->htmlcache = '';
        $doBanners->width = 468;
        $doBanners->height = 60;
        $doBanners->weight = 1;
        $doBanners->seq = 0;
        $doBanners->target = '';
        $doBanners->url = 'http://www.example.com';
        $doBanners->alt = 'Campaign Alt Text';
        $doBanners->statustext = '';
        $doBanners->bannertext = '';
        $doBanners->description = '';
        $doBanners->autohtml = 'f';
        $doBanners->adserver = '';
        $doBanners->block = 0;
        $doBanners->capping = 0;
        $doBanners->session_capping = 0;
        $doBanners->compiledlimitation = '';
        $doBanners->append = '';
        $doBanners->appendtype = 0;
        $doBanners->bannertype = 0;
        $doBanners->alt_filename = '';
        $doBanners->alt_imageurl = '';
        $doBanners->alt_contenttype = '';
        
        $clientId = DataGenerator::generateOne('clients', true);

        // Add 3 campaigns - haha!
        $doCampaigns->campaignname = 'Test Campaign 1';
        $doCampaigns->clientid = $clientId;
        $doCampaigns->views = 0;
        $doCampaigns->clicks = 400;
        $doCampaigns->conversions = 0;
        $doCampaigns->expire = $expiryDate1;
        $doCampaigns->activate = OA_Dal::noDateString();
        $doCampaigns->status = OA_ENTITY_STATUS_RUNNING;
        $doCampaigns->priority = '3';
        $doCampaigns->weight = 1;
        $doCampaigns->target_impression = 0;
        $doCampaigns->anonymous = 'f';
        $doCampaigns->updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $idCampaign1 = DataGenerator::generateOne($doCampaigns, true);

        $doCampaigns->campaignname = 'Test Campaign 2';
        $doCampaigns->clientid = $clientId;
        $doCampaigns->views = 0;
        $doCampaigns->clicks = 0;
        $doCampaigns->conversions = 400;
        $doCampaigns->expire = OA_Dal::noDateString();
        $doCampaigns->activate = OA_Dal::noDateString();
        $doCampaigns->status = OA_ENTITY_STATUS_RUNNING;
        $doCampaigns->priority = '2';
        $doCampaigns->weight = 1;
        $doCampaigns->target_impression = 0;
        $doCampaigns->anonymous = 'f';
        $doCampaigns->updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $idCampaign2 = DataGenerator::generateOne($doCampaigns, true);

        $doCampaigns->campaignname = 'Test Campaign 3';
        $doCampaigns->clientid = $clientId;
        $doCampaigns->views = 500;
        $doCampaigns->clicks = 0;
        $doCampaigns->conversions = 0;
        $doCampaigns->expire = $expiryDate2;
        $doCampaigns->activate = OA_Dal::noDateString();
        $doCampaigns->status = OA_ENTITY_STATUS_RUNNING;
        $doCampaigns->priority = '3';
        $doCampaigns->weight = 1;
        $doCampaigns->target_impression = 0;
        $doCampaigns->anonymous = 'f';
        $doCampaigns->updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $idCampaign3 = DataGenerator::generateOne($doCampaigns, true);

        $doCampaigns->campaignname = 'Test Campaign 4';
        $doCampaigns->clientid = $clientId;
        $doCampaigns->views = 500;
        $doCampaigns->clicks = 0;
        $doCampaigns->conversions = 401;
        $doCampaigns->expire = OA_Dal::noDateString();
        $doCampaigns->activate = OA_Dal::noDateString();
        $doCampaigns->status = OA_ENTITY_STATUS_RUNNING;
        $doCampaigns->priority = '4';
        $doCampaigns->weight = 2;
        $doCampaigns->target_impression = 0;
        $doCampaigns->anonymous = 'f';
        $doCampaigns->updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $idCampaign4 = DataGenerator::generateOne($doCampaigns, true);

        $doCampaigns->campaignname = 'Test Campaign 5';
        $doCampaigns->clientid = $clientId;
        $doCampaigns->views = 500;
        $doCampaigns->clicks = 0;
        $doCampaigns->conversions = 401;
        $doCampaigns->expire = OA_Dal::noDateString();
        $doCampaigns->activate = OA_Dal::noDateString();
        $doCampaigns->status = OA_ENTITY_STATUS_RUNNING;
        $doCampaigns->priority = '3';
        $doCampaigns->weight = 2;
        $doCampaigns->target_impression = 0;
        $doCampaigns->anonymous = 'f';
        $doCampaigns->updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $idCampaign5 = DataGenerator::generateOne($doCampaigns);

        // Add 1st banner to campaign 1
        $doBanners->campaignid=$idCampaign1;
        $doBanners->alt = 'Campaign1 - Banner 1';
        $doBanners->acls_updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $doBanners->updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $idBanner1 = DataGenerator::generateOne($doBanners);

        // Banner 1, Campaign 1 - acls delivery restrictions
        $doAcls->bannerid = $idBanner1;
        $doAcls->logical = 'and';
        $doAcls->type = 'Time:Date';
        $doAcls->comparison = '!=';
        $doAcls->data = $expiryDateLessTwoDays;
        $doAcls->executionorder = 0;
        $idAcls1 = DataGenerator::generateOne($doAcls);

        $doAcls->bannerid = $idBanner1;
        $doAcls->logical = 'and';
        $doAcls->type = 'Time:Hour';
        $doAcls->comparison = '!=';
        $doAcls->data = 1;
        $doAcls->executionorder = 1;
        $idAcls2 = DataGenerator::generateOne($doAcls);

        // Add 2nd banner to campaign 1
        $doBanners = OA_Dal::factoryDO('banners');
        $doBanners->campaignid=$idCampaign1;
        $doBanners->alt = 'Campaign1 - Banner 2';
        $doBanners->acls_updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $doBanners->updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $idBanner2 = DataGenerator::generateOne($doBanners);

        // Banner 2, Campaign 1 - acls delivery restrictions
        $doAcls->bannerid = $idBanner2;
        $doAcls->logical = 'and';
        $doAcls->type = 'Time:Date';
        $doAcls->comparison = '!=';
        $doAcls->data = $expiryDateLessTwoDays;
        $doAcls->executionorder = 0;
        $idAcls3 = DataGenerator::generateOne($doAcls);

        $doAcls->bannerid = $idBanner2;
        $doAcls->logical = 'and';
        $doAcls->type = 'Time:Hour';
        $doAcls->comparison = '!=';
        $doAcls->data = '1,2';
        $doAcls->executionorder = 1;
        $idAcls4 = DataGenerator::generateOne($doAcls);

        // Add 1st banner to campaign 2  ????
        $doBanners->campaignid=$idCampaign2;
        $doBanners->alt = 'Campaign2 - Banner 1';
        $doBanners->acls_updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $doBanners->updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $idBanner3 = DataGenerator::generateOne($doBanners);

        $doBanners->campaignid=$idCampaign2;
        $doBanners->alt = 'Campaign2 - Banner 2';
        $doBanners->acls_updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $doBanners->updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $idBanner4 = DataGenerator::generateOne($doBanners);

        // Banner 1, Campaign 2 - acls delivery restrictions
        $doAcls->bannerid = $idBanner4;
        $doAcls->logical = 'and';
        $doAcls->type = 'Time:Date';
        $doAcls->comparison = '!=';
        $doAcls->data = $expiryDateLessTwoDays;
        $doAcls->executionorder = 0;
        $idAcls5 = DataGenerator::generateOne($doAcls);

        $doAcls->bannerid = $idBanner4;
        $doAcls->logical = 'and';
        $doAcls->type = 'Time:Day';
        $doAcls->comparison = '!=';
        $doAcls->data = '5';
        $doAcls->executionorder = 1;
        $idAcls6 = DataGenerator::generateOne($doAcls);

        // Add 2nd banner to campaign 2
        $doBanners->campaignid=$idCampaign2;
        $doBanners->alt = 'Campaign2 - Banner 3';
        $doBanners->acls_updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $doBanners->updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $idBanner5 = DataGenerator::generateOne($doBanners);

        // Banner 2, Campaign 2 - acls delivery restrictions
        $doAcls->bannerid = $idBanner5;
        $doAcls->logical = 'or';
        $doAcls->type = 'Time:Date';
        $doAcls->comparison = '!=';
        $doAcls->data = $expiryDateLessTwoDays;
        $doAcls->executionorder = 0;
        $idAcls7 = DataGenerator::generateOne($doAcls);

        $doAcls->bannerid = $idBanner5;
        $doAcls->logical = 'or';
        $doAcls->type = 'Time:Day';
        $doAcls->comparison = '==';
        $doAcls->data = '1,2';
        $doAcls->executionorder = 1;
        $idAcls8 = DataGenerator::generateOne($doAcls);

        // Add 1st banner to campaign 3
        $doBanners->campaignid=$idCampaign3;
        $doBanners->alt = 'Campaign3 - Banner 1';
        $doBanners->acls_updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $doBanners->updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $idBanner6 = DataGenerator::generateOne($doBanners);

        // Banner 1, Campaign 3 - acls delivery restrictions
        $doAcls->bannerid = $idBanner6;
        $doAcls->logical = 'and';
        $doAcls->type = 'Time:Date';
        $doAcls->comparison = '!=';
        $doAcls->data = $expiryDateLessTwoDays;
        $doAcls->executionorder = 0;
        $idAcls9 = DataGenerator::generateOne($doAcls);

        $doAcls->bannerid = $idBanner6;
        $doAcls->logical = 'or';
        $doAcls->type = 'Time:Day';
        $doAcls->comparison = '==';
        $doAcls->data = '5';
        $doAcls->executionorder = 1;
        $idAcls10 = DataGenerator::generateOne($doAcls);

        // Add 2nd banner to campaign 3
        $doBanners->campaignid=$idCampaign3;
        $doBanners->alt = 'Campaign3 - Banner 2';
        $doBanners->acls_updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $doBanners->updated = $oDate->format('%Y-%m-%d %H:%M:%S');
        $idBanner7 = DataGenerator::generateOne($doBanners);

        // Banner 2, Campaign 3 - acls delivery restrictions
        $doAcls->bannerid = $idBanner7;
        $doAcls->logical = 'or';
        $doAcls->type = 'Time:Date';
        $doAcls->comparison = '!=';
        $doAcls->data = $expiryDateLessTwoDays;
        $doAcls->executionorder = 0;
        $idAcls11 = DataGenerator::generateOne($doAcls);

        $doAcls->bannerid = $idBanner7;
        $doAcls->logical = 'or';
        $doAcls->type = 'Time:Hour';
        $doAcls->comparison = '1=';
        $doAcls->data = '1,2';
        $doAcls->executionorder = 1;
        $idAcls12 = DataGenerator::generateOne($doAcls);
    }

}

?>