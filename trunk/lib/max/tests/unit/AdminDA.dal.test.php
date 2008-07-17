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
 * @package    MaxDal
 * @subpackage TestSuite
 * @author     Demian Turner <demian@m3.net>
 */

require_once MAX_PATH . '/lib/max/Admin_DA.php';
require_once MAX_PATH . '/lib/pear/Date.php';
require_once 'Text/Password.php';
require_once MAX_PATH . '/lib/max/Dal/tests/util/DalUnitTestCase.php';
require_once MAX_PATH . '/lib/OA/Dal/DataGenerator.php';
require_once MAX_PATH .'/www/admin/lib-zones.inc.php';

/**
 * A class for testing the Admin_DA class.
 */
class Admin_DaTest extends DalUnitTestCase
{
    /** The last campaign id generated by _generateStats(). */
    var $campaignId;

    /** The last agency id generated by _generateStats(). */
    var $agencyId;

    /** The last banner id generated by _generateStats(). */
    var $bannerId;

    /** The last client id generated by _generateStats(). */
    var $clientId;

    /** The last affiliate id generated by _generateStats(). */
    var $affiliateId;

    /** The last zone id generated by _generateStats(). */
    var $zoneId;

    // +---------------------------------------+
    // | Utility methods                       |
    // |                                       |
    // | for db connections and last inserted  |
    // | IDs                                   |
    // +---------------------------------------+
    var $dbh = null;

    function Admin_DaTest()
    {
        $this->UnitTestCase();
        $this->dbh =& OA_DB::singleton();
    }

    function getLastRecordInserted($tableName, $tableIndexField)
    {
        $conf = $GLOBALS['_MAX']['CONF'];
        $tableName = $conf['table']['prefix'] . $tableName;
        $sql = "SELECT MAX($tableIndexField) AS max FROM ".$dbh->quoteIdentifier($tableName, true);
        $result = $dbh->getRow($sql, array(), DB_FETCHMODE_ASSOC);

        return (int) $result['max'];
    }

    function _haveStats($array)
    {
        if (!is_array($array))
        {
            return false;
        }
        $stats = each($array);
        return (is_array($stats)
                && isset($stats[1])
                && isset($stats[1]['sum_requests'])
                && isset($stats[1]['sum_views'])
                && isset($stats[1]['sum_clicks'])
                && isset($stats[1]['sum_conversions'])
                );
    }


    // +---------------------------------------+
    // | Test relational/constraint-related    |
    // | private methods                       |
    // +---------------------------------------+

    function test_getStatsColumns()
    {
        $entities = array(
         'ad',
         'advertiser',
         'agency',
         'placement',
         'publisher',
         'zone',
         );
        foreach ($entities as $entity) {
            $ret = SqlBuilder::_getStatsColumns($entity);
            $this->assertTrue(is_array($ret));
            $this->assertEqual(count($ret), 5); //  each hash should have 5 elements
            $lastElement = array_pop($ret);
             //   last element should be the entity_id
            $this->assertEqual($entity . '_id', $lastElement);
        }
    }

    function test_getColumns()
    {
        //  load hash representing data structure
        require MAX_PATH . '/lib/max/data/data.entities.php';
        foreach ($entities as $entity => $hash) {
            $ret = SqlBuilder::_getColumns($entity, array(), true);
            // Sort! Database column order is not relevant
            $hash = sort($hash);
            $ret = sort($ret);
            $this->assertEqual($hash, $ret);
        }
    }

    function test_getPrimaryTable()
    {
        require MAX_PATH . '/lib/max/data/data.entities.php';
        foreach ($entities as $entity => $hash) {
            $ret = SqlBuilder::_getPrimaryTable($entity);
            $this->assertTrue(is_array($ret));
            $this->assertTrue(count($ret) == 1);
            $keys = array_keys($ret);
            $vals = array_values($ret);
            $this->assertTrue(is_string($keys[0]));
            $this->assertTrue(!is_null($vals[0]));
        }
    }

    function test_getTables()
    {
        require MAX_PATH . '/lib/max/data/data.entities.php';
        foreach ($entities as $entity => $hash) {
            $ret = SqlBuilder::_getTables($entity, array());
            $this->assertTrue(is_array($ret));
            $this->assertTrue(count($ret) == 1);
            $keys = array_keys($ret);
            $vals = array_values($ret);
            $this->assertTrue(is_string($keys[0]));
            $this->assertTrue(!is_null($vals[0]));
        }
    }

    function test_getLimitations()
    {
        require MAX_PATH . '/lib/max/data/data.entities.php';
        foreach ($entities as $entity => $hash) {
            $ret = SqlBuilder::_getLimitations($entity, array_flip($hash));
            $this->assertTrue(is_array($ret));
        }

    }

