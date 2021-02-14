Subcourse module for Moodle
===========================

![Moodle Plugin CI](https://github.com/mudrd8mz/moodle-mod_subcourse/workflows/Moodle%20Plugin%20CI/badge.svg)

This Moodle module provides very simple yet useful functionality. When added into a
course, it behaves as a graded activity. The grade for each student is took from a
final grade in another course. Combined with
[metacourses](http://docs.moodle.org/en/Course_meta_link), this allows course
designers to effectively organise courses into separate units.

Installation
------------

Please follow <https://docs.moodle.org/en/Installing_plugins#Installing_a_plugin> for
the general instructions on how to install Moodle plugins.

When installing from uploaded ZIP package or via Git, the "subcourse" directory is
expected to be place under the "/mod" directory of your Moodle installation. 

Usage
-----

* Create a main course and one or few other courses that should act as a sub-courses of
  the main course.
* Into the main course, add a new instance of the Subcourse activity module for each
  of the referenced course (sub-course) to fetch grades from.
* Enrol students into all courses (the main one as well as the referenced ones).
* Let students receive the final grades in the referenced courses.
* Check that the final grade in the referenced courses now appears as the grade for
  the Subcourse activity in the main course.

Author
------

The module has been written and is currently maintained by David Mudrák <david@moodle.com>

Useful links
------------

* [Bug tracker](https://github.com/mudrd8mz/moodle-mod_subcourse/issues)

License
-------

This program is free software: you can redistribute it and/or modify it under the
terms of the GNU General Public License as published by the Free Software Foundation,
either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this
program. If not, see <http://www.gnu.org/licenses/>.
