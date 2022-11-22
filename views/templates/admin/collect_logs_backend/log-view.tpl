<div class="col-lg-12">
    <div class="panel">
        <h3>{$type}</h3>
        <div class="panel-body">
            <p>
                <h4>{l s='Message:' mod='collectlogs'}</h4>
                <code>{$generic_message|escape:'html'}</code>
                {if $generic_message != $sample_message}
                    <br />
                    <code>{$sample_message|escape:'html'}</code>
                {/if}
            </p>

            <br />

            <p>
                <h4>{l s='Location:' mod='collectlogs'}</h4>
                {if $real_file}
                    <code>{$file}</code>
                    <br />
                    <code>{$real_file}</code>&nbsp;line&nbsp;<code>{$real_line}</code>
                {else}
                    <code>{$file}</code>&nbsp;line&nbsp;<code>{$line}</code>
                {/if}
            </p>
        </div>
    </div>
    {foreach $extraSections as $section}
        <div class="panel">
            <h3>{$section.label|escape:'html'}</h3>
            <div class="panel-body">
                <pre><code>{$section.content|escape:'html'}</code></pre>
            </div>
        </div>

    {/foreach}
</div>