    // +---------------------------------------+
    // | Test SQL-related private methods      |
    // |                                       |
    // +---------------------------------------+
    function test_insert()
    {
        $aVariables = array(
            'affiliateid'  => 23,
            'zonename'     => 'foo',
            'description'  => 'this is the desc',
            'category'     => 0,
            'ad_selection' => 0,
            'chain'        => 0,
            'prepend'      => 0,
            'append'       => 0,
            'updated'      => '2007-04-03 16:41:15'
        );
        $ret = SqlBuilder::_insert('zones', $aVariables);
        $this->assertTrue(is_int($ret));
        $this->assertTrue($ret > 0);
    }

    function test_select()
    {
    }

//    $aActiveAdvertisers = Admin_DA::_getEntities('advertiser', $aParams


    // +---------------------------------------+
    // | Test public methods                   |
    // |                                       |
    // +---------------------------------------+

    // +---------------------------------------+
    // | placementZones                        |
    // +---------------------------------------+
    function testAddPlacementZone()
    {
        // starting the transaction or subtransaction
        TestEnv::startTransaction();

        $ret = Admin_DA::addPlacementZone(array('zone_id' => 1, 'placement_id' => 2));
        $this->assertTrue(is_int($ret));
        $this->assertTrue($ret > 0);

        TestEnv::rollbackTransaction();
    }

    function testPlacementZones()
    {

        TestEnv::startTransaction();

        $ret = Admin_DA::addPlacementZone(array('zone_id' => rand(1,999), 'placement_id' => rand(1,999)));
        $this->assertTrue(is_int($ret));
        $this->assertTrue($ret > 0);

        $retVar = Admin_DA::getPlacementZones(array('placement_zone_assoc_id' => $ret));

        $this->assertTrue(is_array($retVar[$ret]));
        TestEnv::rollbackTransaction();
    }

    // +---------------------------------------+
    // | limitations                           |
    // +---------------------------------------+
    function testGetDeliveryLimitations()
    {
        //  FIXME: needs real data

        TestEnv::startTransaction();

        $ret = Admin_DA::getDeliveryLimitations(array('zone_id' => 1, 'placement_id' => 2));
        $this->assertTrue(is_array($ret));

        TestEnv::rollbackTransaction();
    }


    function testGetVariables()
    {
        $variableId = Admin_DA::addVariable(array(
            'trackerid' => rand(1,999),
            'name' => 'foo',
            'description' => 'bar',
            'datatype' => 'string',
            'purpose' => 'basket_value',
            ));
        $this->assertTrue(is_int($variableId));
        $retVar = Admin_DA::getVariables(array('variableid' => $variableId));
        $this->assertTrue(is_array($retVar[$variableId]));
    }


    function testAddVariable()
    {

        TestEnv::startTransaction();

        $ret = Admin_DA::addVariable(array(
            'trackerid' => rand(1,999),
            'name' => 'foo',
            'description' => 'bar',
            'datatype' => 'string',
            'purpose' => 'basket_value',
            ));
        $this->assertTrue(is_int($ret));
        $this->assertTrue($ret > 0);

        TestEnv::rollbackTransaction();
    }

    // +---------------------------------------+
    // | trackers                              |
    // +---------------------------------------+
    function testGetTracker()
    {
        $id = Admin_DA::addTracker(array(
            'trackername' => 'foo',
            'description' => 'bar',
            'clientid' => 0,
            'appendcode' => 'a'
            ));

        $ret = Admin_DA::getTracker($id);
        // should look like this
        /*
        Array
        (
            [advertiser_id] => 123
            [tracker_id] => 11
            [name] => sdfasdf
            [description] => desc
            [viewwindow] => 22
            [clickwindow] => 33
            [blockwindow] => 55
        )
        */
        $this->assertTrue(is_array($ret));
        $this->assertTrue(count($ret));
        $this->assertTrue(array_key_exists('advertiser_id', $ret));
        $this->assertTrue(array_key_exists('tracker_id', $ret));
        $this->assertTrue(array_key_exists('name', $ret));
        $this->assertTrue(array_key_exists('description', $ret));
        $this->assertTrue(array_key_exists('viewwindow', $ret));
        $this->assertTrue(array_key_exists('clickwindow', $ret));
        $this->assertTrue(array_key_exists('blockwindow', $ret));
    }

    function testAddTracker()
    {

        TestEnv::startTransaction();

        $ret = Admin_DA::addTracker(array(
            'trackername' => 'foo',
            'description' => 'bar',
            'clientid' => 0,
            'appendcode' => 'a'
            ));
        $this->assertTrue(is_int($ret));
        $this->assertTrue($ret > 0);

        TestEnv::rollbackTransaction();
    }

