<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addStaticFile(
    'powermail_crshield',
    'Configuration/TypoScript',
    'Powermail challenge/response spambot shield'
);
