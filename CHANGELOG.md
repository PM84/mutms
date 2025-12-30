# Change log

Plugin versioning is derived from Moodle releases, it does not comply with the semantic versioning standard.

The format of this change log follows the advice given at [Keep a CHANGELOG](https://keepachangelog.com).

## [mu-4.5.8-03] - 2025-12-31

### Changed

- Switched to new change log format
- Reversed block dependencies to simplify Certifications installation and upgrades
- Switched to new mulib Certification helpers
- Improved performance of Certifications management page on sites with large number of contexts
- Fixed category selection autocomplete element in certification editing form
- Standardised certification idnumber to be case-insensitively unique

## [mu-4.5.8-02] - 2025-12-16

- Fixed placement of custom fields in certification creation form.

## [mu-4.5.8-01] - 2025-12-08

- After creating new certification user is now redirected to Period settings page because they need to add program. 
- Fixed timezones in notifications.
- Added supervisor notifications.
- Added support for Expiration column into certification assignment uploads.
- Added Expiration to certification assignment form - this can be used to align employee re-certifications to fixed dates. 
- Added option for enabling of Manual allocations during certification creation.

## [mu-4.5.7-02] - 2025-11-08

- Documentation was moved to https://github.com/mutms/moodle-tool_mucertify/wiki
- Improved table visuals.

## [mu-4.5.7-01] - 2025-10-06

- Fixed certification tags itemtype to match database table name.

## [mu-4.5.6-03] - 2025-09-24

- Program allocation conflicts are now handled gracefully. However, mixing certification allocation sources with other types of allocations within a single program remains discouraged. 

## [mu-4.5.6-02] - 2025-08-31

- Fixed automatic cohort assignment source form.
- Empty custom fields are not displayed anymore.
- Fixed validation of tenant restrictions when selecting users.
- Fixed compatibility with unsupported MS SQL databases.
- Fixed error when sending unassignment email and SMTP is down.

## [mu-4.5.6-01] - 2025-08-09

- Internal refactoring.
- Moodle 4.5.6 support.

## [mu-4.5.5-02] - 2025-06-30

- New plugin versioning.

## [mu-4.5.5-01] - 2025-06-09

- Refactored WS for candidate users.
- Improved docs and added acknowledgements.
- Added custom fields for certification assignments.
