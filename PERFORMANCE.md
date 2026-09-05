# Performance testing

Run the benchmark suite with:

```sh
composer run-script test-performance
```

The manually dispatched **PHP Performance Tests** workflow tests PHP 8.1–8.5
on Ubuntu. It enables OPcache and tracing JIT explicitly:

```ini
opcache.enable_cli=1
opcache.jit=tracing
opcache.jit_buffer_size=256M
```

For local benchmarks, put these settings in the CLI `php.ini` used by both
PHPUnit and its child PHP processes. Disable Xdebug and other coverage drivers
when benchmarking. The workflow uses `coverage: none` and verifies that OPcache
and tracing JIT are active before running tests.

Record the PHP version, operating system, JIT mode, and coverage configuration
when comparing timings. Results using tracing JIT should not be treated as
directly comparable to results collected with a different JIT mode.

## Why tracing JIT is explicit

[Issue #92](https://github.com/okapi-web/php-aop/issues/92) tracks a PHP JIT
compiler crash encountered during PHPUnit test discovery. `setup-php` supplies
`opcache.jit=1235` by default; enabling CLI OPcache with coverage disabled
activates that mode, which compiles hot functions. Tracing JIT (`1254`) uses
a different compilation mode.

The crash was reproduced with PHP 8.5.10 on Linux and Windows. In a PHP 8.5.10
debug build under WSL, the debugger identified
`SebastianBergmann\Exporter\Exporter::recursiveExport()` as the function being
compiled. The engine failed the `count == 1` assertion in `ir_fix_bb_order()`
at `ext/opcache/jit/ir/ir_gcm.c:944`. PHPUnit calls this exporter while formatting
data-provider arguments, before executing the benchmarks.

An exporter-only reproduction also fails without loading PHPUnit or AOP.
`sebastian/exporter` is a development dependency here, not a runtime dependency
of the library. This identifies the observed crash; it does not establish that
all application code is unaffected by the underlying PHP engine bug.

Tracing JIT avoids the observed crash while retaining JIT and OPcache for the
library and benchmarks. This is a test-workflow workaround, not a PHP engine
fix or a requirement for consumers to disable JIT. No application configuration
is changed by the library.

References:

- [PHP JIT configuration](https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.jit)
- [setup-php JIT defaults](https://github.com/shivammathur/setup-php#jit-configuration)
