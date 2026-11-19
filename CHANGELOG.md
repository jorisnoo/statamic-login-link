# Changelog

All notable changes to this project will be documented in this file.

## [0.2.0] - 2026-11-19

### Security

- Developer login now requires a direct loopback connection as well as an allowed environment and hostname. Proxied and remote requests are denied, including when they present a valid link. Use a direct local connection instead of a tunnel.

### Fixed

- Host patterns match case-insensitively. Login links remain visible without inline JavaScript or a password form, and the login response containing them is not cacheable.
- Developer login now elevates the regenerated Statamic session as normal CP login does.

## [0.1.0](https://github.com/jorisnoo/statamic-login-link/releases/tag/0.1.0) (2026-03-17)

### Continuous Integration

- add release workflow ([d9c2f50](https://github.com/jorisnoo/statamic-login-link/commit/d9c2f50630d3c3171bd5f2b64ad26c46c984ce3d))