    function testDuplicateTracker()
    {

        TestEnv::startTransaction();

        $trackerId = Admin_DA::addTracker(array(
            'trackername' => 'foo',
            'description' => 'bar',
            'clientid' => 0,
            'appendcode' => 'a'
            ));
        $this->assertTrue(is_int($trackerId));
        $this->assertTrue($trackerId > 0);

        $tracker1 = Admin_DA::getTracker($trackerId);

        $ret = Admin_DA::duplicateTracker($trackerId);
        $this->assertTrue(is_int($ret));
        $this->assertTrue($ret > 0);

        $tracker2 = Admin_DA::getTracker($trackerId);
        $this->assertTrue(is_array($tracker2));
        $this->assertTrue(count($ret));

        //  compare two trackers
        $this->assertEqual($tracker1, $tracker2);

        TestEnv::rollbackTransaction();
    }
    // +---------------------------------------+
    // | placements                            |
    // +---------------------------------------+
    function testGetPlacement()
    {
        $id = Admin_DA::addPlacement(array(
            'campaignname' => 'foo',
            'clientid' => 0,
            'views' => rand(1,9999),
            'clicks' => rand(1,9999),
            'conversions' => rand(1,9999),
            'activate' => '2007-03-29',
            'expire' => '2007-04-10'
            ));

        $ret = Admin_DA::getPlacement($id);

        // should look like this
        /*
        Array
        (
            [advertiser_id] => 123
            [placement_id] => 1
            [name] => mycampaign
            [active] => t
            [views] => -1
            [clicks] => -1
            [conversions] => -1
            [expire] => 2005-11-01
            [activate] => 0000-00-00
            [priority] => l
            [weight] => 1
            [target_impression] => 0
            [target_click] => 0
            [target_conversion] => 0
            [anonymous] => f
        )
        */
        $this->assertTrue(is_array($ret));
        $this->assertTrue(count($ret));
        $this->assertTrue(array_key_exists('advertiser_id', $ret));
        $this->assertTrue(array_key_exists('placement_id', $ret));
        $this->assertTrue(array_key_exists('name', $ret));
        $this->assertTrue(array_key_exists('status', $ret));
        $this->assertTrue(array_key_exists('views', $ret));
        $this->assertTrue(array_key_exists('clicks', $ret));
        $this->assertTrue(array_key_exists('conversions', $ret));
        $this->assertTrue(array_key_exists('expire', $ret));
        $this->assertTrue(array_key_exists('activate', $ret));
        $this->assertTrue(array_key_exists('priority', $ret));
        $this->assertTrue(array_key_exists('weight', $ret));
        $this->assertTrue(array_key_exists('target_impression', $ret));
        $this->assertTrue(array_key_exists('target_click', $ret));
        $this->assertTrue(array_key_exists('target_conversion', $ret));
        $this->assertTrue(array_key_exists('anonymous', $ret));
    }

    function testGetPlacements()
    {
        $id = Admin_DA::addPlacement(array(
            'campaignname' => 'foo',
            'clientid' => 0,
            'views' => rand(1,9999),
            'clicks' => rand(1,9999),
            'conversions' => rand(1,9999),
            'activate' => '2007-03-29',
            'expire' => '2007-04-10'
            ));

        $res = Admin_DA::getPlacements(array(
            'placement_id' => $id),
            true);

        $this->assertTrue(is_array($res));
        $this->assertTrue(count($res));
    }


    function testDuplicatePlacement()
    {

        TestEnv::startTransaction();

        $placementId = Admin_DA::addPlacement(array(
            'campaignname' => 'foo',
            'clientid' => 0,
            'views' => rand(1,9999),
            'clicks' => rand(1,9999),
            'conversions' => rand(1,9999),
            'activate' => '2007-03-29',
            'expire' => '2007-04-10'
            ));
        $this->assertTrue(is_int($placementId));
        $this->assertTrue($placementId > 0);

        $placement1 = Admin_DA::getPlacement($placementId);

        $ret = Admin_DA::duplicatePlacement($placementId);
        $this->assertTrue(is_int($ret));
        $this->assertTrue($ret > 0);

        $placement2 = Admin_DA::getPlacement($ret);
        $this->assertTrue(is_array($placement2));
        $this->assertTrue(count($placement2));

        //  compare two placements
        unset($placement1['placement_id']);
        unset($placement2['placement_id']);
        unset($placement1['name']);
        unset($placement2['name']);
        $this->assertEqual($placement1, $placement2);

        TestEnv::rollbackTransaction();
    }
    // +---------------------------------------+
    // | agencies                              |
    // +---------------------------------------+
    function testGetAgency()
    {

        TestEnv::startTransaction();

        $id = Admin_DA::addAgency(array(
            'name' => 'foo',
            'contact' => 'bar',
            'username' => 'user',
            'email' => 'agent@example.com',
            ));

        $ret = Admin_DA::getAgency($id);

        // should look like this
        /*
        Array
        (
            [agency_id] => 1
            [name] => my agency
            [contact] => foo bar
            [email] => foo@example.com
            [username] => Ronald
            [password] => Reagan
            [permissions] => 33
            [language] => chinese
        )
        */
        $this->assertTrue(is_array($ret));
        $this->assertTrue(count($ret));
        $this->assertTrue(array_key_exists('agency_id', $ret));
        $this->assertTrue(array_key_exists('name', $ret));
        $this->assertTrue(array_key_exists('contact', $ret));
        $this->assertTrue(array_key_exists('email', $ret));

        TestEnv::rollbackTransaction();
    }

