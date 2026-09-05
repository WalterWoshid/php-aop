<?php

namespace Okapi\Aop\Tests\Performance;

use Exception;
use Okapi\Aop\AopKernel;
use Okapi\Aop\Tests\Performance\Service\NumbersServiceInterface;
use Okapi\Aop\Tests\Performance\Target\Numbers;
use Okapi\Aop\Tests\Util;
use Okapi\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

#[RunTestsInSeparateProcesses]
class MeasurePerformanceTest extends TestCase
{
    private bool $useAspects;
    private bool $cached;
    private bool $production;

    private const MEASURE_TYPE_NO_ASPECTS = 'No Aspects';
    private const MEASURE_TYPE_ASPECTS = 'Aspects';
    private const MEASURE_TYPE_CACHED_ASPECTS = 'Cached Aspects';
    private const MEASURE_TYPE_PRODUCTION = 'Production';

    /** @var array<string, array<string, array<string, int|float>>> */
    private array $measures = [
        self::MEASURE_TYPE_NO_ASPECTS => [],
        self::MEASURE_TYPE_ASPECTS => [],
        self::MEASURE_TYPE_CACHED_ASPECTS => [],
        self::MEASURE_TYPE_PRODUCTION => [],
    ];

    private const MEASURE_TYPE_FROM_START_TO_END = 'From Start to End';
    private const MEASURE_TYPE_BOOT = 'Boot Time - Kernel::init()';
    private const MEASURE_TYPE_CLASS_LOADING = 'Class Loading Time';
    private const MEASURE_TYPE_EXECUTION = 'Execution Time';

    private const START_TIME = 'Start Time';
    private const END_TIME = 'End Time';
    private const START_MEMORY = 'Start Memory';
    private const END_MEMORY = 'End Memory';

    private const METRIC_TYPE_TIME = 'Time';
    private const METRIC_TYPE_MEMORY = 'Memory';

    /** @var non-empty-list<array{positive-int, positive-int}> */
    public static array $aspectCountAndExecutionCount = [
        [1, 1], // Minimal
        [5, 5], // Small
        [20, 20], // Moderate
        [50, 50], // Medium
        [100, 100], // Common
        [500, 100], // Common: Aspects++
        [100, 500], // Common: Executions++
        [500, 500], // High
        [1000, 500], // High: Aspects++
        [500, 1000], // High: Executions++
        [1000, 1000], // Very High
    ];

    /** @return array<string, array{aspectCount: int, executionCount: int, useAspects?: bool, cached?: bool, production?: bool}> */
    public static function dataProvider(): array
    {
        $flags = [
            self::MEASURE_TYPE_NO_ASPECTS => [],
            self::MEASURE_TYPE_ASPECTS => ['useAspects' => true],
            self::MEASURE_TYPE_CACHED_ASPECTS => ['useAspects' => true, 'cached' => true],
            self::MEASURE_TYPE_PRODUCTION => ['useAspects' => true, 'cached' => true, 'production' => true],
        ];

        $data = [];

        foreach (self::$aspectCountAndExecutionCount as $count) {
            foreach ($flags as $measureType => $flag) {
                $aspectCount = $count[0];
                $executionCount = $count[1];

                $aspectsLabel = $aspectCount === 1 ? 'aspect' : 'aspects';
                $executionLabel = $executionCount === 1 ? 'execution' : 'executions';
                $dataProviderLabel = "{$measureType}: {$aspectCount} {$aspectsLabel}, {$executionCount} {$executionLabel}";

                $data[$dataProviderLabel] = [
                    'aspectCount' => $aspectCount,
                    'executionCount' => $executionCount,
                    'useAspects' => $flag['useAspects'] ?? false,
                    'cached' => $flag['cached'] ?? false,
                    'production' => $flag['production'] ?? false,
                ];
            }
        }

        // Cleanup data
        $data['Cleanup'] = [
            'aspectCount' => 0,
            'executionCount' => 0,
        ];

        // Number of tests should equal the number of generated data sets
        $dataCount = count($data);
        $expectedDataCount = (count(self::$aspectCountAndExecutionCount) * count($flags)) + 1;
        if ($dataCount !== $expectedDataCount) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new Exception("Expected {$expectedDataCount} data sets, got {$dataCount}");
        }

        if (extension_loaded('xdebug')) {
            $input = new ArgvInput();
            $output = new ConsoleOutput(decorated: true);
            $io = new SymfonyStyle($input, $output);

            $io->caution('Xdebug is enabled, which will slow down the tests');
        }

