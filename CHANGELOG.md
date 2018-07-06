# Change Log
All notable changes to this project will be documented in this file, starting with 2.0.1
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [4.0.0]
Fixed a bug with the WP Cron client where $args were passed to callback functions as individual callback args, instead of as a single array as intended. 

## [3.0.0]
Renamed from WP Gears to WP Minions, to better reflect that this is designed to be used with any job queue backend - not just Gearman

### Fixed
- Ensure unit test suite is compatible with latest versions of PHP and WordPress

## [2.1.0]
### Added
- `wp_async_task_after_work` action after the gearman worker finishes working
- Include instance of `GearmanJob` in action hooks

### Fixed
- Clarify configuration instructions for Gearman on Ubuntu
- Don't cache blog ID on the gearman client class to fix issues when adding jobs on multiple blogs

## [2.0.1]
### Added
- Changelog

### Fixed
- Fix fatal error when Gearman unable to connect to gearman servers
