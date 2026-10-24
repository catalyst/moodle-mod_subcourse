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
 * Keeps track of upgrades to the subcourse module
 *
 * @package     mod_subcourse
 * @category    upgrade
 * @copyright   2008 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Performs upgrade of the database structure and data
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool true
 */
function xmldb_subcourse_upgrade($oldversion = 0) {
    global $CFG, $DB;

    require_once($CFG->libdir . '/completionlib.php');

    $dbman = $DB->get_manager();

    if ($oldversion < 2013102501) {
        // Drop the 'grade' field from the 'subcourse' table.

        $table = new xmldb_table('subcourse');
        $field = new xmldb_field('grade');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2013102501, 'subcourse');
    }

    if ($oldversion < 2014060900) {
        // Add the field 'instantredirect' to the table 'subcourse'.
        $table = new xmldb_table('subcourse');
        $field = new xmldb_field('instantredirect', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'refcourse');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2014060900, 'subcourse');
    }

    if ($oldversion < 2017071300) {
        // Add the field completioncourse to the table 'subcourse'.
        $table = new xmldb_table('subcourse');
        $field = new xmldb_field('completioncourse', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'instantredirect');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2017071300, 'subcourse');
    }

    if ($oldversion < 2018121600) {
        // Add field 'blankwindow' to the table 'subcourse'.
        $table = new xmldb_table('subcourse');
        $field = new xmldb_field('blankwindow', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'completioncourse');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018121600, 'subcourse');
    }

    if ($oldversion < 2020071100) {
        // Add field 'fetchpercentage' to the table 'subcourse'.
        $table = new xmldb_table('subcourse');
        $field = new xmldb_field('fetchpercentage', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'blankwindow');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2020071100, 'subcourse');
    }

    if ($oldversion < 2021021400) {
        // Add the field 'coursepageprintgrade' to the table 'subcourse'.
        $table = new xmldb_table('subcourse');
        $field = new xmldb_field(
            'coursepageprintgrade',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '1',
            'fetchpercentage'
        );

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add the field 'coursepageprintprogress' to the table 'subcourse'.
        $field = new xmldb_field(
            'coursepageprintprogress',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '1',
            'coursepageprintgrade'
        );

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2021021400, 'subcourse');
    }

    if ($oldversion < 2026090200) {
        // Add field 'completioncoursereversible' to the table 'subcourse'.
        $table = new xmldb_table('subcourse');
        $field = new xmldb_field(
            'completioncoursereversible',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'completioncourse'
        );

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026090200, 'subcourse');
    }

    if ($oldversion < 2026090300) {
        // Add field 'completionpassgradesubcourse' to the table 'subcourse'.
        $table = new xmldb_table('subcourse');
        $field = new xmldb_field(
            'completionpassgradesubcourse',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'completioncoursereversible'
        );

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $subcoursemoduleid = $DB->get_field('modules', 'id', ['name' => 'subcourse'], IGNORE_MISSING);
        if ($subcoursemoduleid) {
            $sql = "SELECT cm.id AS cmid, cm.course, s.id AS subcourseid
                      FROM {course_modules} cm
                      JOIN {subcourse} s ON s.id = cm.instance
                     WHERE cm.module = :moduleid
                       AND cm.completionpassgrade = 1";
            $records = $DB->get_recordset_sql($sql, ['moduleid' => $subcoursemoduleid]);
            $courseids = [];

            foreach ($records as $record) {
                xmldb_subcourse_move_core_passgrade_rule($record);
                $courseids[(int)$record->course] = true;
            }
            $records->close();

            foreach (array_keys($courseids) as $courseid) {
                rebuild_course_cache($courseid, true);
            }
        }

        upgrade_mod_savepoint(true, 2026090300, 'subcourse');
    }

    if ($oldversion < 2026090301) {
        // Recalculate existing pass-grade subcourse completions after migrating away from Moodle core pass-grade state.

        $subcoursemoduleid = $DB->get_field('modules', 'id', ['name' => 'subcourse'], IGNORE_MISSING);
        if ($subcoursemoduleid) {
            $sql = "SELECT cm.id AS cmid, cm.course, s.id AS subcourseid
                      FROM {course_modules} cm
                      JOIN {subcourse} s ON s.id = cm.instance
                     WHERE cm.module = :moduleid
                       AND (s.completionpassgradesubcourse = 1 OR cm.completionpassgrade = 1)";
            $records = $DB->get_recordset_sql($sql, ['moduleid' => $subcoursemoduleid]);

            foreach ($records as $record) {
                xmldb_subcourse_move_core_passgrade_rule($record);

                $gradeitem = $DB->get_record('grade_items', [
                    'courseid' => $record->course,
                    'itemtype' => 'mod',
                    'itemmodule' => 'subcourse',
                    'iteminstance' => $record->subcourseid,
                    'itemnumber' => 0,
                ], 'id,gradepass', IGNORE_MISSING);

                if (!$gradeitem || empty($gradeitem->gradepass)) {
                    continue;
                }

                $gradesql = "SELECT cmc.id, cmc.userid, cmc.completionstate, gg.finalgrade, gg.rawgrade
                               FROM {course_modules_completion} cmc
                          LEFT JOIN {grade_grades} gg
                                 ON gg.userid = cmc.userid
                                AND gg.itemid = :itemid
                              WHERE cmc.coursemoduleid = :cmid
                                AND cmc.overrideby IS NULL";
                $completionrecords = $DB->get_recordset_sql(
                    $gradesql,
                    [
                        'itemid' => $gradeitem->id,
                        'cmid' => $record->cmid,
                    ]
                );

                foreach ($completionrecords as $completionrecord) {
                    $score = $completionrecord->finalgrade ?? $completionrecord->rawgrade;
                    $newstate = $score === null ? COMPLETION_INCOMPLETE :
                        ($score >= $gradeitem->gradepass ? COMPLETION_COMPLETE : COMPLETION_COMPLETE_FAIL);

                    if ((int)$completionrecord->completionstate !== $newstate) {
                        $completionrecord->completionstate = $newstate;
                        $completionrecord->timemodified = time();
                        $DB->update_record('course_modules_completion', $completionrecord);
                    }
                }
                $completionrecords->close();
            }
            $records->close();
        }

        upgrade_mod_savepoint(true, 2026090301, 'subcourse');
    }

    if ($oldversion < 2026102400) {
        // Recalculate pass-grade failures so Moodle displays its failed (red) completion state.
        $sql = "SELECT cmc.id, cmc.completionstate, gg.finalgrade, gg.rawgrade, gi.gradepass
                  FROM {course_modules_completion} cmc
                  JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {subcourse} s ON s.id = cm.instance AND s.completionpassgradesubcourse = 1
                  JOIN {grade_items} gi
                    ON gi.courseid = cm.course
                   AND gi.itemtype = :itemtype
                   AND gi.itemmodule = :itemmodule
                   AND gi.iteminstance = s.id
                   AND gi.itemnumber = 0
             LEFT JOIN {grade_grades} gg ON gg.userid = cmc.userid AND gg.itemid = gi.id
                 WHERE cmc.overrideby IS NULL
                   AND gi.gradepass > 0";
        $records = $DB->get_recordset_sql($sql, [
            'modulename' => 'subcourse',
            'itemtype' => 'mod',
            'itemmodule' => 'subcourse',
        ]);

        foreach ($records as $record) {
            $score = $record->finalgrade ?? $record->rawgrade;
            $newstate = $score === null ? COMPLETION_INCOMPLETE :
                ($score >= $record->gradepass ? COMPLETION_COMPLETE : COMPLETION_COMPLETE_FAIL);

            if ((int)$record->completionstate !== $newstate) {
                $record->completionstate = $newstate;
                $record->timemodified = time();
                $DB->update_record('course_modules_completion', $record);
            }
        }
        $records->close();

        upgrade_mod_savepoint(true, 2026102400, 'subcourse');
    }

    return true;
}

/**
 * Move core pass-grade completion settings to the Subcourse strict pass-grade rule.
 *
 * @param stdClass $record Record with cmid and subcourseid fields.
 * @return void
 */
function xmldb_subcourse_move_core_passgrade_rule(stdClass $record): void {
    global $DB;

    $DB->set_field('subcourse', 'completionpassgradesubcourse', 1, ['id' => $record->subcourseid]);
    $DB->set_field('course_modules', 'completionpassgrade', 0, ['id' => $record->cmid]);
    $DB->set_field('course_modules', 'completiongradeitemnumber', null, ['id' => $record->cmid]);
}
