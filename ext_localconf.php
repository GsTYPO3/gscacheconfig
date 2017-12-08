<?php
defined('TYPO3_MODE') || die('Access denied.');

if (class_exists('Gilbertsoft\CacheConfig\Extension\Configurator')) {
    \Gilbertsoft\CacheConfig\Extension\Configurator::localconf($_EXTKEY);
}
