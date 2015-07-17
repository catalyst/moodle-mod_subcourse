Changes
=======

v3.1
----

* Added support for Moodle 2.9 - does not use `add_intro_editor()` for this and higher versions.
* Improved Behat tests to prevent accidental false positive matches of certain selectors.

v3.0
----

* The module now observes "user graded" and "role assigned" events and fetches grades instantly on them (no need to rely on pressing
  the "Fetch now" button or the cron task running). Credit goes to Vadim Dvorovenko for implementing this.
* Added Behat tests.
* Fixed missing name of the "addinstance" capability.
* Requires Moodle 2.8.
* Changed versioning scheme of the plugin. The "master" branch now contains the most recent stable code.

v2.7.0
------

* Added an option to instantly redirect to the referenced course when attempting to view the subcourse module page. This does not
  affect users with the permission to fetch grades manually so they do not loose that option. Credit goes to Matt Gibson for the
  original idea and implementation in his fork.
* Legacy cron function replaced with the new scheduled task API. This allows administrators to define the schedule of fetching
  grades from subcourses to fit their needs (especially on heavy loaded sites with many users).
* Legacy add_to_log() replaced with the new event API. This allows to use the new logging storage introduced in Moodle 2.7. Credit
  goes to Vadim Dvorovenko for the original patch.