    function testGetAgencies()
    {

        TestEnv::startTransaction();

        $id = Admin_DA::addAgency(array(
            'name' => 'foo',
            'contact' => 'bar',
            'username' => 'user',
            'email' => 'agent@example.com',
            ));

        $agencies = Admin_DA::getAgencies(array('agency_id' => $id));
        $this->assertTrue(count($agencies) == 1);
        $aKey = array_keys($agencies);
        $id = $aKey[0];
        $this->assertTrue(is_int($id));
        $this->assertTrue(is_array($agencies[$id]));
        $this->assertTrue(array_key_exists('agency_id', $agencies[$id]));
        $this->assertTrue(array_key_exists('name', $agencies[$id]));

        TestEnv::rollbackTransaction();

    }

    // +---------------------------------------+
    // | advertisers                           |
    // +---------------------------------------+

    // +---------------------------------------+
    // | ads                                   |
    // +---------------------------------------+
    // FIMXE: do duplicate and add methods
    function testGetAd()
    {
        $id = Admin_DA::addAd(array(
            'campaignid' => rand(1, 999),
            'active' => 't',
            'contenttype' => 'gif',
            'pluginversion' => rand(1, 999),
            'htmltemplate' => '<html></html>',
            'htmlcache' => '<html><body></body></html>',
            'url' => 'http://www.openx.org',
            'bannertext' => 'text',
            'compiledlimitation' => '',
            'append' => '',
            'acls_updated' => '2007-04-11'
            ));

        $ret = Admin_DA::getAd($id);
        // should look like this
        /*
        Array
        (
            [ad_id] => 1
            [placement_id] => 234234
            [active] => t
            [name] => desc
            [type] => sql
            [contenttype] => gif
            [pluginversion] => 1
            [filename] => sdfasdf
            [imageurl] => http://img.com
            [htmltemplate] => foo
            [htmlcache] => bar
            [width] => 5
            [height] => 6
            [weight] => 3
            [seq] => 1
            [target] =>
            [url] => http://localhost/phpMyAdmin/
            [alt] =>
            [status] =>
            [bannertext] => asdasdfad
            [autohtml] => t
            [adserver] =>
            [block] => 0
            [capping] => 0
            [session_capping] => 0
            [compiledlimitation] => asdfasdf
            [append] => asdfasdf
            [appendtype] => 1
            [bannertype] => 2
            [alt_filename] =>
            [alt_imageurl] =>
            [alt_contenttype] => gif
        )
        */
        $this->assertTrue(is_array($ret));
        $this->assertTrue(count($ret));
        $this->assertTrue(array_key_exists('ad_id', $ret));
        $this->assertTrue(array_key_exists('placement_id', $ret));
        $this->assertTrue(array_key_exists('status', $ret));
        $this->assertTrue(array_key_exists('name', $ret));
        $this->assertTrue(array_key_exists('type', $ret));
        $this->assertTrue(array_key_exists('contenttype', $ret));
        $this->assertTrue(array_key_exists('pluginversion', $ret));
        $this->assertTrue(array_key_exists('filename', $ret));
        $this->assertTrue(array_key_exists('imageurl', $ret));
        $this->assertTrue(array_key_exists('htmltemplate', $ret));
        $this->assertTrue(array_key_exists('htmlcache', $ret));
        $this->assertTrue(array_key_exists('width', $ret));
        $this->assertTrue(array_key_exists('height', $ret));
        $this->assertTrue(array_key_exists('weight', $ret));
        $this->assertTrue(array_key_exists('seq', $ret));
        $this->assertTrue(array_key_exists('target', $ret));
        $this->assertTrue(array_key_exists('url', $ret));
        $this->assertTrue(array_key_exists('alt', $ret));
        $this->assertTrue(array_key_exists('status', $ret));
        $this->assertTrue(array_key_exists('bannertext', $ret));
        $this->assertTrue(array_key_exists('autohtml', $ret));
        $this->assertTrue(array_key_exists('adserver', $ret));
        $this->assertTrue(array_key_exists('block', $ret));
        $this->assertTrue(array_key_exists('capping', $ret));
        $this->assertTrue(array_key_exists('session_capping', $ret));
        $this->assertTrue(array_key_exists('compiledlimitation', $ret));
        $this->assertTrue(array_key_exists('append', $ret));
        $this->assertTrue(array_key_exists('appendtype', $ret));
        $this->assertTrue(array_key_exists('bannertype', $ret));
        $this->assertTrue(array_key_exists('alt_filename', $ret));
        $this->assertTrue(array_key_exists('alt_imageurl', $ret));
        $this->assertTrue(array_key_exists('alt_contenttype', $ret));
    }

