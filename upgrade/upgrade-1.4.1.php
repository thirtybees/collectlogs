<?php

/**
 * @param CollectLogs $module
 *
 * @return bool
 * @throws PrestaShopException
 */
function upgrade_module_1_4_1($module)
{
    return $module->registerHook('actionLogCaughtException');
}
