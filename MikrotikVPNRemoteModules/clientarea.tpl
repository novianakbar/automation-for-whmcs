
<table style="width:100%" class="table table-responsive" >
<tbody style="width: 100%; display: table">
<tr>
<th>
<table style="width:90%" class="table table-striped">
<tr>
<td colspan="2">
{if $curl['name']}
<div class="alert alert-success text-center">ONLINE</div></td>
{else}
<div class="alert alert-danger text-center">OFFLINE</div></td>
{/if}
</tr>
<tr>
<td><b>Service:</b></td>
<td>
{if $curl['service']}{$curl['service']}{else}<img src="/assets/img/statusfailed.gif" alt="Online" width="16" height="16">{/if}
</td>
</tr>
<tr>
<td><b>Name:</b></td>
<td>
{if $curl['name']}{$curl['name']}{else}<img src="/assets/img/statusfailed.gif" alt="Online" width="16" height="16">{/if}
</td>
</tr>
<tr>
<td><b>Caller-id:</b></td>
<td>
{if $curl['caller-id']}{$curl['caller-id']}{else}<img src="/assets/img/statusfailed.gif" alt="Online" width="16" height="16">{/if}
</td>
</tr>
<tr>
<td><b>Address:</b></td>
<td>
{if $curl['address']}{$curl['address']}{else}<img src="/assets/img/statusfailed.gif" alt="Online" width="16" height="16">{/if}
</td>
</tr>
<tr>
<td><b>Uptime:</b></td>
<td>
{if $curl['uptime']}{$curl['uptime']}{else}<img src="/assets/img/statusfailed.gif" alt="Online" width="16" height="16">{/if}
</td>
</tr>
</table>
</th>
<th>
<b>{$lang['server_address']}:</b> <h2>{$params['serverhostname']}</h2>
<hr>
<h5>{$lang['info_1']}</h5>
<table style="width:90%" class="table">
    <tr>
        <td>{$lang['username']}:</td>
        <td>{$params['username']}</td>
    </tr>
        <td>{$lang['password']}:</td>
        <td>{$params['password']}</td>
</table>
<h3>List Port Remote</h3>
<table style="width:90%" class="table">
    <tr>
        <td>Port 22</td>
        <td>:</td>
        <td>{$params['serverhostname']}:{$port[0]}</td>
    </tr>
    <tr>
        <td>Port 8291</td>
        <td>:</td>
        <td>{$params['serverhostname']}:{$port[1]}</td>
    </tr>
    <tr>
        <td>Port 80</td>
        <td>:</td>
        <td>{$params['serverhostname']}:{$port[2]}</td>
    </tr>
    <tr>
        <td>Port 8728</td>
        <td>:</td>
        <td>{$params['serverhostname']}:{$port[3]}</td>
    </tr>
        
</table>
</th>
</tr>
</tbody>
</table>

