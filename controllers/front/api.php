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

class CollectLogsApiModuleFrontController extends ModuleFrontController
{
    /**
     * @var CollectLogs
     */
    public $module;

    /**
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function init()
    {
        parent::init();

        $action = Tools::getValue('action');
        switch ($action) {
            case CollectLogs::ACTION_UNSUBSCRIBE:
                $this->unsubscribe();
                break;
            default:
                $this->displayError($this->l("Invalid action"));

        }
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    protected function unsubscribe()
    {
        $email = Tools::getValue('email');
        if (!Validate::isEmail($email)) {
            $this->displayError($this->l('Invalid email address'));
            return;
        }
        $settings = $this->module->getSettings();
        $secret = Tools::getValue('secret');
        if ($secret !== $settings->getUnsubscribeSecret($email)) {
            $this->displayError($this->l('Invalid secret'));
            return;
        }
        $addresses = $settings->getEmailAddresses();
        $newAddresses = array_diff($addresses, [ $email ]);
        if (count($addresses) !== count($newAddresses)) {
            $settings->setEmailAddresses($newAddresses);
        }
        $this->displayConfirmation(sprintf($this->l('Email address %s has been unsubscribed'), $email));
    }

    /**
     * @param string $message
     *
     * @return void
     * @throws PrestaShopException
     */
    protected function displayConfirmation($message)
    {
        $this->context->smarty->assign('message', $message);
        $this->setTemplate('confirmation.tpl');
    }

    /**
     * @param string $message
     *
     * @return void
     * @throws PrestaShopException
     */
    protected function displayError($message)
    {
        $this->context->smarty->assign('error', $message);
        $this->setTemplate('error.tpl');
    }

    /**
     * @param string $message
     *
     * @return string
     */
    public function l($message)
    {
        return $this->module->l($message);
    }

}
