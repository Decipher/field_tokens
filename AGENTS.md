# AGENTS.md

This file provides guidance to AI agents when working with code in this repository.

## Overview

Field Tokens is a Drupal module that provides formatted field and field property tokens.
It allows users to use tokens like:
- `[formatted_field-{type}:{formatter}:{settings}]`
- `[field_property-{type}:{property}]`

Primary use case: Token support for the HTML+Token engine in Custom Formatters.

## Development Commands

### Build and Environment Management

**Using Make:**
- `make build` - Complete build (stop -> assemble -> start -> provision)
- `make assemble` - Assemble codebase with dependencies
- `make start` - Start PHP development server
- `make stop` - Stop development server
- `make provision` - Install/provision Drupal site
- `make reset` - Clean build directory and logs

**Using Ahoy:**
- `ahoy build` - Complete build process
- `ahoy assemble` - Assemble codebase
- `ahoy start` / `ahoy stop` - Start/stop development server
- `ahoy provision` - Provision Drupal site

### Code Quality

**Linting:**
- `make lint` / `ahoy lint` - Run all linting tools (phpcs, phpstan, rector dry-run, twig-cs-fixer)
- `make lint-fix` / `ahoy lint-fix` - Auto-fix coding standards violations

**Testing:**
- `make test` / `ahoy test` - Run all PHPUnit tests
- `make test-unit` / `ahoy test-unit` - Run unit tests only
- `make test-kernel` / `ahoy test-kernel` - Run kernel tests only
- `make test-functional` / `ahoy test-functional` - Run functional tests only
- `make test-functional-javascript` / `ahoy test-functional-javascript` - FunctionalJavascript tests (requires Selenium)

### Drupal Commands

- `make drush <command>` - Run Drush command
- `make login` / `ahoy login` - Get one-time login link

## Project Structure

- `field_tokens.info.yml` - Module info
- `field_tokens.module` - Main module file
- `field_tokens.tokens.inc` - Token implementations (primary code)
- `src/Tests/` - Functional tests (FieldTokensFormattedTest, etc.)
- `build/` - Assembled Drupal codebase (gitignored, symlinked extension)
- `.devtools/` - Build and deployment scripts used by CI

## Architecture

Field Tokens is a relatively simple module that:

1. Implements `hook_token_info()` - defines token types for each field type and formatter
2. Implements `hook_token_info_alter()` - alters entity tokens to add `-formatted` and `-property` suffixes
3. Implements `hook_tokens()` - provides the actual token replacement logic

**Key files:**
- `field_tokens.tokens.inc` - All token implementations (lines 1-348)

**Token patterns provided:**
- `[node:field_{name}-formatted:{delta}:{formatter}:{settings}]` - Formatted field output
- `[node:field_{name}-property:{delta}:{property}]` - Raw field properties

## Environment Variables

- `DRUPAL_VERSION` - Target Drupal version (e.g., `10`, `11`)
- `WEBSERVER_HOST` - Development server host (default: localhost)
- `WEBSERVER_PORT` - Development server port (default: 8000)
- `GITHUB_TOKEN` - GitHub API token to avoid rate limits

## Code Quality Tools

- **PHPCS**: Drupal and DrupalPractice standards
- **PHPStan**: Static analysis at level 7 with Drupal extensions
- **Rector**: Automated refactoring and deprecation fixes (D9/D10 sets)
- **Twig CS Fixer**: Twig template formatting

## CI/CD

- **GitHub Actions**: `.github/workflows/test.yml` (lint + 15-matrix test)
- **Deploy**: `.github/workflows/deploy.yml` (mirror to Drupal.org via SSH)
- **Matrix testing**: PHP 8.2-8.4, Drupal 10-11 (legacy/stable/canary)

## Important Notes

- The `build/` directory contains the assembled Drupal site
- Extension files are symlinked from root into `build/web/modules/custom/`
- SQLite database created in `/tmp/site_field_tokens.sqlite`
- All quality tools run from within `build/` directory
- Active development branch: `2.0.x`

## Integration with Custom Formatters

Field Tokens is an optional dependency of Custom Formatters. It enables:
- `[formatted_field-{field_type}:{formatter}:{settings}]` tokens
- `[field_property-{field_type}:{property}]` tokens

Used in the example formatter `example_html_token_image` for image field token support.
