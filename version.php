<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Code fragment to define the version of subcourse
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author
 * @version $Id$
 * @package subcourse
 **/
defined('MOODLE_INTERNAL') || die();

$module->version  = 2011110200;           // If version == 0 then module will not be installed
//$module->version  = 2010032200;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2010031900;  // Requires this Moodle version
$module->cron     = 600;           // Period for cron to check this module (secs)
