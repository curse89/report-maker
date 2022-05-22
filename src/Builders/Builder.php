<?php

namespace ReportMaker\Builders;

abstract class Builder
{
    protected const BASE_REPORT_CLASS = "\\RetailCrm\\CodeQuality\\Report\\";

    protected const VENDOR_BIN_DIR = "/vendor/bin/";

    protected static array $requiredParameters = [
        'file' => [
            'definition' => [],
            'method' => 'getChangedFiles'
        ]
    ];

    public static function getRequiredParameters(): ?array
    {
        return static::$requiredParameters;
    }

    abstract public function exec(): void;
}