    // +---------------------------------------+
    // | zones                                 |
    // +---------------------------------------+


    function newZone()
    {
        $id = Admin_DA::addZone(array(
            'publisher_id' => rand(1, 999),
            'type' => rand(0, 4),
            'name' => 'myzone',
            'category' => 'hard',
            'ad_selection' => '',
            'chain' => '',
            'prepend' => '',
            'append' => ''
            ));
        return $id;
    }


    function testAddZone()
    {
        $ret = $this->newZone();
        $this->assertTrue(is_int($ret));
        $this->assertTrue($ret > 0);
    }

    function testGetZone()
    {
        $conf = $GLOBALS['_MAX']['CONF'];
        $dbh  = &OA_DB::singleton();

        $id = $this->newZone();
        $this->assertTrue(is_int($id));
        $this->assertTrue($id > 0);

        // Get zone record as control element
        $query = 'SELECT * FROM '.$dbh->quoteIdentifier($conf['table']['prefix'].'zones', true).' WHERE zoneid = ' . $id;
        $aZone1 = $dbh->getRow($query);
        $this->assertTrue(is_array($aZone1));

        // Massage results so as to be comparable with Admin_DA::getZone()
        Admin_DA::_switch($aZone1, 'affiliateid', 'publisher_id');
        Admin_DA::_switch($aZone1, 'zonename', 'name');
        Admin_DA::_switch($aZone1, 'delivery', 'type');
        Admin_DA::_switch($aZone1, 'zoneid', 'zone_id');
        unset($aZone1['zonetype']);
        unset($aZone1['updated']);
        $aZone1 = array_filter($aZone1, 'strlen');
        $aZone2 = Admin_DA::getZone($id);

        /*
        Array
        (
            [zone_id] => 80
            [publisher_id] => 508
            [name] => toufreacli
            [type] => 3
            [description] =>
            [width] => 0
            [height] => 0
            [chain] =>
            [prepend] =>
            [append] =>
            [appendtype] => 0
            [forceappend] => f
            [inventory_forecast_type] => 0
        )
        */

        $this->assertTrue(is_array($aZone2));
        $this->assertTrue(count($aZone2) > 0);
        $this->assertTrue(array_key_exists('zone_id', $aZone2));
        $this->assertTrue(array_key_exists('publisher_id', $aZone2));
        $this->assertTrue(array_key_exists('name', $aZone2));
        $this->assertTrue(array_key_exists('type', $aZone2));
        $this->assertTrue(array_key_exists('description', $aZone2));
        $this->assertTrue(array_key_exists('width', $aZone2));
        $this->assertTrue(array_key_exists('height', $aZone2));
        $this->assertTrue(array_key_exists('chain', $aZone2));
        $this->assertTrue(array_key_exists('prepend', $aZone2));
        $this->assertTrue(array_key_exists('append', $aZone2));
        $this->assertTrue(array_key_exists('appendtype', $aZone2));
        $this->assertTrue(array_key_exists('forceappend', $aZone2));
        $this->assertTrue(array_key_exists('inventory_forecast_type', $aZone2));
        $this->assertTrue(array_key_exists('comments', $aZone2));
        $this->assertTrue(array_key_exists('cost', $aZone2));
        $this->assertTrue(array_key_exists('cost_type', $aZone2));
        $this->assertTrue(array_key_exists('cost_variable_id', $aZone2));
        $this->assertTrue(array_key_exists('technology_cost', $aZone2));
        $this->assertTrue(array_key_exists('technology_cost_type', $aZone2));
        $this->assertTrue(array_key_exists('block', $aZone2));
        $this->assertTrue(array_key_exists('capping', $aZone2));
        $this->assertTrue(array_key_exists('session_capping', $aZone2));

        $aZone2 = array_filter($aZone2, 'strlen');
        $this->assertEqual($aZone1, $aZone2);
    }

