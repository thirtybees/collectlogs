<?php

use CollectLogsModule\Settings;
use CollectLogsModule\TransformMessageImpl;

/**
 * @param CollectLogs $module
 *
 * @return bool
 * @throws PrestaShopException
 */
function upgrade_module_1_3_0($module)
{
    $module->executeSqlScript('version_1_3_0');

    $db = Db::getInstance();
    $db->update('collectlogs_convert_message', ['id_remote' => 9999999]);

    $module->getTransformMessage()->synchronize();

    return true;
}