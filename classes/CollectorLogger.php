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

use Db;
use DbQuery;
use PrestaShopException;
use Psr\Log\AbstractLogger;
use Thirtybees\Core\Error\ErrorUtils;
use Throwable;

class CollectLogLogger extends AbstractLogger
{
    /**
     * @var array
     */
    protected $messageConvertRegexp = null;

    const SEVERITIES = [
        'Fatal error' => 4,
        'Warning' => 3,
        'Notice' => 1,
        'Deprecation' => 2,
        'Unknown error' => 1,
        'Exception' => 4,
    ];

    /**
     * @param int $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        try {
            $type = $context['type'] ?? 'unknown';
            $file = $context['errfile'] ?? 'unknown';
            $line = (int)($context['errline'] ?? 0);
            $message = $context['errstr'] ?? $message;
            $extra = $context['extra'] ?? [];
            $realFile = $context['realFile'] ?? '';
            $realLine = (int)($context['realLine'] ?? 0);

            $hasRealFile = $realFile && $realLine;
            $genericMessage = $this->generifyMessage($message);
            if ($hasRealFile) {
                $uid = $this->calculateUID($type, $realFile, $realLine, $genericMessage);
            } else {
                $uid = $this->calculateUID($type, $file, $line, $genericMessage);
            }

            $errorId = $this->getErrorLogId($uid);
            if ($errorId) {
                $this->markErrorSeen($errorId);
            } else {
                if (! $this->hasStackTrace($extra)) {
                    $extra[] = [
                        'label' => 'Stacktrace',
                        'content' => $this->getStackTrace(),
                    ];
                }
                $extra[] = [
                    'label' => 'HTTP Request',
                    'content' => $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . "\n"
                ];
                if (isset($_SERVER['HTTP_REFERER'])) {
                    $extra[] = [
                        'label' => 'Referrer',
                        'content' => $_SERVER['HTTP_REFERER'],
                    ];
                }
                if ($_GET) {
                    $params = "";
                    foreach ($_GET as $param => $value) {
                        $params .= "  [$param]: " . ErrorUtils::displayArgument($value) . "\n";
                    }
                    $extra[] = [
                        'label' => 'GET parameters',
                        'content' => $params
                    ];
                }
                if ($_POST) {
                    $params = "";
                    foreach ($_POST as $param => $value) {
                        $params .= "  [$param]: " . ErrorUtils::displayArgument($value) . "\n";
                    }
                    $extra[] = [
                        'label' => 'POST parameters',
                        'content' => $params
                    ];
                }
                $cookie = \Context::getContext()->cookie;
                $params = "";
                foreach ($cookie->getAll() as $param => $value) {
                    $params .= "  [$param]: " . ErrorUtils::displayArgument($value) . "\n";
                }
                $extra[] = [
                    'label' => 'Cookie',
                    'content' => $params
                ];

                $this->insertErrorToDb($uid, $type, $file, $line, $realFile, $realLine, $genericMessage, $message, $extra);
            }

            $this->logMessageToFile($errorId === 0, $uid, $type, $message, $file, $line, $hasRealFile, $realFile, $realLine, $extra);
        } catch (Throwable $t) {
            trigger_error("collectlogs: failed to log error: " . $t->getMessage(), E_USER_WARNING);
        }
    }

    /**
     * @param string $message
     * @return string
     * @throws PrestaShopException
     */
    protected function generifyMessage($message)
    {
        foreach ($this->getMessageConvertors() as $search => $replace) {
            $message = preg_replace($search, $replace, $message);
        }
        return $message;
    }

    /**
     * @param $type
     * @param $file
     * @param int $line
     * @param $message
     * @return string
     */
    protected function calculateUID($type, $file, int $line, $message)
    {
        return md5($type . $file . $line . $message);
    }

    /**
     * @param bool $isNew
     * @param string $uid
     * @param string $type
     * @param string $message
     * @param string $file
     * @param int $line
     * @param bool $hasRealFile
     * @param string $realFile
     * @param int $realLine
     * @param array $extra
     * @return void
     */
    protected function logMessageToFile($isNew, $uid, $type, $message, $file, int $line, bool $hasRealFile, $realFile, int $realLine, $extra)
    {
        $newOld = $isNew ? 'NEW' : 'OLD';
        $formattedMessage = '[' . date('H:i:s.ss') . '] ['.$newOld.'] ['.$uid.'] [' . strtoupper($type) . '] ' . $message . " in file $file";
        if ($line) {
            $formattedMessage .= " at line $line.";
        } else {
            if ($hasRealFile) {
                $formattedMessage .= " [" . $realFile . ':' . $realLine . "]";
            }
        }
        // display extra information for new entries only
        if ($isNew) {
            $indent = "\n    ";
            foreach ($extra as $section) {
                $formattedMessage .= $indent . $section['label'] . ":";
                $formattedMessage .= rtrim($indent . str_replace("\n", $indent, $section['content'])) . "\n";
            }
        }
        $formattedMessage = rtrim($formattedMessage) . "\n";
        $path = _PS_ROOT_DIR_ . '/log/collect_' . date('Ymd') . '.log';
        file_put_contents($path, $formattedMessage, FILE_APPEND);
    }