    //  FIXME: why does getZones() have a v. different return type
    //  from getZone()?
    function testGetZones()
    {
        $conf = $GLOBALS['_MAX']['CONF'];
        $dbh =& OA_DB::singleton();

        $ret = $this->newZone();
        $this->assertTrue(is_int($ret));
        $this->assertTrue($ret > 0);

        //  get zone record as control element
        $query = 'SELECT * FROM '.$dbh->quoteIdentifier($conf['table']['prefix'].'zones', true).' WHERE zoneid = ' . $ret;
        $aZone1 = $dbh->queryRow($query);
        $this->assertTrue(is_array($aZone1));

        //  massage results so as to be comparable with Admin_DA::getZone()
        Admin_DA::_switch($aZone1, 'affiliateid', 'publisher_id');
        Admin_DA::_switch($aZone1, 'zonename', 'name');
        Admin_DA::_switch($aZone1, 'delivery', 'type');
        Admin_DA::_switch($aZone1, 'zoneid', 'zone_id');
        unset($aZone1['zonetype']);
        unset($aZone1['appendtype']);
        unset($aZone1['forceappend']);
        unset($aZone1['inventory_forecast_type']);
        unset($aZone1['height']);
        unset($aZone1['width']);
        unset($aZone1['updated']);
        unset($aZone1['block']);
        unset($aZone1['capping']);
        unset($aZone1['session_capping']);
        unset($aZone1['category']);
        unset($aZone1['is_in_ad_direct']);
        unset($aZone1['rate']);
        unset($aZone1['pricing']);
        $aZone1 = array_filter($aZone1, 'strlen');

        $aZone2 = Admin_DA::getZones(array('zone_id' => $ret));
        /*
        Array
        (
            [80] => Array
                (
                    [zone_id] => 80
                    [publisher_id] => 508
                    [name] => toufreacli
                    [type] => 3
                )

        )
        */
        $this->assertTrue(is_array($aZone2[$ret]));
        $this->assertEqual($aZone1, $aZone2[$ret]);

        $zoneId = $this->newZone();
        $doZones = OA_DAL::staticGetDO('zones', $zoneId);
        $doZones->inventory_forecast_type = 5;
        $doZones->update();
        $aZone = Admin_DA::getZones(array('zone_inventory_forecast_type' => 1));
        $this->assertTrue($aZone); // The returned zone isn't null or false or empty.
    }

    function testDuplicateZone()
    {
        $conf = $GLOBALS['_MAX']['CONF'];
        $dbh =& OA_DB::singleton();
        $id = $this->newZone();
        $this->assertTrue(is_int($id));
        $this->assertTrue($id > 0);

        //  get zone record as control element
        $query = 'SELECT * FROM '.$dbh->quoteIdentifier($conf['table']['prefix'].'zones', true).' WHERE zoneid = ' . $id;
        $aZone1 = $dbh->queryRow($query);
        $this->assertTrue(is_array($aZone1));


        $ret = Admin_DA::duplicateZone($id);
        $this->assertTrue(is_int($ret));
        $this->assertTrue($ret > 0);

        $query = 'SELECT * FROM '.$dbh->quoteIdentifier($conf['table']['prefix'].'zones', true).' WHERE zoneid = ' . $ret;
        $aZone2 = $dbh->queryRow($query);
        $this->assertTrue(is_array($aZone2));

        //  unset zoneid and name as these are unique
        unset($aZone1['zoneid']);
        unset($aZone2['zoneid']);
        unset($aZone1['zonename']);
        unset($aZone2['zonename']);
        unset($aZone1['updated']);
        unset($aZone2['updated']);
        $this->assertEqual($aZone1, $aZone2);
    }

    function testAddCategory()
    {
        $name = & new Text_Password();

        TestEnv::startTransaction();

        $ret = Admin_DA::addCategory(array('name' => $name->create()));
        $this->assertTrue(is_int($ret));
        $this->assertTrue($ret > 0);

        TestEnv::rollbackTransaction();
    }

    function testAddAdCategory()
    {

        TestEnv::startTransaction();

        $ret = Admin_DA::addAdCategory(array('ad_id' => 1, 'category_id' => 2));
        $this->assertTrue(is_int($ret));
        $this->assertTrue($ret > 0);

        TestEnv::rollbackTransaction();
    }

