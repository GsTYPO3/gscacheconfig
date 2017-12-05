<?php
namespace Gilbertsoft\CacheConfig\Service;

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
use Gilbertsoft\Lib\Service\AbstractInstallService;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * GS Proteced Config Install Service class.
 */
class InstallService extends AbstractInstallService
{
    /**
     * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager
     */
    protected $configurationManager = null;

    /**
     * Get the ConfigurationManager
     *
     * @return \TYPO3\CMS\Core\Configuration\ConfigurationManager
     */
    protected function getConfigurationManager()
    {
        if (!isset($this->configurationManager)) {
            $this->configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        }

        return $this->configurationManager;
    }

    /**
     * Returns the lines from AdditionalConfiguration.php file without own additions
     *
     * @param string $extensionKey Extension key
     */
    protected function getCleanAdditionalConfiguration($extensionKey)
    {
        $newLines = [];

        // Load content and search for the include
        if (($content = GeneralUtility::getUrl($this->getConfigurationManager()->getAdditionalConfigurationFileLocation())) !== false)
        {
            $currentLines = explode(LF, $content);

            // Delete the php marker line
            array_shift($currentLines);

            //
            $startFound = false;
            $endFound = false;

            foreach ($currentLines as $line)
            {
                if (($startFound) && (!$endFound)) {
                    $endFound = (strpos($line, '}') !== false);
                } elseif ((!$startFound) && (strpos($line, $extensionKey) !== false)) {
                    $startFound = true;
                    $endFound = false;
                } else {
                    $newLines[] = $line;
                }
            }
        }

        // Remove blank lines at the end
        $revLines = array_reverse($newLines);
        $newLines = [];
        $lineFound = false;

        foreach ($revLines as $line)
        {
            if ($lineFound || !empty(trim($line))) {
                $newLines[] = $line;
                $lineFound = true;
            }
        }

        return array_reverse($newLines);
    }

    /**
     * Creates the AdditionalConfiguration.php file with necessary includes
     *
     * @param string $extensionKey Extension key
     */
    protected function updateAdditionalConfiguration($extensionKey)
    {
        /**
         * Add Flashmessage that the configuration has to be checked
         */
        $this->showFlashMessage(
            'To enable the features you have to edit the extensions configuration in the Extension Manager. After the new configuration is saved please relogin to the backend to ensure the changes!',
            'Check your configuration',
            FlashMessage::NOTICE,
            true
        );

        /**
         * Update configuration
         */
        $newLines = $this->getCleanAdditionalConfiguration($extensionKey);

        if (!empty(end($newLines))) {
            $newLines[] = '';
        }

        $newLines[] = '// Run configuration modifier for extension ' . $extensionKey . ' - added on ' . date(DATE_ATOM) . ' by ' . __CLASS__;
        $newLines[] = 'if (class_exists(\'Gilbertsoft\CacheConfig\Extension\Configurator\')) {';
        $newLines[] = '    \Gilbertsoft\CacheConfig\Extension\Configurator::additionalConfiguration(\'' . $extensionKey . '\');';
        $newLines[] = '}';

        $this->getConfigurationManager()->writeAdditionalConfiguration($newLines);

        /**
         * Add Flashmessage that the configuration has written
         */
        $this->showFlashMessage(
            'Configuration successfully added to ' .
            $this->getConfigurationManager()->getAdditionalConfigurationFileLocation() .
            '.',
            'Configuration added',
            FlashMessage::OK,
            true
        );
    }

    /**
     * Removes the added lines in the AdditionalConfiguration.php file
     *
     * @param string $extensionKey Extension key
     */
    protected function removeAdditionalConfiguration($extensionKey)
    {
        $newLines = $this->getCleanAdditionalConfiguration($extensionKey);

        if (empty($newLines) !== true)
        {
            $this->getConfigurationManager()->writeAdditionalConfiguration($newLines);

            /**
             * Add Flashmessage that the configuration has written
             */
            $this->showFlashMessage(
                'Configuration successfully removed from ' .
                $this->getConfigurationManager()->getAdditionalConfigurationFileLocation() .
                '.',
                'Configuration removed',
                FlashMessage::OK,
                true
            );
        }
        else
        {
            unlink($this->getConfigurationManager()->getAdditionalConfigurationFileLocation());

            /**
             * Add Flashmessage that the configuration has deleted
             */
            $this->showFlashMessage(
                'File ' . $this->getConfigurationManager()->getAdditionalConfigurationFileLocation() . ' successfully deleted.',
                'Configuration delete',
                FlashMessage::OK,
                true
            );
        }
    }

    /**
     * Executes the setup tasks if extension is installed.
     *
     * @param string $extensionKey Installed extension name
     */
    public function afterInstall($extensionKey)
    {
        if ($extensionKey == $this->extensionKey)
        {
            $this->updateAdditionalConfiguration($extensionKey);
        }
    }

    /**
     * Executes the setup tasks if extension is uninstalled.
     *
     * @param string $extensionKey Uninstalled extension name
     */
    public function afterUninstall($extensionKey)
    {
        if ($extensionKey == $this->extensionKey)
        {
            $this->removeAdditionalConfiguration($extensionKey);
        }
    }
}
