<?php // $Id$

/**
 * This page lists all the instances of subcourse in a particular course
 *
 * @author David Mudrak <david.mudrak@gmail.com>
 * @version $Id$
 * @package mod/subcourse
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);   // course id

if (! $course = get_record("course", "id", $id)) {
    error("Course ID is incorrect");
}

require_login($course->id);

add_to_log($course->id, "subcourse", "view all", "index.php?id=$course->id", "");


/// Get all required stringssubcourse

$strsubcourses = get_string("modulenameplural", "subcourse");
$strsubcourse  = get_string("modulename", "subcourse");


/// Print the header

$navlinks = array();
$navlinks[] = array('name' => $strsubcourses, 'link' => '', 'type' => 'activity');
$navigation = build_navigation($navlinks);

print_header_simple("$strsubcourses", "", $navigation, "", "", true, "", navmenu($course));

/// Get all the appropriate data

if (! $subcourses = get_all_instances_in_course("subcourse", $course)) {
    notice("There are no subcourses", "../../course/view.php?id=$course->id");
    die;
}

/// Print the list of instances (your module will probably extend this)

$timenow = time();
$strname  = get_string("name");
$strweek  = get_string("week");
$strtopic  = get_string("topic");

if ($course->format == "weeks") {
    $table->head  = array ($strweek, $strname);
    $table->align = array ("center", "left");
} else if ($course->format == "topics") {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ("center", "left", "left", "left");
} else {
    $table->head  = array ($strname);
    $table->align = array ("left", "left", "left");
}

foreach ($subcourses as $subcourse) {
    if (!$subcourse->visible) {
        //Show dimmed if the mod is hidden
        $link = "<a class=\"dimmed\" href=\"view.php?id=$subcourse->coursemodule\">$subcourse->name</a>";
    } else {
        //Show normal if the mod is visible
        $link = "<a href=\"view.php?id=$subcourse->coursemodule\">$subcourse->name</a>";
    }

    if ($course->format == "weeks" or $course->format == "topics") {
        $table->data[] = array ($subcourse->section, $link);
    } else {
        $table->data[] = array ($link);
    }
}

echo "<br />";

print_table($table);

/// Finish the page

print_footer($course);

?>
