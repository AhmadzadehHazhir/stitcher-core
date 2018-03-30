<?php

namespace Pageon;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Illuminate\Support\Arr;
use Iterator;
use Stitcher\Exception\InvalidConfiguration;
use Stitcher\File;
use Symfony\Component\Finder\Finder;

class Config
{
    protected static $env;
    protected static $loadedConfiguration = [];
    protected static $plugins = [];

    public static function init(): void
    {
        self::$env = new Dotenv(File::path());

        try {
            self::$env->load();
        } catch (InvalidPathException $e) {
            throw InvalidConfiguration::dotEnvNotFound(File::path());
        }

        $configurationFiles = Finder::create()->files()->in(File::path('config'))->name('*.php')->getIterator();

        $loadedConfiguration = self::load($configurationFiles);

        self::registerPlugins($loadedConfiguration);

        self::registerConfiguration($loadedConfiguration);
    }

    public static function get(string $key)
    {
        return self::$loadedConfiguration[$key] ?? null;
    }

    public static function all(): array
    {
        return self::$loadedConfiguration;
    }

    public static function plugins(): array
    {
        return self::$plugins;
    }

    protected static function defaults(): array
    {
        return [
            'rootDirectory' => File::path(),
            'templateRenderer' => 'twig',
            'staticFiles' => [],
            'cacheStaticFiles' => false,
            'cacheImages' => true,
        ];
    }

    protected static function load(Iterator $configurationFiles): array
    {
        $loadedConfiguration = [];

        foreach ($configurationFiles as $configurationFile) {
            $loadedFileConfiguration = require $configurationFile;

            if (! is_array($loadedFileConfiguration)) {
                continue;
            }

            $loadedConfiguration = array_merge($loadedConfiguration, $loadedFileConfiguration);
        }

        return $loadedConfiguration;
    }

    protected static function registerPlugins(array $loadedConfiguration): void
    {
        self::$plugins = $loadedConfiguration['plugins'] ?? [];
    }

    protected static function registerConfiguration(array $loadedConfiguration): void
    {
        self::$loadedConfiguration = array_merge(
            self::defaults(),
            $loadedConfiguration,
            Arr::dot($loadedConfiguration)
        );
    }
}