    function testAddAdZone()
    {
        $this->_generateStats();
        $ret = Admin_DA::addAdZone(array('zone_id' => $this->zoneId, 'ad_id' => $this->bannerId));
        $this->assertTrue(is_int($ret));
        $this->assertTrue($ret > 0);
    }

    function testCheckBannerType()
    {
        $this->_generateStats();
        $ret = Admin_DA::addAdZone(array('zone_id' => $this->zoneId, 'ad_id' => $this->bannerId));
        $this->assertTrue(is_int($ret));
        $this->assertTrue($ret > 0);

        //  attempt to add a HTML banner to a text zone which should fail
        $ret = Admin_DA::addAdZone(array('zone_id' => $this->zoneId, 'ad_id' => $this->bannerId2));
        $this->assertTrue(PEAR::isError($ret));

        //  attempt to add a Text banner to a Email/Newsletter zone which should fail
        $ret = Admin_DA::addAdZone(array('zone_id' => $this->zoneId2, 'ad_id' => $this->bannerId));
        $this->assertTrue(PEAR::isError($ret));
    }

    function testdeleteAdZones()
    {

        $this->_generateStats();
        $ret = Admin_DA::addAdZone(array('zone_id' => $this->zoneId, 'ad_id' => $this->bannerId));
        $this->assertTrue(is_int($ret));
        $this->assertTrue($ret > 0);
        Admin_DA::deleteAdZones(array('zone_id' => $this->zoneId, 'ad_id' => $this->adId));
    }

    // +---------------------------------------+
    // | Test helper methods                   |
    // |                                       |
    // +---------------------------------------+
    function test_Switch()
    {
        $input = array(
            'fooName' => 'fooValue',
            'barName' => 'barValue');
        $name = 'fooName';
        $legacyName = 'replacedFooName';
        $output = $input;
        Admin_DA::_switch($output, $name, $legacyName);

        //  determine that the desired field name has been removed
        $this->assertTrue(!array_key_exists($name, $output));

        //  determine that the legacy name has been inserted
        $this->assertTrue(array_key_exists($legacyName, $output));

        //  make sure extra keys not altered
        $this->assertTrue(array_key_exists('barName', $output));

        //  make sure existing value has new key
        $this->assertEqual('fooValue', $output[$legacyName]);

        //  assert key swapped successfully
        $this->assertEqual($input[$name], $output[$legacyName]);
    }


    //  the 3rd arg to any DA call sets the key value of the returned array
    //  this tests that all possible  keys are set correctly
    function testReturnByColumnType()
    {
        $id = Admin_DA::addPlacement(array(
            'campaignname' => 'foo',
            'clientid' => 0,
            'views' => rand(1,9999),
            'clicks' => rand(1,9999),
            'conversions' => rand(1,9999),
            'activate' => '2007-03-29',
            'expire' => '2007-04-10'
            ));

        $ret = Admin_DA::getPlacements(array(
            'placement_id' => $id),
            true);
        $stats = each($ret);
        foreach ($stats[1] as $k => $v) {
            $tmp = Admin_DA::getPlacements(array(
                'placement_id' => $id),
                true,
                $k);
            $resKey = each($tmp);
            $this->assertEqual($resKey['key'], $v);
        }

        $this->assertTrue(is_array($ret));
        $this->assertTrue(count($ret));
    }

    function test_getUniqueName()
    {
        $entities = array();
        for ($x = 0; $x < 20; $x++) {
            $entities[] = array('name' => 'foo_' . $x);
        }
        $control = array('name' => 'foo_7');
        $orig = $control;
        Admin_DA::_getUniqueName($control, $entities, 'copy');
        $this->assertTrue(is_array($control));
        $this->assertTrue(array_key_exists('name', $control));
        $this->assertNotEqual($orig, $control);
    }

    // +---------------------------------------+
    // | Test cache methods                    |
    // |                                       |
    // +---------------------------------------+

    function testFromCacheGetPlacements()
    {
        $id = Admin_DA::addPlacement(array(
            'campaignname' => 'foo',
            'clientid' => 0,
            'views' => rand(1,9999),
            'clicks' => rand(1,9999),
            'conversions' => rand(1,9999),
            'activate' => '2007-03-29',
            'expire' => '2007-04-10'
            ));

        $ret = Admin_DA::fromCache('getPlacements', array(
            'placement_id' => $id));
        $this->assertTrue(is_array($ret));
        $this->assertTrue(count($ret));

        $stats = each($ret);
        $this->assertTrue(is_array($stats));
        if (is_array($stats))
        {
            $this->assertTrue(array_key_exists('advertiser_id', $stats[1]));
            $this->assertTrue(array_key_exists('placement_id', $stats[1]));
            $this->assertTrue(array_key_exists('name', $stats[1]));
            $this->assertTrue(array_key_exists('status', $stats[1]));
        }
    }

