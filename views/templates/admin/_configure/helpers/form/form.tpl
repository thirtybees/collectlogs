{**
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
 *}
{extends file="helpers/form/form.tpl"}
{block name="field"}
    {if $input.type == 'errors_table'}
        {if $input.errorTypes}
            <div class="col-lg-9 col-lg-offset-3">
                <table class="table table-responsive" style="width:auto;min-width:50%">
                    <thead>
                    <th>{l s='Error type' mod='collectlogs'}</th>
                    <th>{l s='Count' mod='collectlogs'}</th>
                    </thead>
                    <tbody>
                    {foreach $input.errorTypes as $errorType}
                        <tr>
                            <td><span class="badge {$errorType.badge}">{$errorType.type}</span></td>
                            <td><a href="{$errorType.link}">{$errorType.cnt}</a></td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
                <div style="padding-top: 1em">
                    <h4>{l s='Error logs deletion' mod='collectlogs'}</h4>
                    <span style="padding-right: 1em">
                    {l s='Delete error logs that we haven\'t seen for [1][/1] days' mod='collectlogs' tags=['<input type="number" name="OLDER_THAN_DAYS" value="30">']}
                </span>
                    <button
                            class="btn btn-primary"
                            name="ACTION_DELETE_OLDER_THAN_DAYS"
                            onclick="return confirm('{l s='Are you sure?'|escape:'html':'UTF-8' mod='collectlogs'}');"
                    >{l s='Delete' mod='collectlogs'}</button>
                </div>
            </div>
        {else}
            <div class="help-block" style="width:100%;text-align: center;font-size:2em;padding:1em;">
                {l s='No errors collected' mod='collectlogs'}
            </div>
        {/if}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
