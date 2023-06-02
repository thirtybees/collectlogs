<?php
/**
 * Copyright (C) 2022-2022 thirty bees
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
 * @copyright 2022 - 2022 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

namespace CollectLogsModule;

use Psr\Log\LoggerInterface;

if (! defined('_TB_VERSION_')) {
    exit();
}

/**
 * Logger that implements LoggerInterface
 *
 * Exists for backwaards compatibility with thirty bees 1.4, which requires LoggerInterface
 */
class PsrLogger extends Logger implements LoggerInterface
{
}