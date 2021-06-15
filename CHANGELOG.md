# Release Notes

## [Unreleased](https://github.com/laravel/sanctum/compare/v2.11.2...2.x)


## [v2.11.2 (2021-06-15)](https://github.com/laravel/sanctum/compare/v2.11.1...v2.11.2)

### Fixed
- Ignore updating `last_used_at` for deciding the DB connection host ([#283](https://github.com/laravel/sanctum/pull/283), [2c8b9a1](https://github.com/laravel/sanctum/commit/2c8b9a1071b87c1911ba99448d1173dd75e97c9f))
- Fix resolving wrong app instance on Octane ([#285](https://github.com/laravel/sanctum/pull/285), [#286](https://github.com/laravel/sanctum/pull/286))


## [v2.11.1 (2021-05-25)](https://github.com/laravel/sanctum/compare/v2.11.0...v2.11.1)

### Changed
- Only parse APP_URL for default stateful domains when it's set ([#279](https://github.com/laravel/sanctum/pull/279))


## [v2.11.0 (2021-05-11)](https://github.com/laravel/sanctum/compare/v2.10.0...v2.11.0)

### Added
- `Sanctum::$accessTokenAuthenticationCallback` callback for more granular control over access token validation ([#275](https://github.com/laravel/sanctum/pull/275), [9c07921](https://github.com/laravel/sanctum/commit/9c079213d3e748fa0d784a17b6ef2f5cde92a286), [#276](https://github.com/laravel/sanctum/pull/276))


## [v2.10.0 (2021-04-20)](https://github.com/laravel/sanctum/compare/v2.9.4...v2.10.0)

### Added
- Add HasApiTokens contract to complement trait ([#270](https://github.com/laravel/sanctum/pull/270))


## [v2.9.4 (2021-04-06)](https://github.com/laravel/sanctum/compare/v2.9.3...v2.9.4)

### Changed
- Use app helper ([60f2809](https://github.com/laravel/sanctum/commit/60f280995c3f878de0e6422eaacd1c30d37d263e))


## [v2.9.3 (2021-03-30)](https://github.com/laravel/sanctum/compare/v2.9.2...v2.9.3)

### Changed
- Environment APP_URL added into the default sanctum.stateful configuration ([#264](https://github.com/laravel/sanctum/pull/264))


## [v2.9.2 (2021-03-23)](https://github.com/laravel/sanctum/compare/v2.9.1...v2.9.2)

### Fixed
- Changed Primary Key will not be used in created token's plainTextToken ([#262](https://github.com/laravel/sanctum/pull/262))


## [v2.9.1 (2021-03-09)](https://github.com/laravel/sanctum/compare/v2.9.0...v2.9.1)

### Fixed
- Avoid running string functions when domain is null ([#258](https://github.com/laravel/sanctum/pull/258))


## [v2.9.0 (2021-01-26)](https://github.com/laravel/sanctum/compare/v2.8.2...v2.9.0)

### Added
- Add multiple guard support for SPA auth ([#246](https://github.com/laravel/sanctum/pull/246), [f5695ae](https://github.com/laravel/sanctum/commit/f5695aecc547138c76bc66aaede73ba549dabdc5))

### Fixed
- Return json response when the request expects a json ([#247](https://github.com/laravel/sanctum/pull/247))


## [v2.8.2 (2020-11-24)](https://github.com/laravel/sanctum/compare/v2.8.1...v2.8.2)

### Fixed
- Fix user provider in `sanctum` guard ([#225](https://github.com/laravel/sanctum/pull/225))


## [v2.8.1 (2020-11-17)](https://github.com/laravel/sanctum/compare/v2.8.0...v2.8.1)

### Changed
- Add default nextjs address to stateful ([e86d3e0](https://github.com/laravel/sanctum/commit/e86d3e01ade3325438fe1e64ddd64ec53f828dc4))


## [v2.8.0 (2020-11-03)](https://github.com/laravel/sanctum/compare/v2.7.0...v2.8.0)

### Added
- PHP 8 Support ([#213](https://github.com/laravel/sanctum/pull/213))


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