    /**
     * @param string $uid
     * @return int
     * @throws PrestaShopException
     */
    protected function getErrorLogId(string $uid)
    {
        $conn = Db::getInstance();
        return (int)$conn->getValue((new DbQuery())
            ->select('id_collectlogs_logs')
            ->from('collectlogs_logs')
            ->where('uid = \'' .pSQL($uid) . '\'')
        );
    }

    /**
     * @param string $uid
     * @param $type
     * @param $file
     * @param int $line
     * @param $realFile
     * @param int $realLine
     * @param string $genericMessage
     * @param $message
     * @param array $extra
     * @return int
     * @throws PrestaShopException
     */
    protected function insertErrorToDb(string $uid, $type, $file, int $line, $realFile, int $realLine, string $genericMessage, $message, $extra)
    {
        $conn = Db::getInstance();
        $severity = 1;
        if (isset(static::SEVERITIES[$type])) {
            $severity = static::SEVERITIES[$type];
        }
        if ($conn->insert('collectlogs_logs', [
            'uid' => pSQL($uid),
            'date_add' => date('Y-m-d H:i:s'),
            'type' => pSQL($type),
            'severity' => (int)$severity,
            'file' => pSQL($file),
            'line' => (int)$line,
            'real_file' => pSQL($realFile),
            'real_line' => (int)$realLine,
            'generic_message' => pSQL($genericMessage),
            'sample_message' => pSQL($message)
        ])) {
            $errorId = (int)$conn->Insert_ID();
            foreach ($extra as $section) {
                $conn->insert('collectlogs_extra', [
                    'id_collectlogs_logs' => $errorId,
                    'label' => pSQL($section['label']),
                    'content' => pSQL($section['content'])
                ]);
            }
            $this->markErrorSeen($errorId);
        }
        return 0;
    }

    /**
     * @param int $errorId
     * @return void
     * @throws PrestaShopException
     */
    protected function markErrorSeen(int $errorId)
    {
        $conn = Db::getInstance();
        $dimension = date('Y-m-d');
        $sql = "
            INSERT INTO " . _DB_PREFIX_ . "collectlogs_stats(id_collectlogs_logs, dimension, `count`)
            VALUES($errorId, '$dimension', 1)
            ON DUPLICATE KEY UPDATE `count` = `count` + 1";
        $conn->execute($sql);
    }

    /**
     * @return array
     * @throws PrestaShopException
     */
    protected function getMessageConvertors()
    {
        if (is_null($this->messageConvertRegexp)) {
            $conn = Db::getInstance();
            $this->messageConvertRegexp = [];
            $rows = $conn->getArray((new DbQuery())
                ->select('`search` as s, `replace` as r' )
                ->from('collectlogs_convert_message')
                ->orderBy('id_collectlogs_convert_message')
            );
            foreach ($rows as $row) {
                $this->messageConvertRegexp[$row['s']] = $row['r'];
            }
        }
        return $this->messageConvertRegexp;
    }

    protected function getStackTrace()
    {
        $result = '';
        $stackTrace = debug_backtrace();
        if ($stackTrace) {
            $total = count($stackTrace) + 1;
            $separatorLen = strlen("$total") + 1;
            $cnt = 1;
            $found = false;
            $prev = [];
            foreach ($stackTrace as $trace) {
                $class = $trace['class'] ?? '';
                $type = $trace['type'] ?? '';
                $function = $trace['function'] ?? '';
                if (! $found) {
                    if ($class !== 'CollectLogsModule\CollectLogLogger' &&
                        $class !== 'Psr\Log\AbstractLogger' &&
                        $class !== 'Thirtybees\Core\Error\ErrorHandlerCore'
                    ) {
                        $found = true;
                        $separator = str_repeat(' ', $separatorLen - 1);
                        $result .= $this->getLocation($prev, 0, $separator) . "\n";
                    } else {
                        $prev = $trace;
                    }
                }
                if ($found) {
                    $len = strlen("$cnt");
                    $separator = str_repeat(' ', $separatorLen - $len);
                    $result .= $this->getLocation($trace, $cnt, $separator) . ': ';
                    $result .= $class . $type . $function . '(';
                    if (isset($trace['args']) && $trace['args']) {
                        $args = array_map(function ($param) {
                            return strtok(ErrorUtils::displayArgument($param), "\n");
                        }, $trace['args']);
                        $result .= implode(', ', $args);
                    }
                    $result .= ')';
                    $result .= "\n";
                    $cnt++;

                }
            }
        }
        return $result;
    }

    protected function getLocation($entry, $cnt, $separator)
    {
        $prefix = '#' . $cnt . $separator;
        if (isset($entry['file']) && isset($entry['line'])) {
            return $prefix . ErrorUtils::getRelativeFile($entry['file']) . '(' . $entry['line'] .')';
        } else {
            return $prefix . 'builtin';
        }
    }

    /**
     * @param array $extra
     * @return bool
     */
    protected function hasStackTrace($extra)
    {
        foreach ($extra as $section) {
            if (strtolower($section['label'] ?? '') === 'stacktrace') {
                return true;
            }
        }
        return false;

    }
}