        return $data;
    }

    /** @noinspection PhpUnusedLocalVariableInspection */
    #[Test]
    #[DataProvider('dataProvider')]
    public function measurePerformance(
        int $aspectCount,
        int $executionCount,
        bool $useAspects = false,
        bool $cached = false,
        bool $production = false,
    ): void {
        $noFlags = !$useAspects && !$cached && !$production;
        $lastMeasure = $useAspects && $cached && $production;

        $firstRun =
            $aspectCount === self::$aspectCountAndExecutionCount[0][0]
            && $executionCount === self::$aspectCountAndExecutionCount[0][1]
            && $noFlags;

        $shouldCleanCache = $firstRun || $useAspects && !$cached;

        $lastRun = $aspectCount === 0 && $executionCount === 0 && $noFlags;

        if ($firstRun) {
            $this->cleanup();
        }

        if ($shouldCleanCache) {
            Util::clearCache();
        }

        if ($lastRun) {
            $this->cleanup();
            $this->assertTrue(true);
            return;
        }

        /** @var class-string<NumbersServiceInterface>[] $services */
        $services = [];
        if ($useAspects) {
            // Create $aspectCount aspects and a kernel that uses them
            $kernel = $this->createKernelAndAspects($aspectCount, $production);
        }
        if (!$useAspects) {
            // Emulate aspects by creating $aspectCount services
            $services = $this->createServices($aspectCount);
        }

        $this->useAspects = $useAspects;
        $this->cached = $cached;
        $this->production = $production;

        $this->startMeasure(self::MEASURE_TYPE_FROM_START_TO_END);
        $this->startMeasure(self::MEASURE_TYPE_BOOT);

        if ($useAspects) {
            /** @var class-string<AopKernel> $kernel */
            $kernel::init();
        }

        $this->endMeasure(self::MEASURE_TYPE_BOOT);
        $this->startMeasure(self::MEASURE_TYPE_CLASS_LOADING);

        $numbersClass = new Numbers();

        /** @var NumbersServiceInterface[] $serviceInstances */
        $serviceInstances = [];
        if (!$useAspects) {
            foreach ($services as $service) {
                $serviceClass = new ReflectionClass($service);
                if (!$serviceClass->isInstantiable()) {
                    throw new Exception('Expected an instantiable benchmark service');
                }
                $serviceInstances[] = $serviceClass->newInstance();
            }
        }

        $this->endMeasure(self::MEASURE_TYPE_CLASS_LOADING);
        // This is only used for validating the results, comment it out
        // $expectedResults = [];
        // $actualResults = [];
        $this->startMeasure(self::MEASURE_TYPE_EXECUTION);

        // There are 2 loops here, that could be merged into one, but the
        // performance difference is important, so it's not worth the
        // readability loss
        if ($useAspects) {
            foreach (range(1, $executionCount) as $ignored) {
                $result = $numbersClass->get();

                // This is only used for validating the results, comment it out
                // $expectedResults[] = $aspectCount;
                // $actualResults[] = $result;
            }
        }
        if (!$useAspects) {
            foreach (range(1, $aspectCount) as $i) {
                $numbersService = $serviceInstances[$i - 1];

                foreach (range(1, $executionCount) as $ignored) {
                    // Here we emulate the aspect by using a service
                    $numbersService->addToNumbers(1, $numbersClass);
                }

                $result = $numbersClass->get();

                // This is only used for validating the results, comment it out
                // $expectedResults[] = $executionCount;
                // $actualResults[] = $result;
            }
        }

        $this->endMeasure(self::MEASURE_TYPE_EXECUTION);
        $this->endMeasure(self::MEASURE_TYPE_FROM_START_TO_END);

        // This is only used for validating the results, comment it out
        // $this->assertEquals($expectedResults, $actualResults);

        $this->saveMeasuresToFile();

        if ($lastMeasure) {
            $this->printMeasures((string) $this->dataName());
        }

        $this->assertTrue(true);
    }

    /**
     * @return class-string<AopKernel>
     */
    private function createKernelAndAspects(int $aspectCount, bool $production): string
    {
        $tempDirectory = __DIR__ . '/Temp';
        if (!file_exists($tempDirectory)) {
            Filesystem::mkdir($tempDirectory);
        }

        $newKernelFilePath = "{$tempDirectory}/MeasurePerformanceKernel{$aspectCount}.php";
        /** @var class-string<AopKernel> $newKernelFileNamespace */
        $newKernelFileNamespace = "\\Okapi\\Aop\\Tests\\Performance\\Temp\\MeasurePerformanceKernel{$aspectCount}";

        /** @var string|null $kernelFile */
        static $kernelFile = null;
        if (!$kernelFile) {
            $kernelFile = Filesystem::readFile(__DIR__ . '/Kernel/MeasurePerformanceKernel.php');
        }

        $originalAspectLineNumber = 0;
        $originalAspectLine = '';
        $lines = explode("\n", $kernelFile);
        foreach ($lines as $lineNumber => &$line) {
            // Replace namespace
            if (str_contains($line, 'namespace Okapi\\Aop\\Tests\\Performance\\Kernel')) {
                $line = str_replace(search: 'Kernel', replace: 'Temp', subject: $line);
            }

            // Replace class name
            if (str_contains($line, 'class MeasurePerformanceKernel')) {
                $line = str_replace(
                    search: 'MeasurePerformanceKernel',
                    replace: "MeasurePerformanceKernel{$aspectCount}",
                    subject: $line,
                );
            }

            // Find the line where the "AddOneAspect" is added to the kernel
            if (str_contains($line, 'AddOneAspect::class,')) {
                $originalAspectLineNumber = $lineNumber;
                $originalAspectLine = $line;
                break;
            }

            // Replace environment
            if ($production && str_contains($line, 'Environment::DEVELOPMENT')) {
                $line = str_replace(
                    search: 'Environment::DEVELOPMENT',
                    replace: 'Environment::PRODUCTION',
                    subject: $line,
                );
            }
        }

        $aspects = [];
        foreach (range(1, $aspectCount) as $aspectNumber) {
            $aspects[] = str_replace(
                search: 'Aspect\\AddOneAspect::class,',
                replace: "Temp\\AddOneAspect{$aspectNumber}::class,",
                subject: $originalAspectLine,
            );

            // Read aspect file
            /** @var string|null $aspectFile */
            static $aspectFile = null;
            if (!$aspectFile) {
                $aspectFile = Filesystem::readFile(__DIR__ . '/Aspect/AddOneAspect.php');
            }

            $newAspectFilePath = __DIR__ . "/Temp/AddOneAspect{$aspectNumber}.php";
            if (file_exists($newAspectFilePath)) {
                continue;
            }

            $newAspectFile = $aspectFile;

            // Replace namespace
            $newAspectFile = str_replace(
                search: 'namespace Okapi\\Aop\\Tests\\Performance\\Aspect;',
                replace: 'namespace Okapi\\Aop\\Tests\\Performance\\Temp;',
                subject: $newAspectFile,
            );

            // Replace class name
            $newAspectFile = str_replace(
                search: 'class AddOneAspect',
                replace: "class AddOneAspect{$aspectNumber}",
                subject: $newAspectFile,
            );

            // Write aspect file
            Filesystem::writeFile($newAspectFilePath, $newAspectFile);

            $this->cacheFile($newAspectFilePath);
        }

        unset($lines[$originalAspectLineNumber]); // Remove the original aspect

        array_splice($lines, $originalAspectLineNumber, 0, $aspects); // Add the new aspects

        $kernelFile = implode("\n", $lines);

        // Write kernel file
        Filesystem::writeFile($newKernelFilePath, $kernelFile);

        $this->cacheFile($newKernelFilePath);

        $this->dumpAutoload();

        return $newKernelFileNamespace;
    }

    /**
     * @return class-string<NumbersServiceInterface>[]
     */
    private function createServices(int $serviceCount): array
    {
        $tempDirectory = __DIR__ . '/Temp';
        if (!file_exists($tempDirectory)) {
            Filesystem::mkdir($tempDirectory);
        }

        $services = [];
        foreach (range(1, $serviceCount) as $serviceNumber) {
            // Read service file
            /** @var string|null $serviceFile */
            static $serviceFile = null;
            if (!$serviceFile) {
                $serviceFile = Filesystem::readFile(__DIR__ . '/Service/NumbersService.php');
            }

            /** @var class-string<NumbersServiceInterface> $serviceNamespace */
            $serviceNamespace = "\\Okapi\\Aop\\Tests\\Performance\\Temp\\NumbersService{$serviceNumber}";
            $services[] = $serviceNamespace;

            $newServiceFilePath = __DIR__ . "/Temp/NumbersService{$serviceNumber}.php";
            if (file_exists($newServiceFilePath)) {
                continue;
            }

            $newServiceFile = $serviceFile;

            // Replace namespace
            $newServiceFile = str_replace(
                search: 'namespace Okapi\\Aop\\Tests\\Performance\\Service;',
                replace: 'namespace Okapi\\Aop\\Tests\\Performance\\Temp;',
                subject: $newServiceFile,
            );

            // Replace class name
            $newServiceFile = str_replace(
                search: 'class NumbersService',
                replace: "class NumbersService{$serviceNumber}",
                subject: $newServiceFile,
            );

            // Write service file
            Filesystem::writeFile($newServiceFilePath, $newServiceFile);

            $this->cacheFile($newServiceFilePath);
        }

        $this->dumpAutoload();

        return $services;
    }

    private function cacheFile(string $filename): void
    {
        if (function_exists('opcache_compile_file')) {
            opcache_compile_file($filename);
        }
    }

    private function dumpAutoload(): void
    {
        $workingDir = __DIR__ . '/../..';
        $workingDir = DIRECTORY_SEPARATOR === '\\'
            ? str_replace('/', '\\', $workingDir)
            : str_replace('\\', '/', $workingDir);

        ob_start();
        shell_exec("composer dump-autoload -d {$workingDir} -o -q");
        ob_end_clean();
    }

    private function startMeasure(string $name): void
    {
        $type = $this->getMeasureType();

        $this->measures[$type][$name] = [
            self::START_TIME => microtime(true),
            self::START_MEMORY => memory_get_usage(),
        ];
    }

    private function endMeasure(string $name): void
    {
        $type = $this->getMeasureType();

        $this->measures[$type][$name][self::END_TIME] = microtime(true);
        $this->measures[$type][$name][self::END_MEMORY] = memory_get_usage();
    }

    private function saveMeasuresToFile(): void
    {
        $tempDirectory = __DIR__ . '/Temp';

        $measuresFile = "{$tempDirectory}/measures.json";
        $measures = $this->measures;
        if (file_exists($measuresFile)) {
            $measures = $this->readMeasures($measuresFile);
            $measures[$this->getMeasureType()] = $this->measures[$this->getMeasureType()];
        }

        // Save it every execution, because #[RunTestsInSeparateProcesses]
        // will not store $this->measures between executions
        Filesystem::writeFile($measuresFile, json_encode($measures, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }

    /** @return array<string, array<string, array<string, int|float>>> */
    private function readMeasures(string $path): array
    {
        /** @var mixed $decoded */
        $decoded = json_decode(Filesystem::readFile($path), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new Exception('Expected measurement groups in ' . $path);
        }

        $measures = [];
        /** @var mixed $group */
        foreach ($decoded as $type => $group) {
            if (!is_string($type) || !is_array($group)) {
                throw new Exception('Invalid measurement group in ' . $path);
            }
            $measures[$type] = [];
            /** @var mixed $metrics */
            foreach ($group as $name => $metrics) {
                if (!is_string($name) || !is_array($metrics)) {
                    throw new Exception('Invalid measurement in ' . $path);
                }
                $measures[$type][$name] = [];
                /** @var mixed $value */
                foreach ($metrics as $metric => $value) {
                    if (!is_string($metric) || !is_int($value) && !is_float($value)) {
                        throw new Exception('Invalid measurement metric in ' . $path);
                    }
                    $measures[$type][$name][$metric] = $value;
                }
            }
        }
        return $measures;
    }

    private function getMeasureType(): string
    {
        if ($this->production) {
            return self::MEASURE_TYPE_PRODUCTION;
        }

        if ($this->cached) {
            return self::MEASURE_TYPE_CACHED_ASPECTS;
        }

        if ($this->useAspects) {
            return self::MEASURE_TYPE_ASPECTS;
        }

        return self::MEASURE_TYPE_NO_ASPECTS;
    }

    // region Print Measures

    private function printMeasures(string $dataProviderLabel): void
    {
        $this->measures = $this->readMeasures(__DIR__ . '/Temp/measures.json');

        // Remove the last measure, because it's the cleanup
        $dataProviderLabel = str_replace(
            search: (array_key_last($this->measures) ?? '') . ': ',
            replace: '',
            subject: $dataProviderLabel,
        );

        $input = new ArgvInput();
        $output = new ConsoleOutput(decorated: true);
        $io = new SymfonyStyle($input, $output);

        $output->writeln('');

        $io->section($dataProviderLabel);

        // First Table
        $output->writeln("<fg=cyan>Table 1: Without Aspects vs With Aspects ({$dataProviderLabel})</>");
        $this->printTable($output, self::MEASURE_TYPE_ASPECTS);

        // Second Table
        $output->writeln('');
        $output->writeln("<fg=cyan>Table 2: Without Aspects vs With Cached Aspects ({$dataProviderLabel})</>");
        $this->printTable($output, self::MEASURE_TYPE_CACHED_ASPECTS);

        // Third Table
        $output->writeln('');
        $output->writeln("<fg=cyan>Table 3: Without Aspects vs Production ({$dataProviderLabel})</>");
        $this->printTable($output, self::MEASURE_TYPE_PRODUCTION);

        // Fourth Table
        $output->writeln('');
        $output->writeln("<fg=cyan>Table 4: With Cached Aspects vs Production ({$dataProviderLabel})</>");
        $this->printTable($output, self::MEASURE_TYPE_PRODUCTION, self::MEASURE_TYPE_CACHED_ASPECTS);

        $output->writeln('');
    }

    private function printTable(
        ConsoleOutput $output,
        string $comparisonAspect,
        string $compareToType = self::MEASURE_TYPE_NO_ASPECTS,
    ): void {
        $table = new Table($output);

        $headers = [
            'Measure Type',
            'Metric',
            $compareToType,
            $comparisonAspect,
            'Difference',
        ];
        $table->setHeaders($headers);

        $measuresToCompareWith = $this->measures[$compareToType];
        foreach ($measuresToCompareWith as $measureType => $metrics) {
            $table->addRow($this->generateRowData(
                $measureType,
                $comparisonAspect,
                $compareToType,
                self::METRIC_TYPE_TIME,
            ));
        }

        foreach ($measuresToCompareWith as $measureType => $metrics) {
            $table->addRow($this->generateRowData(
                $measureType,
                $comparisonAspect,
                $compareToType,
                self::METRIC_TYPE_MEMORY,
            ));
        }

        // Pad the values to the left
        $tableStyle = new TableStyle();
        $tableStyle->setPadType(STR_PAD_LEFT);
        $table->setColumnStyle(2, $tableStyle);
        $table->setColumnStyle(3, $tableStyle);
        $table->setColumnStyle(4, $tableStyle);

        $table->render();
    }

    /** @return list<string> */
    private function generateRowData(
        string $measureType,
        string $comparisonAspect,
        string $compareToType,
        string $metricType,
    ): array {
        // Get start and end metrics
        $startMetric = $metricType === self::METRIC_TYPE_TIME ? self::START_TIME : self::START_MEMORY;

        $endMetric = $metricType === self::METRIC_TYPE_TIME ? self::END_TIME : self::END_MEMORY;

        // Calculate without aspects
        $withoutAspectsValue =
            $this->measures[$compareToType][$measureType][$endMetric]
            - $this->measures[$compareToType][$measureType][$startMetric];

        // Calculate with aspects
        $comparisonValue =
            $this->measures[$comparisonAspect][$measureType][$endMetric]
            - $this->measures[$comparisonAspect][$measureType][$startMetric];

        // For memory, convert to MB
        if ($metricType === self::METRIC_TYPE_MEMORY) {
            $withoutAspectsValue /= 1024 * 1024;
            $comparisonValue /= 1024 * 1024;
        }

        // Calculate difference
        $difference = $comparisonValue - $withoutAspectsValue;

        // Time: 8 digits, Memory: 4 digits
        $digits = $metricType === self::METRIC_TYPE_TIME ? 8 : 4;

        // Format digits
        $withoutAspectsValue = number_format($withoutAspectsValue, $digits);
        $comparisonValue = number_format($comparisonValue, $digits);
        $difference = number_format($difference, $digits);

        // Prefix difference with + or -
        $prefix = $difference > 0 ? '+' : '';
        $differenceText = "{$prefix}{$difference}";

        // Append unit
        $append = $metricType === self::METRIC_TYPE_TIME ? ' s' : ' MB';
        $withoutAspectsValue .= $append;
        $comparisonValue .= $append;
        $differenceText .= $append;

        if ($difference > 0) {
            $differenceText = "<fg=red>{$differenceText}</>";
        }
        if ($difference < 0) {
            $differenceText = "<fg=green>{$differenceText}</>";
        }

        return [
            $measureType,
            $metricType,
            $withoutAspectsValue,
            $comparisonValue,
            $differenceText,
        ];
    }

    // endregion

    private function cleanup(): void
    {
        Filesystem::rm(__DIR__ . '/Temp', recursive: true, force: true);
    }
}
