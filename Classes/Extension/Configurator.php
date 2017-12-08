<?php
namespace Gilbertsoft\CacheConfig\Extension;

/*
 * This file is part of the "GS Cache Config" Extension for TYPO3 CMS.
 *
 * Copyright (C) 2017 by Gilbertsoft (gilbertsoft.org)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * For the full license information, please read the LICENSE file that
 * was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Use declarations
 */
use Gilbertsoft\Lib\Extension\AbstractConfigurator;
use Gilbertsoft\CacheConfig\Service\InstallService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The Configuration Modifier class.
 *
 * USE:
 * The class is intended to be used without creating an instance of it.
 * So: Do not instantiate - call functions with "\Gilbertsoft\ProtectedConfig\Configuration\Modifier::" prefixed the function name.
 * So use \Gilbertsoft\ProtectedConfig\Configuration\Modifier::[method-name] to refer to the functions, eg. '\Gilbertsoft\ProtectedConfig\Configuration\Modifier::processLocalConfiguration($extensionKey)'
 */
class Configurator extends AbstractConfigurator
{
    const BACKEND_DATABASE = \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class;
    const BACKEND_FILE = \TYPO3\CMS\Core\Cache\Backend\FileBackend::class;
    const BACKEND_APC = \TYPO3\CMS\Core\Cache\Backend\ApcBackend::class;
    const BACKEND_APCU = \TYPO3\CMS\Core\Cache\Backend\ApcuBackend::class;

    /**
     * @param string $string String to be converted to lowercase underscore
     * @return string lowercase_and_underscored_string
     */
    protected static function sanitizeValue(array &$conf, $value, $default)
    {
        if (!isset($conf[$value])) {
            $conf[$value] = $default;
        }
    }

    /**
     * @param string $extensionKey Extension key to load config from
     * @return array Sanitized extension configuration array
     */
    protected static function getSanitizedExtConf($extensionKey)
    {
        $conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extensionKey]);

        self::sanitizeValue($conf, 'cachingConfigEnable', true);
        self::sanitizeValue($conf, 'cachingOptimizationEnable', true);
        self::sanitizeValue($conf, 'cachingCliFallbackExtbaseObject', true);

        return $conf;
    }

    /**
     * @param string $backendClassName Backend class name
     * @param string $cacheName Cache name
     * @return void
     */
    protected static function setCacheBackend($backendClassName, $cacheName)
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheName])) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheName]['backend'] = $backendClassName;

            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheName]['options'])) {
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheName]['options'] = [];
            }
        }
    }

    /**
     * @param array $extConf Extension configuration array
     * @return void
     */
    protected static function handleCachingConfiguration($extConf)
    {
        // Override cache configuration
        $context = GeneralUtility::getApplicationContext();

        if (self::isCli()) {
            self::enableApcOnCli($extConf);
        }

        $apcExtensionLoaded = extension_loaded('apc');
        $apcuExtensionLoaded = extension_loaded('apcu');
        $apcAvailable = $apcExtensionLoaded || $apcuExtensionLoaded;
        $apcEnabled = ini_get('apc.enabled') == true;

        if (self::isCli()) {
            $apcEnabled = $apcEnabled && ini_get('apc.enable_cli') == true;
        }

        if (!$context->isDevelopment() && $apcAvailable && $apcEnabled) {
            $backendClassName = $apcuExtensionLoaded ? self::BACKEND_APCU : self::BACKEND_APC;
        } else {
            $backendClassName = self::BACKEND_FILE;
        }

        self::setCacheBackend($backendClassName, 'cache_hash');
        self::setCacheBackend($backendClassName, 'cache_imagesizes');
        self::setCacheBackend($backendClassName, 'cache_pages');
        self::setCacheBackend($backendClassName, 'cache_pagesection');
        self::setCacheBackend($backendClassName, 'cache_rootline');
        self::setCacheBackend($backendClassName, 'extbase_datamapfactory_datamap');

        if (self::isCli() && ($extConf['cachingCliFallbackExtbaseObject'] == 1)) {
            self::setCacheBackend(self::BACKEND_FILE, 'extbase_object');
        } else {
            self::setCacheBackend($backendClassName, 'extbase_object');
        }

        self::setCacheBackend($backendClassName, 'extbase_reflection');
        self::setCacheBackend($backendClassName, 'extbase_typo3dbbackend_queries');
        self::setCacheBackend($backendClassName, 'extbase_typo3dbbackend_tablecolumns');
    }


    /**
     * Enables the use of APC on CLI
     *
     * @param array $extConf Extension configuration array
     * @return void
     * @throws \RuntimeException
     */
    protected static function enableApcOnCli($extConf)
    {
        if (!ini_get('apc.enable_cli')) {
            ini_set('apc.enable_cli', '1');
        }
    }

    /**
     * Called from ext_localconf.php.
     *
     * @param string $extensionKey Extension key
     * @return void
     */
    public static function localconf($extensionKey)
    {
        InstallService::registerService($extensionKey);
    }

    /**
     * Called from ext_tables.php.
     *
     * @param string $extensionKey Extension key
     * @return void
     */
    public static function tables($extensionKey)
    {
    }

    /**
     * Called from additionalConfiguration.php.
     *
     * @param string $extensionKey Extension key
     * @return void
     */
    public static function additionalConfiguration($extensionKey)
    {
        // Get the configuration
        $extConf = self::getSanitizedExtConf($extensionKey);

        if ($extConf['cachingConfigEnable'] == 1) {
            self::handleCachingConfiguration($extConf);
        }
    }
}
