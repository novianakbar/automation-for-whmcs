<b>{$lang['server_address']}:</b> <h2><a
href="https://{$params['serverhostname']}">{$params['serverhostname']}</a></h2>
<hr>
<h3>{$lang['info_1']}</h3>
<h4><b>{$lang['username']}: </b> {$params['username']}</h4>
<h4><b>Password: </b> {$params['password']}</h4>
<h4><b>{$lang['email']}:</b> {$info['ocs']['data']['email']}</h4>
<h4><b>{$lang['displayname']}:</b> {$info['ocs']['data']['displayname']}</h3>
<hr>
<h3>{$lang['use_of_resources_for_now']}</h3>
<b>{$lang['total']}:</b> {round($info['ocs']['data']['quota']['quota'] / '1000' / '1024'/ '1024')} Gb <b>|</b>
<b>{$lang['used']}:</b> {round($info['ocs']['data']['quota']['used'] / '1000' / '1024'/ '1024', 2)} Gb , {round('100' * $info['ocs']['data']['quota']['used'] /$info['ocs']['data']['quota']['quota'])}%
{if $params['configoption3'] eq 'YES'}
<br>
<br>
{$limits = explode(",", $params['configoption4'])}
<form method="post" action="clientarea.php?action=productdetails&id={$params['serviceid']}">
<select id="limit" name="limit" class="dropdown">
{foreach from=$limits item=$limit}
    <option value="{$limit}">{$limit} GB</option>
{/foreach}
</select>
<input type="hidden" name="id" value="{$params['serviceid']}" />
<input type="hidden" name="modop" value="custom" />
<input type="hidden" name="a" value="changeLimit" />
<input type="submit" class="button" value="{$lang['change_limit']}" />
</form>
{/if}
<hr>
