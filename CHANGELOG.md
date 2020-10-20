# Release Notes

## [Unreleased](https://github.com/laravel/sanctum/compare/v2.7.0...2.x)


## [v2.7.0 (2020-10-20)](https://github.com/laravel/sanctum/compare/v2.6.0...v2.7.0)

### Added
- Adds origin header fallback ([#204](https://github.com/laravel/sanctum/pull/204))


## [v2.6.0 (2020-09-01)](https://github.com/laravel/sanctum/compare/v2.5.0...v2.6.0)

### Changed
- Shorten tokens ([#186](https://github.com/laravel/sanctum/pull/186))


## [v2.5.0 (2020-08-25)](https://github.com/laravel/sanctum/compare/v2.4.2...v2.5.0)

### Added
- Laravel 8 support ([#184](https://github.com/laravel/sanctum/pull/184))


## [v2.4.2 (2020-06-16)](https://github.com/laravel/sanctum/compare/v2.4.1...v2.4.2)

### Fixed
- Use the correct `Str::endsWith` parameter order ([#163](https://github.com/laravel/sanctum/pull/163))


## [v2.4.1 (2020-06-11)](https://github.com/laravel/sanctum/compare/v2.4.0...v2.4.1)

### Fixed
- Fix default statefull domains ([#158](https://github.com/laravel/sanctum/pull/158), [2aac713](https://github.com/laravel/sanctum/commit/2aac713ced04e6e7f046748833dea5ab4c98b621))


## [v2.4.0 (2020-06-09)](https://github.com/laravel/sanctum/compare/v2.3.3...v2.4.0)

### Added
- Added Multiple Provider Support ([#149](https://github.com/laravel/sanctum/pull/149))

### Fixed
- Fixed Host Problem ([#155](https://github.com/laravel/sanctum/pull/155))


## [v2.3.3 (2020-05-26)](https://github.com/laravel/sanctum/compare/v2.3.2...v2.3.3)

### Fixed
- EncryptCookies middleware option in config/sanctum.php ([#147](https://github.com/laravel/sanctum/pull/147))


## [v2.3.2 (2020-05-21)](https://github.com/laravel/sanctum/compare/v2.3.1...v2.3.2)

### Added
- Add routes config option ([6cf798f](https://github.com/laravel/sanctum/commit/6cf798ff69d43fb2a714986cf028b5b5fa5612f2))


## [v2.3.1 (2020-05-12)](https://github.com/laravel/sanctum/compare/v2.3.0...v2.3.1)

### Fixed
- 419 Exception with requests without referrer ([#139](https://github.com/laravel/sanctum/pull/139))


## [v2.3.0 (2020-05-05)](https://github.com/laravel/sanctum/compare/v2.2.0...v2.3.0)

### Changed
- More performant tokens lookup ([#136](https://github.com/laravel/sanctum/pull/136))


## [v2.2.1 (2020-04-21)](https://github.com/laravel/sanctum/compare/v2.2.0...v2.2.1)

### Fixed
- No need to specify a provider ([#129](https://github.com/laravel/sanctum/pull/129))


## [v2.2.0 (2020-04-14)](https://github.com/laravel/sanctum/compare/v2.1.2...v2.2.0)

### Added
- Allow customizing the query used to get the token ([#124](https://github.com/laravel/sanctum/pull/124))


## [v2.1.2 (2020-04-09)](https://github.com/laravel/sanctum/compare/v2.1.1...v2.1.2)

### Fixed
- Enhance supportsTokens check ([#123](https://github.com/laravel/sanctum/pull/123))


## [v2.1.1 (2020-04-07)](https://github.com/laravel/sanctum/compare/v2.1.0...v2.1.1)

### Fixed
- `actingAs` any ability ([#120](https://github.com/laravel/sanctum/pull/120))


## [v2.1.0 (2020-03-24)](https://github.com/laravel/sanctum/compare/v2.0.0...v2.1.0)

### Added
- Make the guard configurable ([#110](https://github.com/laravel/sanctum/pull/110))


## [v2.0.0 (2020-03-20)](https://github.com/laravel/sanctum/compare/v1.0.1...v2.0.0)

### Changed
- Renamed package to Sanctum


## [v1.0.1 (2020-03-12)](https://github.com/laravel/sanctum/compare/v1.0.0...v1.0.1)

### Fixed
- Allow localhost ip access by default ([#81](https://github.com/laravel/sanctum/pull/81))
- Update minimum Laravel version to ^6.9 ([#89](https://github.com/laravel/sanctum/pull/89))
- Fix wildcard matching ([d8de232](https://github.com/laravel/sanctum/commit/d8de2323b49e9e408c7e5e302bcad392ed0989cb), [9a66e76](https://github.com/laravel/sanctum/commit/9a66e767e203bbee83cd5fcda7ce265835468f84))


## [v1.0.0 (2020-03-03)](https://github.com/laravel/sanctum/compare/v0.2.1...v1.0.0)

First stable release.


## [v0.2.1 (2020-02-12)](https://github.com/laravel/sanctum/compare/v0.2.0...v0.2.1)

### Changed
- Allow .env configuration of stateful domains ([#70](https://github.com/laravel/sanctum/pull/70))


## [v0.2.0 (2020-01-28)](https://github.com/laravel/sanctum/compare/v0.1.0...v0.2.0)

### Added
- Added user mocking using actingAs ([#51](https://github.com/laravel/sanctum/pull/51))
- Add a CSRF middleware config variable ([#54](https://github.com/laravel/sanctum/pull/54), [4f77acd](https://github.com/laravel/sanctum/commit/4f77acd5e60d241b0bb8196b1986e6f59946af1d), [7df454d](https://github.com/laravel/sanctum/commit/7df454d03868d4329915a4d105b067df0d0a924d))

### Changed
- Modify PersonalAccessToken Model to be polymorphic ([#49](https://github.com/laravel/sanctum/pull/49))


## v0.1.0 (2020-01-20)

Initial commit.
