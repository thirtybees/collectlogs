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

use CollectLogsModule\CollectLogLogger;
use Thirtybees\Core\DependencyInjection\ServiceLocator;

/**
 * Class CollectLogs
 */
class CollectLogs extends Module
{

    // configuration keys
    const CRON_SECRET = 'COLLECTLOGS_CRON_SECRET';
    const LAST_CRON_EXECUTION = 'COLLECTLOGS_CRON_TS';
    const SEND_NEW_ERRORS_EMAIL = 'COLLECTLOGS_SEND_NEW_ERRORS_EMAIL';
    const NEW_ERRORS_EMAIL_ADDRESSES = 'COLLECTLOGS_NEW_ERRORS_EMAIL';

    public function __construct()
    {
        $this->name = 'collectlogs';
        $this->tab = 'administaration';
        $this->version = '1.0.0';
        $this->author = 'thirty bees';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();
        $this->displayName = $this->l('Collect PHP Logs');
        $this->description = $this->l('Debugging module that collects PHP logs');
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6.999'];
        $this->tb_min_version = '1.4.0';
        $this->controllers = ['front'];
    }

    /**
     * @param bool $createTables
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install($createTables = true)
    {
        if (! $this->systemSupportsLogger()) {
            $this->_errors[] = Tools::displayError('Your version of thirty bees does not support logger registration. Please update to never version of thirty bees');
            return false;
        }
        return (
            parent::install() &&
            $this->installTab() &&
            $this->installDb($createTables) &&
            $this->registerHook('actionRegisterErrorHandlers')
        );
    }

    /**
     * @param bool $dropTables
     * @return bool
     * @throws PrestaShopException
     */
    public function uninstall($dropTables = true)
    {
        return (
            $this->removeTab() &&
            $this->uninstallDb($dropTables) &&
            parent::uninstall()
        );
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function reset()
    {
        return (
            $this->uninstall(false) &&
            $this->install(false)
        );
    }

    /**
     * @param bool $create
     * @return bool
     * @throws PrestaShopException
     */
    private function installDb($create)
    {
        if (! $create) {
            return true;
        }
        return $this->executeSqlScript('install');
    }

    /**
     * @param bool $drop
     * @return bool
     * @throws PrestaShopException
     */
    private function uninstallDb($drop)
    {
        if (! $drop) {
            return true;
        }
        return $this->executeSqlScript('uninstall', false);
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function installTab() {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminCollectLogsBackend';
        $tab->module = $this->name;
        $tab->id_parent = $this->getTabParent();
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $this->l('Error logs');
        }
        return $tab->add();
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function removeTab() {
        $ret = true;
        foreach (Tab::getCollectionFromModule($this->name) as $tab) {
            $ret = $tab->delete() && $ret;
        }
        return $ret;
    }

    /**
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function getTabParent() {
        $id = Tab::getIdFromClassName('AdminTools');
        if ($id !== false) {
            return $id;
        }
        return 0;
    }

    /**
     * @param $script
     * @param bool $check
     * @return bool
     * @throws PrestaShopException
     */
    public function executeSqlScript($script, $check = true)
    {
        $file = dirname(__FILE__) . '/sql/' . $script . '.sql';
        if (!file_exists($file)) {
            PrestaShopLogger::addLog($this->name . ": sql script $file not found");
            return false;
        }
        $sql = file_get_contents($file);
        if (!$sql) {
            return false;
        }
        $sql = str_replace(['PREFIX_', 'ENGINE_TYPE', 'CHARSET_TYPE', 'COLLATE_TYPE'], [_DB_PREFIX_, _MYSQL_ENGINE_, 'utf8mb4', 'utf8mb4_unicode_ci'], $sql);
        $sql = preg_split("/;\s*[\r\n]+/", $sql);
        foreach ($sql as $statement) {
            $stmt = trim($statement);
            if ($stmt) {
                try {
                    if (!Db::getInstance()->execute($stmt)) {
                        PrestaShopLogger::addLog($this->name . ": sql script $script: $stmt: error");
                        if ($check) {
                            return false;
                        }
                    }
                } catch (Exception $e) {
                    PrestaShopLogger::addLog($this->name . ": sql script $script: $stmt: exception: $e");
                    if ($check) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * @return void
     */
    public function hookActionRegisterErrorHandlers()
    {
        if ($this->systemSupportsLogger()) {
            require_once __DIR__ . '/classes/CollectorLogger.php';
            ServiceLocator::getInstance()->getErrorHandler()->addLogger(new CollectLogLogger());
        }
    }

    /**
     * @return bool
     */
    protected function systemSupportsLogger()
    {
        if (class_exists('Thirtybees\Core\DependencyInjection\ServiceLocator')) {
            $serviceLocator = ServiceLocator::getInstance();
            return method_exists($serviceLocator, 'getErrorHandler');
        }
        return false;
    }

    /**
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getContent()
    {
        if (Tools::isSubmit('submitSettings')) {
            Configuration::updateGlobalValue(static::SEND_NEW_ERRORS_EMAIL, (int)Tools::getValue(static::SEND_NEW_ERRORS_EMAIL));
            $emailAddresses = static::extractValidEmails(Tools::getValue(static::NEW_ERRORS_EMAIL_ADDRESSES));
            Configuration::updateGlobalValue(static::NEW_ERRORS_EMAIL_ADDRESSES, implode("\n", $emailAddresses));
        }
        $cronUrl = $this->context->link->getModuleLink($this->name, 'cron', [
            'secure_key' => $this->getCronSecret()
        ]);

        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Send email with new errors summary'),
                        'desc' => $this->l('When enabled, cron job will send email with new detected errors'),
                        'name' => static::SEND_NEW_ERRORS_EMAIL,
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ],
                        ],
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Email addressess'),
                        'rows' => 3,
                        'name' => static::NEW_ERRORS_EMAIL_ADDRESSES,
                        'desc' => $this->l('Email addresses of people that should receive email with new errors. Enter each address on separate line!'),
                    ],
                    [
                        'type' => 'html',
                        'label' => $this->l('Cron URL'),
                        'name' => 'COLLECTLOGS_CRON_URL',
                        'html_content' => "<code style='display:block;margin-top:7px'>$cronUrl</code>",
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        /** @var AdminController $controller */
        $controller = $this->context->controller;

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSettings';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $controller->getLanguages();
        $helper->fields_value = [
            static::SEND_NEW_ERRORS_EMAIL => Configuration::getGlobalValue(static::SEND_NEW_ERRORS_EMAIL),
            static::NEW_ERRORS_EMAIL_ADDRESSES => Configuration::getGlobalValue(static::NEW_ERRORS_EMAIL_ADDRESSES),
        ];

        return $helper->generateForm([
            $fieldsForm
        ]);
    }

    /**
     * @return void
     *
     * @throws PrestaShopException
     */
    public function processCron()
    {
        if (! headers_sent()) {
            header('Content-Type: text/plain');
        }
        if (! Configuration::getGlobalValue(static::SEND_NEW_ERRORS_EMAIL)) {
            echo "Sending emails with new errors is disabled in module settings, exiting...\n";
            return;
        }
        $emailAddresses = static::extractValidEmails(Configuration::getGlobalValue(static::NEW_ERRORS_EMAIL_ADDRESSES));
        if (! $emailAddresses) {
            echo "No email address specified, exiting...\n";
            return;
        }
        $lastExec = (int)Configuration::getGlobalValue(static::LAST_CRON_EXECUTION);
        Configuration::updateGlobalValue(static::LAST_CRON_EXECUTION, time() - 1);
        $from = date('Y-m-d H:i:s', $lastExec);
        echo "Retrieving new errors since " . $from . "\n";

        $conn = Db::getInstance();
        $rows = $conn->getArray((new DbQuery())
            ->select('*')
            ->from('collectlogs_logs')
            ->where('date_add >= \'' .$from . '\'')
            ->orderBy('id_collectlogs_logs')
        );
        if (! $rows) {
            echo "No new errors, exiting...\n";
            return;
        }

        echo "Found " . count($rows) ." new errors:\n";

        $errorsTxt = "";
        $errorsHtml = "";
        foreach ($rows as $row) {
            $id = (int)$row['id_collectlogs_logs'];
            $dateAdd = $row['date_add'];
            $type = $row['type'];
            $message = $row['sample_message'];
            $file = $row['file'];
            $realFile = $row['real_file'];
            $realLine = $row['real_line'];
            $line = $row['line'];
            $seen = (int)$conn->getValue((new DbQuery())
                ->select("SUM(`count`) as cnt")
                ->from('collectlogs_stats')
                ->where("id_collectlogs_logs = $id")
            );

            $errorsHtmlDescription = "<div>";
            $errorsHtmlDescription .= "<h3>[$type] $message</h3>";
            $errorTxtDescription = "  - [$type] ";
            $errorTxtDescription .= $message;
            $errorTxtDescription .= "\n    in " . $row['file'];
            $errorsHtmlDescription .= "<div>in file <code>$file</code>";
            if ($realFile) {
                $errorTxtDescription .= " (" . $realFile . ':' . $realLine . ")";
                $errorsHtmlDescription .= " <span>(<code>$realFile:$realLine</code>)</span>";
            } else {
                $errorTxtDescription .= ":" . $line;
                $errorsHtmlDescription .= "<code>:$line</code>";
            }
            $errorsHtmlDescription .= "</div>";
            $errorsHtmlDescription .= "<div>Seen <b>$seen</b> times since $dateAdd</div>";
            $errorTxtDescription .= "\n    Seen $seen times since $dateAdd";

            $extras = $conn->getArray((new DbQuery())
                ->select('*')
                ->from('collectlogs_extra')
                ->where('id_collectlogs_logs = ' . $id)
            );

            foreach ($extras as $section) {
                $errorTxtDescription .= "\n    " . $section['label'];
                $errorTxtDescription .= "\n    " . trim(str_replace("\n", "\n    ", $section['content']));
                $errorsHtmlDescription .= "<div><h5>".$section['label']."</h5><code><pre>".$section['content']."</pre></code></div>";
            }
            $errorTxtDescription .= "\n";
            $errorsHtmlDescription .= "</div>";
            $errorsTxt .= $errorTxtDescription;
            $errorsHtml .= $errorsHtmlDescription;
        }

        echo $errorsTxt . "\n";

        foreach ($emailAddresses as $emailAddress) {
            Mail::send(
                Configuration::get('PS_LANG_DEFAULT'),
                'collectlogs-errors',
                Mail::l('New errors detected'),
                [
                    '{errorsTxt}' => $errorsTxt,
                    '{errorsHtml}' => $errorsHtml,
                ],
                $emailAddress,
                null,
                null,
                null,
                null,
                null,
                dirname(__FILE__) . '/mails/'
            );
        }
    }

    /**
     * @param string $string
     * @return array
     */
    protected static function extractValidEmails($string)
    {
        if (!is_string($string) || !$string) {
            return [];
        }
        return array_filter(array_map('trim', explode("\n", $string)), function($addr) {
            if (! $addr) {
                return false;
            }
            return Validate::isEmail($addr);
        });
    }

    /**
     * @return string
     * @throws PrestaShopException
     */
    public function getCronSecret()
    {
        $value = Configuration::getGlobalValue(static::CRON_SECRET);
        if (! $value) {
            $value = Tools::passwdGen(32);
            Configuration::updateGlobalValue(static::CRON_SECRET, $value);
        }
        return $value;
    }
}
