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

// TODO we need this here to allow module testing at Moodle 1.9 servers, remove when 2.0 is out
if (! class_exists('moodle_exception')) {
    /**
     * Base Moodle Exception class
     */
    class moodle_exception extends Exception {
        public $errorcode;
        public $module;
        public $a;
        public $link;
        public $debuginfo;

        /**
         * Constructor
         * @param string $errorcode The name of the string from error.php to print
         * @param string $module name of module
         * @param string $link The url where the user will be prompted to continue. If no url is provided the user will be directed to the site index page.
         * @param object $a Extra words and phrases that might be required in the error string
         * @param string $debuginfo optional debugging information
         */
        function __construct($errorcode, $module='', $link='', $a=NULL, $debuginfo=null) {
            if (empty($module) || $module == 'moodle' || $module == 'core') {
                $module = 'error';
            }

            $this->errorcode = $errorcode;
            $this->module    = $module;
            $this->link      = $link;
            $this->a         = $a;
            $this->debuginfo = $debuginfo;

            $message = get_string($errorcode, $module, $a);

            parent::__construct($message, 0);
        }
    }
}


/**
 * Basic subcourse module exception class
 *
 * @uses moodle_exception
 */
class subcourse_exception extends moodle_exception {
    function __construct($errorcode, $a=NULL, $debuginfo=null) {
        parent::__construct($errorcode, 'subcourse', '', $a, $debuginfo);
    }
}


/**
 * Remote grade_item uses scale which is not global
 *
 * @uses subcourse_exception
 */
class subcourse_localremotescale_exception extends subcourse_exception {
    function __construct($subcourseid, $debuginfo=null) {
        $a = new object();
        $a->subcourseid = $subcourseid;
        parent::__construct('errlocalremotescale', $a, $debuginfo);
    }
}


?>
