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

class AdminCollectLogsBackendController extends ModuleAdminController
{
    /** @var CollectLogs */
    public $module;

    const SEVERITIES = [
        1 => 'badge-info',
        2 => 'badge-warning',
        3 => 'badge-danger',
        4 => 'badge-critical',
    ];

    /**
     * AdminCollectLogsBackendController constructor.
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->table = 'collectlogs_logs';
        $this->bootstrap = true;
        $this->identifier = 'id_collectlogs_logs';

        parent::__construct();

        $this->_select .= implode(",\n", [
            'extra.location',
            'extra.total',
            'extra.last_seen',
        ]);
        $this->_select .= ',';
        $this->_select .= ',';
        $this->_defaultOrderBy = 'a.date_add';
        $this->_defaultOrderWay = 'DESC';
        $join = (new DbQuery())
            ->select('l.id_collectlogs_logs AS id_collectlogs_logs')
            ->select('CONCAT(l.file, IF(l.line, CONCAT(":", l.line), "")) AS location')
            ->select('SUM(s.count) AS total')
            ->select('DATEDIFF(NOW(), MAX(s.`dimension`)) AS last_seen')
            ->from('collectlogs_logs', 'l')
            ->innerJoin('collectlogs_stats', 's', '(s.id_collectlogs_logs = l.id_collectlogs_logs)')
            ->groupBy('id_collectlogs_logs');
        $this->_join .= " INNER JOIN ($join) AS extra ON (extra.id_collectlogs_logs = a.id_collectlogs_logs)";

        $this->actions = ['view', 'delete'];
        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash',
            ]
        ];

        $this->fields_list = [
            'type' => [
                'title' => $this->l('Type'),
                'type' => 'text',
                'order_key' => 'a!severity',
                'callback_object' => $this,
                'callback' => 'displayType',
            ],
            'generic_message' => [
                'title' => $this->l('Message'),
                'type' => 'text',
            ],
            'location' => [
                'title' => $this->l('File'),
                'type' => 'text',
            ],
            'total' => [
                'title' => $this->l('Occurrences'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'last_seen' => [
                'title' => $this->l('Last seen'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'callback_object' => $this,
                'callback' => 'displayLastSeen',
            ],
            'date_add' => [
                'title' => $this->l('Date'),
                'align' => 'right',
                'type' => 'datetime',
            ],
        ];
    }

    /**
     * Process delete single log entry
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processDelete()
    {
        $id = (int)Tools::getValue($this->identifier);
        $result = $this->deleteLog($id);
        if ($result) {
            $this->redirect_after = static::$currentIndex . '&conf=1&token=' . $this->token;
        } else {
            $this->errors[] = Tools::displayError('Failed to delete log');
        }
        return $result;
    }

    /**
     * Process bulk delete action
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function processBulkDelete()
    {
        $result = true;
        if (is_array($this->boxes) && !empty($this->boxes)) {
            foreach ($this->boxes as $id) {
                $result = $this->deleteLog($id) && $result;
            }
        }
        return $result;
    }

    /**
     * Delete single log entry
     *
     * @param $id
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function deleteLog($id)
    {
        $id = (int)$id;
        $conn = Db::getInstance();
        return (
            $conn->delete('collectlogs_stats', 'id_collectlogs_logs = ' . $id) &&
            $conn->delete('collectlogs_extra', 'id_collectlogs_logs = ' . $id) &&
            $conn->delete('collectlogs_logs', 'id_collectlogs_logs = ' . $id)
        );
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderView()
    {
        $id = (int)Tools::getValue($this->identifier);
        $conn = Db::getInstance();
        $log = $conn->getRow((new DbQuery())
            ->select('*')
            ->from('collectlogs_logs')
            ->where('id_collectlogs_logs = ' . $id)
        );
        if (!$log) {
            $this->errors[] = $this->l('Object not found');
            return '';
        }
        $extras = $conn->getArray((new DbQuery())
            ->select('*')
            ->from('collectlogs_extra')
            ->where('id_collectlogs_logs = ' . $id)
        );
        $template = $this->createTemplate('log-view.tpl');
        $template->assign($log);
        $template->assign('extraSections', $extras);
        return $template->fetch();
    }

    /**
     * @param $value
     * @param $row
     * @return string
     */
    public function displayType($value, $row)
    {
        $class = static::SEVERITIES[$row['severity']] ?? 'badge-info';
        return '<span class="badge ' . $class . '">' . $value . '</span>';
    }

    public function initToolbar()
    {
        $this->page_header_toolbar_btn['settings'] = [
            'icon' => 'process-icon-cogs',
            'href' => $this->context->link->getAdminLink('AdminModules', true, [
                'configure' => $this->module->name,
                'module_name' => $this->module->name
            ]),
            'desc' => $this->l('Settings'),
        ];
    }


    /**
     * @param int $value
     * @return string
     */
    public function displayLastSeen($value)
    {
        $value = (int)$value;
        if ($value === 0) {
            return '<span class="badge badge-critical">' . $this->l('Today') . '</span>';
        }
        if ($value === 1) {
            return '<span class="badge badge-danger">' . $this->l('Yesterday') . '</span>';
        }
        if ($value < 7) {
            return '<span class="badge badge-warning">' . sprintf($this->l('%s days ago'), $value) . '</span>';
        }
        if ($value < 30) {
            return '<span class="badge badge-info">' . sprintf($this->l('%s days ago'), $value) . '</span>';
        }
        return '<span class="badge badge-success">' . sprintf($this->l('%s days ago'), $value) . '</span>';
    }

}
