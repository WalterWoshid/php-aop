<?php

/** @return non-empty-string */
function mago_temp_file(): string
{
    $path = tempnam(sys_get_temp_dir(), 'aop-mago-');
    if ($path === false || $path === '') {
        throw new RuntimeException('Cannot create Mago test output.');
    }
    return $path;
}

function mago_read_file(string $path): string
{
    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException('Cannot read Mago test output.');
    }
    return $contents;
}

/**
 * @param list<string> $paths
 * @return list<array{code: string, annotations: list<array{kind: string, span: array{file_id: array{name: string}}}>}>
 */
function mago_type_issues(array $paths): array
{
    $stdout = mago_temp_file();
    $stderr = mago_temp_file();
    try {
        $pipes = [];
        $process = proc_open(
            [
                PHP_BINARY,
                'vendor/bin/mago',
                '--config',
                'tools/mago/tests.toml',
                'analyze',
                '--reporting-format',
                'json',
                '--minimum-fail-level',
                'note',
                ...$paths,
            ],
            [0 => ['pipe', 'r'], 1 => ['file', $stdout, 'w'], 2 => ['file', $stderr, 'w']],
            $pipes,
            dirname(__DIR__, 2),
        );
        if (!is_resource($process)) {
            throw new RuntimeException('Cannot start Mago.');
        }
        if (array_key_exists(0, $pipes)) {
            fclose($pipes[0]);
        }
        $status = proc_close($process);
        $errors = trim(mago_read_file($stderr));
        try {
            /** @var array{issues: list<array{code: string, annotations: list<array{kind: string, span: array{file_id: array{name: string}}}>}>} $report Mago JSON output schema. */
            $report = json_decode(mago_read_file($stdout), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Invalid Mago output: ' . $errors, previous: $exception);
        }
        if ($status !== ($report['issues'] === [] ? 0 : 1)) {
            throw new RuntimeException('Unexpected Mago exit status: ' . $status . ' ' . $errors);
        }
        return $report['issues'];
    } finally {
        unlink($stdout);
        unlink($stderr);
    }
}

if (mago_type_issues(['tools/mago/tests/valid']) !== []) {
    throw new RuntimeException('Valid dependency injection callbacks must pass analysis.');
}

$issues = mago_type_issues(['tools/mago/tests/valid', 'tools/mago/tests/invalid']);
$actual = [];
foreach ($issues as $issue) {
    foreach ($issue['annotations'] as $annotation) {
        if ($annotation['kind'] !== 'Primary') {
            continue;
        }
        $actual[basename($annotation['span']['file_id']['name'])][] = $issue['code'];
        break;
    }
}
$expected = [
    'ScalarCallback.php' => ['invalid-return-statement'],
    'WrongBaseCallback.php' => ['invalid-return-statement'],
    'WrongManager.php' => ['invalid-argument'],
    'WrongReturn.php' => ['invalid-return-statement'],
];
ksort($actual);
ksort($expected);
if ($actual !== $expected || count($issues) !== 4) {
    throw new RuntimeException(
        'Invalid callbacks must produce all four expected diagnostics: ' . json_encode($actual, JSON_THROW_ON_ERROR),
    );
}
echo 'Mago type patch: valid callbacks accepted; all four invalid callbacks rejected.' . PHP_EOL;
