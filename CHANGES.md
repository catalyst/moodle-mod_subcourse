### 10.1.0 ###

* Ionic 5 support added to make the plugin compatible with the Moodle App version
  3.9.5. Credit goes to Dani Palou (@dpalou).
* Removed legacy Bootstrap 2 classes. Credit goes to Daniel Escobar (@Daryhez).
* Coding style cleanups and improvements.

### 10.0.0 ###

* Display of progress and grade in referenced course on the main course page can be
  now configured (issue #32). Credit goes to Arnaud Trouvé (@ak4t0sh).
* Supported Moodle versions 3.9 (LTS) and 3.10.

### 9.0.1 ###

* Added example JSON context to the ionic template to pass the mustache lint.

### 9.0.0 ###

* Added support for the Moodle Mobile App. The app displays a simple screen with the
  information about the progress in the referenced course, the current final grade
  there and a button to go to the referenced course. Teacher specific features such as
  fetching grades are out of mobile app support scope (issue #19).
* Added standard subcourse:view allowing to fine control what can view the module
  instances in the course.
* Added ability to mark the Subcourse activity as completed automatically upon opening
  by users - uses standard view tracking feature.

### 8.0.1 ###

* Fixed issue #38 causing the grades fetch fail and throw error in certain cases. This
  is a regression of the hidden grades support introduced in 8.0.0 (issue #28).

### 8.0.0 ###

* Referenced course selector uses the autocomplete form widget (issue #34).
* Referenced course final grades can now be fetched either as real values (existing
  and default behaviour) or as percentual values (new optional behaviour). This allows
  teacher to keep the percentage displayed in the referenced course matching with the
  grade in the subcourse activity even if there are excluded grades (issue #29).
* If the grades are hidden in the referenced course, they are now correctly marked as
  hidden in subcourse activity, too. This supports both hidden grade item (i.e. whole
  columns in the gradebook) and individual grades (issue #28).
* Supported and tested on Moodle 3.8 and 3.9. Likely to work on lower versions, too.

### 7.2.0 ###

* Links to the referenced course can open in a new window/tab (issue #27).

### 7.1.1 ###

* Do not call a method deprecated in Moodle 3.6
* Updated Behat tests to work in Moodle 3.6

### 7.1.0 ###

* Improved handling of hidden grade items in the referenced course (issue #28).

### 7.0.0 ###

* Progress in the referenced course is displayed.
* The main view page now provides links to the gradebook in the referenced course
  instead of the link to the gradebook in the current course (issue #22).
* Overall cleanup and UI improvements of the main view page.

### 6.0.0 ###

* Filters are applied displaying course names - credit goes to Philipp Hager
* The "should be completed" event is displayed on the dashboard if expected completion
  date/time is set.
* Privacy API implemented to make it GDPR friendly. No personal data are stored by the
  module itself.
* Requires Moodle 3.3 or higher, updated tests for Moodle 3.5.

### 5.0.1 ###

* Fixed a typo in the string (currrent -> current).
* Updated Behat tests to work in Moodle 3.3 and 3.4.

### 5.0.0 ###

* Performance improvements: On triggered events (such as when a student
  received a grade in the referenced course), the subcourse used to fetch all
  students grades. This led to performance troubles in courses with many
  students, or when performing bulk changes (such as enrolling multiple
  students at once).
* Completion: The subcourse can now be automatically marked as completed when
  the student completes the referenced course.
* Behat tests pass on Moodle 3.1 and 3.2, manually tested on 3.3.

### 4.0.1 ###

* Fixed Behat tests syntax for Moodle 3.1 and 3.2

### 4.0.0 ###

* Fixed Behat test failure in Moodle 3.0 due to MDL-51051.
* Fixed coding style violations reported by the codechecker.
* Added support for Travis CI.

### 3.1 ###

* Added support for Moodle 2.9 - does not use `add_intro_editor()` for this and higher versions.
* Improved Behat tests to prevent accidental false positive matches of certain selectors.

### 3.0 ###

* The module now observes "user graded" and "role assigned" events and fetches grades instantly on them (no need to rely on pressing
  the "Fetch now" button or the cron task running). Credit goes to Vadim Dvorovenko for implementing this.
* Added Behat tests.
* Fixed missing name of the "addinstance" capability.
* Requires Moodle 2.8.
* Changed versioning scheme of the plugin. The "master" branch now contains the most recent stable code.

### 2.7.0 ###

* Added an option to instantly redirect to the referenced course when attempting to view the subcourse module page. This does not
  affect users with the permission to fetch grades manually so they do not loose that option. Credit goes to Matt Gibson for the
  original idea and implementation in his fork.
* Legacy cron function replaced with the new scheduled task API. This allows administrators to define the schedule of fetching
  grades from subcourses to fit their needs (especially on heavy loaded sites with many users).
* Legacy add_to_log() replaced with the new event API. This allows to use the new logging storage introduced in Moodle 2.7. Credit
  goes to Vadim Dvorovenko for the original patch.
