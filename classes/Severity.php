<?php
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

namespace CollectLogsModule;

use Translate;

class Severity
{
    const SEVERITY_ERROR = 4;
    const SEVERITY_WARNING = 3;
    const SEVERITY_DEPRECATION = 2;
    const SEVERITY_NOTICE = 1;

    const ERROR_TYPE_MAPPING = [
        'Fatal error' => self::SEVERITY_ERROR,
        'Warning' => self::SEVERITY_WARNING,
        'Notice' => self::SEVERITY_NOTICE,
        'Deprecation' => self::SEVERITY_DEPRECATION,
        'Unknown error' => self::SEVERITY_NOTICE,
        'Exception' => self::SEVERITY_ERROR,
    ];

    /**
     * @param int $level
     *
     * @return bool
     */
    public static function isSeverityLevel($level)
    {
        return in_array((int)$level, [
            static::SEVERITY_ERROR,
            static::SEVERITY_WARNING,
            static::SEVERITY_DEPRECATION,
            static::SEVERITY_NOTICE,
        ]);
    }

    /**
     * @param string $level
     *
     * @return string
     */
    public static function getSeverityBadge($level)
    {
        switch ((int)$level) {
            case static::SEVERITY_ERROR:
                return 'badge-critical';
            case static::SEVERITY_WARNING:
                return 'badge-danger';
            case static::SEVERITY_DEPRECATION:
                return 'badge-warning';
            case static::SEVERITY_NOTICE:
            default:
                return 'badge-info';
        }
    }

    /**
     * @param int $level
     *
     * @return string
     */
    public static function getSeverityName($level)
    {
        switch ((int)$level) {
            case static::SEVERITY_ERROR:
                return Translate::getModuleTranslation('collectlogs', 'Error', 'Severity');
            case static::SEVERITY_WARNING:
                return Translate::getModuleTranslation('collectlogs', 'Warning', 'Severity');
            case static::SEVERITY_DEPRECATION:
                return Translate::getModuleTranslation('collectlogs', 'Deprecation', 'Severity');
            case static::SEVERITY_NOTICE:
                return Translate::getModuleTranslation('collectlogs', 'Notice', 'Severity');
            default:
                return Translate::getModuleTranslation('collectlogs', 'Unknown severity', 'Severity');
        }
    }

    /**
     * @param string $type
     *
     * @return int
     */
    public static function getSeverity(string $type)
    {
        if (isset(static::ERROR_TYPE_MAPPING[$type])) {
            return static::ERROR_TYPE_MAPPING[$type];
        }
        return static::SEVERITY_NOTICE;
    }
}