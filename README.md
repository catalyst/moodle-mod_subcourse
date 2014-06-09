Subcourse
=========

This Moodle module provides very simple yet useful functionality. When added into a course, it behaves as a graded activity. The
grade for each student is took from a final grade in another course. Combined with
[metacourses](http://docs.moodle.org/en/Course_meta_link), this allows course designers to organize courses into separate units.

v2.7.0
------

* Added an option to instantly redirect to the referenced course when attempting to view the subcourse module page. This does not
  affect users with the permission to fetch grades manually so they do not loose that option. Credit goes to Matt Gibson for the
  original idea and implementation in his fork.
* Legacy cron function replaced with the new scheduled task API. This allows administrators to define the schedule of fetching
  grades from subcourses to fit their needs (especially on heavy loaded sites with many users).
* Legacy add_to_log() replaced with the new event API. This allows to use the new logging storage introduced in Moodle 2.7. Credit
  goes to Vadim Dvorovenko for the original patch.

Author
------

The module has been written and is currently maintained by David Mudrák <david@moodle.com>

Useful links
------------

* [Bug tracker](https://github.com/mudrd8mz/moodle-mod_subcourse/issues)

License
-------

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as
published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see
<http://www.gnu.org/licenses/>.
