# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/), starting with 2.0.1.  This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added
- HHVM compatibility
- Support for RabbitMQ vhost and docs

### Fixed
- Hostname from localh1ost to localhost

## [4.1.0] - 2018-11-05
### Added
- Filters for rabbitmq queue options
- Options for rabbitmq vhost. Defaults to `/`

### Fixed
- Fixes php-amqplib at 2.8.0

## [4.0.0] - 2018-07-06
### Fixed
- Bug with the WP Cron client where $args were passed to callback functions as individual callback args, instead of as a single array as intended. 

## [3.0.0] - 2017-07-06
### Changed
- Renamed from WP Gears to WP Minions, to better reflect that this is designed to be used with any job queue backend - not just Gearman

### Fixed
- Ensure unit test suite is compatible with latest versions of PHP and WordPress

## [2.1.0] - 2016-05-31
### Added
- `wp_async_task_after_work` action after the gearman worker finishes working
- Include instance of `GearmanJob` in action hooks

### Fixed
- Clarify configuration instructions for Gearman on Ubuntu
- Don't cache blog ID on the gearman client class to fix issues when adding jobs on multiple blogs

## [2.0.1] - 2016-05-14
### Added
- Changelog

### Fixed
- Fix fatal error when Gearman unable to connect to gearman servers

## [2.0.0] - 2016-01-21
### Added
- PHPUnit tests
- Build status indicator

## [1.0.0] - 2015-10-23
- Initial release

[Unreleased]: https://github.com/10up/WP-Minions/compare/4.0.0...master
[4.1.0]: https://github.com/10up/WP-Minions/compare/4.0.0...4.1.0
[4.0.0]: https://github.com/10up/WP-Minions/compare/3.0.0...4.0.0
[3.0.0]: https://github.com/10up/WP-Minions/compare/2.1.0...3.0.0
[2.1.0]: https://github.com/10up/WP-Minions/compare/2.0.1...2.1.0
[2.0.1]: https://github.com/10up/WP-Minions/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/10up/WP-Minions/compare/1.0.0...2.0.0
[1.0.0]: https://github.com/10up/WP-Minions/releases/tag/1.0.0
