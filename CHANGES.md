# Changelog

## mu-4.5.8-02

Release date: 16/12/2025

* Fixed placement of custom fields in certification creation form.

## mu-4.5.8-01

Release date: 08/12/2025

* After creating new certification user is now redirected to Period settings page because they need to add program. 
* Fixed timezones in notifications.
* Added supervisor notifications.
* Added support for Expiration column into certification assignment uploads.
* Added Expiration to certification assignment form - this can be used to align employee re-certifications to fixed dates. 
* Added option for enabling of Manual allocations during certification creation.

## mu-4.5.7-02

Release date: 08/11/2025

* Documentation was moved to https://github.com/mutms/moodle-tool_mucertify/wiki
* Improved table visuals.

## mu-4.5.7-01

Release date: 06/10/2025

* Fixed certification tags itemtype to match database table name.

## mu-4.5.6-03

Release date: 24/09/2025

* Program allocation conflicts are now handled gracefully. However, mixing certification allocation sources with other types of allocations within a single program remains discouraged. 

## mu-4.5.6-02

Release date: 31/08/2025

* Fixed automatic cohort assignment source form.
* Empty custom fields are not displayed anymore.
* Fixed validation of tenant restrictions when selecting users.
* Fixed compatibility with unsupported MS SQL databases.
* Fixed error when sending unassignment email and SMTP is down.

## mu-4.5.6-01

Release date: 09/08/2025

* Internal refactoring.
* Moodle 4.5.6 support.

## mu-4.5.5-02

Release date: 30/06/2025

* New plugin versioning.

## mu-4.5.5-01

Release date: 09/06/2025

* Refactored WS for candidate users.
* Improved docs and added acknowledgements.
* Added custom fields for certification assignments.
