<?php

namespace ReportMaker\Builders;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CsBuilder extends Builder
{
    protected const REPORT_CLASS = self::BASE_REPORT_CLASS . "CodeSniffer\\Gitlab";

    protected const BIN_DIR = self::VENDOR_BIN_DIR . "phpcs";

    protected const STAND_PHPCOMPATIBILITY = "PHPCompatibility";
    protected const STAND_CUSTOM = "./phpcs.xml.dist";

    protected const PHPCOMPATIBILITY_OUTPUT_REPORT_FILE = "php_compatibility-report.json";
    protected const CUSTOM_OUTPUT_REPORT_FILE = "phpcs-report.json";

    protected static array $requiredParameters = [
        'file' => [
            'definition' => ["--files", "-f"],
            'method' => 'getChangedFiles',
            'nullable' => false
        ],
        'standart' => [
            'definition' => ["--standart", "-s"],
            'method' => 'getParameter',
            'nullable' => true
        ],
        'php_version' => [
            'definition' => ["--version", "-v"],
            'method' => 'getParameter',
            'nullable' => false
        ]
    ];

    protected array $files;

    protected ?string $standart;

    protected string $version;

    public function __construct($files, $standart, $version)
    {
        $this->files = $files;
        $this->standart = $standart;
        $this->version = $version;
    }

    public function exec(): void
    {
        $changedFiles = $this->getExistenceFiles();

        if (!empty($changedFiles)) {
            if (self::STAND_PHPCOMPATIBILITY === $this->standart) {
                $phpcsConfig = new Process(
                    [
                        self::BIN_DIR,
                        "--config-set",
                        "installed_paths",
                        "vendor/phpcompatibility/php-compatibility"
                    ]
                );
                $phpcsConfig->run();

                if (!$phpcsConfig->isSuccessful()) {
                    throw new ProcessFailedException($phpcsConfig);
                }

                $process = new Process(
                    [
                        self::BIN_DIR,
                        "-s",
                        "-p",
                        $changedFiles,
                        "--standart=" . self::STAND_PHPCOMPATIBILITY,
                        "--report-" . self::REPORT_CLASS,
                        "--report-file=" . self::PHPCOMPATIBILITY_OUTPUT_REPORT_FILE,
                        "--no-cache",
                        "--runtime-set",
                        "testVersion",
                        $this->version
                    ]
                );

                $process->run();

            } else if (self::STAND_CUSTOM === $this->standart || null === $this->standart) {
                $command = [
                    self::BIN_DIR,
                    "-p",
                    $changedFiles,
                    "--report-" . self::REPORT_CLASS,
                    "--report-file=" . self::CUSTOM_OUTPUT_REPORT_FILE,
                    "--no-cache",
                    "--runtime-set",
                    "testVersion",
                    $this->version
                ];

                if (self::STAND_CUSTOM === $this->standart) {
                    $command[] = "--standart=" . self::STAND_CUSTOM;
                }

                $process = new Process($command);

                $process->run();
            }
        }
    }

    protected function getExistenceFiles(): string
    {
        $existFiles = "";

        $this->files = \array_filter($this->files, function (string $file) {
            return \file_exists($file);
        });

        if (!empty($this->files)) {
            $existFiles = \implode(" ", $this->files);
        }

        return $existFiles;
    }
}