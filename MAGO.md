# Mago checks

Install development dependencies with `composer install`, then run:

```sh
composer check        # formatting, linter, analyzer for source and tests
composer format      # apply formatting
composer lint
composer analyze
```

Mago is pinned to **1.47.4**, the newest published version verified for this upgrade.
Update the pin deliberately and run the full checks before upgrading. Composer installs the
platform-specific launcher; its first invocation downloads the matching binary.
CI provides `GITHUB_TOKEN` for that download. Scripts invoke the project-local PHP
launcher explicitly so an older system-wide binary cannot be used accidentally.
There is no baseline.

The formatter, linter, and analyzer have been run successfully with 1.47.4 against
both configurations. On Windows, the Composer launcher needs PHP's ZIP extension
and a configured trusted CA certificate bundle to download and extract its binary.

`mago.toml` targets PHP 8.1 for library code. `mago-tests.toml` targets PHP 8.2 because
some fixtures intentionally exercise readonly classes and traits; PHPUnit skips
the applicable tests on PHP 8.1. Both configurations read dependency declarations
without checking vendor code. Generated AOP cache and coverage files are excluded.

The linter uses its default correctness, consistency, and maintainability checks.
Exceptions in configuration are explicit design choices: mandatory strict types
would change coercion behavior; named arguments and boolean flags are existing API
choices; `isset` intentionally distinguishes null; aggregate complexity metrics
are left to review. Analyzer missing-type checks are enabled. CI fails on all
reported severities. No analyzer issue codes are ignored globally.

The vendor type patch in `tools/mago/patches/CodeTransformerKernel.php` models its
dependency injection callback as a generic extension point. The default remains
the original one-argument callback returning a Transformer. AopKernel specializes
it to its established two-argument callback returning an aspect or transformer.
This replaces the previous callback suppression without changing runtime code or
loosening TransformerManager's contract. `composer check-mago-types` checks valid
callbacks and rejects scalar returns and incorrect default transformer callbacks.
It runs as part of `composer check`, including in CI. Its deliberately invalid
fixtures live in `tools/mago/tests` and are checked separately from library code.

Two narrow `@mago-expect` annotations still cover four property-access diagnostics.
These are explicit analyzer exceptions, not fixes to the analyzer's limitations:

- Two property-access closures operate on a declaration validated through reflection.
  Mago cannot prove a runtime property name exists on the subject. Reference, scope,
  uninitialized-property, and unset behavior have runtime regression tests.

These expectations name individual diagnostics at the affected operations. Mago
reports an unfulfilled expectation if a future release stops producing a diagnostic,
so stale exceptions fail CI. No baseline, file exclusions for these operations, or
global analyzer diagnostic suppressions are used.

An extension experiment confirmed that Mago 1.47.4 does not dispatch property type
providers for these unresolved runtime operations. The experiment was discarded;
no custom extension or issue filter is installed.

Performance fixture generation now uses a shared service interface and validates
generated service instantiation through reflection. Its construction work is part
of the class-loading measurement; compare loading results only against runs using
the same harness. The timed method-execution loop is unchanged.
