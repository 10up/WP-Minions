# Change Log
All notable changes to this project will be documented in this file, starting with 2.0.1
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

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
