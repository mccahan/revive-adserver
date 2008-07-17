<?php

/*
+---------------------------------------------------------------------------+
| Openads v${RELEASE_MAJOR_MINOR}                                                              |
| ============                                                              |
|                                                                           |
| Copyright (c) 2003-2007 Openads Limited                                   |
| For contact details, see: http://www.openads.org/                         |
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
 * @package    Max
 * @subpackage SimulationSuite
 * @author
 */

require_once '../../../init.php';
require_once 'tdconst.php';

function get_file_list($directory, $ext, $strip_ext=false)
{
    $aFiles = array();
    $dh = opendir($directory);
    if ($dh)
    {
        while (false !== ($file = readdir($dh)))
        {
            if (strpos($file, $ext)>0)
            {
                if ($strip_ext)
                {
                    $file = str_replace($ext, '', $file);
                }
                $aFiles[] = $file;
            }
        }
        closedir($dh);
    }
    natcasesort($aFiles);
    return $aFiles;
}




?>