    function testFromCacheGetPlacement()
    {
        $id = Admin_DA::addPlacement(array(
            'campaignname' => 'foo',
            'clientid' => 0,
            'views' => rand(1,9999),
            'clicks' => rand(1,9999),
            'conversions' => rand(1,9999),
            'activate' => '2007-03-29',
            'expire' => '2007-04-10'
            ));

        $res = Admin_DA::fromCache('getPlacement', $id);
        $this->assertTrue(is_array($res));
        $this->assertTrue(count($res));
    }

    // +---------------------------------------+
    // | Test stats methods                    |
    // |                                       |
    // +---------------------------------------+
    function testGetPlacementsStats()
    {
        $this->_generateStats();
        $ret = Admin_DA::getPlacementsStats(array('advertiser_id' => $this->clientId));
        $this->assertTrue($this->_haveStats($ret));
        /*
        Array
        (
            [advertiser_id] => 1
            [placement_id] => 1
            [name] => test  campaign
            [active] => t
            [num_children] => 1
            [sum_requests] => 13009
            [sum_views] => 9439
            [sum_clicks] => 14123
            [sum_conversions] => 11575
        )
        */
    }

    function testGetPlacementsStatsTwoParams()
    {
        $this->_generateStats();
        $ret = Admin_DA::getPlacementsStats(array('advertiser_id' => $this->clientId, 'placement_id' => $this->campaignId));
        $this->assertTrue($this->_haveStats($ret));
    }

    function testGetAdvertisersStats()
    {
        $this->_generateStats();
        $ret = Admin_DA::getAdvertisersStats(array('agency_id' => $this->agencyId));
        $this->assertTrue($this->_haveStats($ret));
    }

    function testGetPublishersStats()
    {
        $this->_generateStats();
        $ret = Admin_DA::getPublishersStats(array('agency_id' => $this->agencyId));
        $this->assertTrue($this->_haveStats($ret));
    }

    function testGetZonesStats()
    {
        $this->_generateStats();
        $ret = Admin_DA::getZonesStats(array('publisher_id' => $this->affiliateId));
        $this->assertTrue($this->_haveStats($ret));
    }

    function testGetAdsStats()
    {
        $this->_generateStats();
        $ret = Admin_DA::getAdsStats(array('placement_id' => $this->campaignId));
        $this->assertTrue($this->_haveStats($ret));
    }

    // +---------------------------------------+
    // | Test cache stats methods              |
    // |                                       |
    // +---------------------------------------+
    //  all stats come from data_summary_ad_hourly
    function testFromCacheGetPlacementsStats()
    {
        $this->_generateStats();

        $ret = Admin_DA::fromCache('getPlacementsStats', array('advertiser_id' => $this->clientId));
        $this->assertTrue($this->_haveStats($ret));
    }

    function testFromCacheGetAdvertisersStats()
    {
        $this->_generateStats();

        $res = Admin_DA::fromCache('getAdvertisersStats', array('agency_id' => $this->agencyId));
        $this->assertTrue(is_array($res));
        $this->assertTrue(count($res));
    }

    function testFromCacheGetPublishersStats()
    {
        $this->_generateStats();

        $res = Admin_DA::fromCache('getPublishersStats', array('agency_id' => $this->agencyId));
        $this->assertTrue(is_array($res));
        $this->assertTrue(count($res));
    }

    function testFromCacheGetZonesStats()
    {
        $this->_generateStats();

        $res = Admin_DA::fromCache('getZonesStats', array('publisher_id' => $this->affiliateId));
        $this->assertTrue(is_array($res));
        $this->assertTrue(count($res));
    }

    function testFromCacheGetAdsStats()
    {
        $this->_generateStats();

        $res = Admin_DA::fromCache('getAdsStats', array('placement_id' => $this->campaignId));
        $this->assertTrue(is_array($res));
        $this->assertTrue(count($res));
    }

    function _generateStats()
    {

        $this->aIds = TestEnv::loadData('data_summary_ad_hourly_001');
        $this->agencyId = $this->aIds['agency'][1];
        $this->clientId = $this->aIds['clients'][1];
        $this->campaignId = $this->aIds['campaigns'][1];
        $this->bannerId = $this->aIds['banners'][1];
        $this->bannerId2 = $this->aIds['banners'][2];
        $this->affiliateId = $this->aIds['affiliates'][1];
        $this->zoneId = $this->aIds['zones'][1];
        $this->zoneId2 = $this->aIds['zones'][2];
    }

}

?>