<?php //$Id$

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
