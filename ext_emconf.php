<?php

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

$EM_CONF[$_EXTKEY] = [
    'title' => 'GS Cache Config',
    'description' => 'GS Cache Config allows you to switch various caching backends to APCu with fallback in case of CLI mode. Configuration can simply be adapted by the Extension Manager.',
    'version' => '0.0.5',
    'category' => 'misc',
    'constraints' => [
        'depends' => [
            'php' => '7.1.0-0.0.0',
            'typo3' => '8.7.0-9.5.99',
            'gslib' => '0.0.9-0.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'state' => 'beta',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Simon Gilli',
    'author_email' => 'typo3@gilbertsoft.org',
    'author_company' => 'Gilbertsoft',
    'autoload' => [
        'psr-4' => [
            'Gilbertsoft\\CacheConfig\\' => 'Classes'
        ]
    ],
    'autoload-dev' => [
        'psr-4' => [
            'Gilbertsoft\\CacheConfig\\Tests\\' => 'Tests'
        ]
    ]
];
