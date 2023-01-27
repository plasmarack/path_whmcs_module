<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-sweetalert/1.0.1/sweetalert.min.css" integrity="sha512-hwwdtOTYkQwW2sedIsbuP1h0mWeJe/hFOfsvNKpRB3CkRxq8EW7QMheec1Sgd8prYxGm1OM9OZcGW7/GUud5Fw==" crossorigin="anonymous" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-sweetalert/1.0.1/sweetalert.min.js" integrity="sha512-MqEDqB7me8klOYxXXQlB4LaNf9V9S0+sG1i8LtPOYmHqICuEZ9ZLbyV3qIfADg2UJcLyCm4fawNiFvnYbcBJ1w==" crossorigin="anonymous"></script>
<link rel="stylesheet" type="text/css" href="modules/servers/dedicated/assets/bootstrap-select.min.css">
<link rel="stylesheet" href="modules/servers/dedicated/assets/bootstrap-editable.css">
<script type="text/javascript" src="modules/servers/dedicated/assets/bootstrap-editable.min.js" defer></script>
<script type="text/javascript" src="modules/servers/dedicated/assets/bootstrap-select.min.js"></script>
<script type="text/javascript" src="modules/servers/dedicated/assets/gaugeMeter.min.js"></script>
<script type="text/javascript" src="modules/servers/dedicated/assets/bootstrap-confirm.js" defer></script>

<style>

.navigation-button:hover {
    background-color: rgba(0, 0, 0, .05);
}

.navigation-button.active {
    background-color: rgba(0, 0, 0, .075);
}

</style>

<div style="display: flex; flex-direction: row; gap: .75rem; padding: 1rem 0rem;">
    <a class="btn btn-primary{if $page eq "rules"} active {/if}" href="index.php?m=pathfirewall&server={$server}&page=rules">Rules</a>
    <a class="btn btn-primary{if $page eq "ratelimiters"} active {/if}" href="index.php?m=pathfirewall&server={$server}&page=ratelimiters">Ratelimiters</a>
    <a class="btn btn-primary{if $page eq "filters"} active {/if}" href="index.php?m=pathfirewall&server={$server}&page=filters">Filters</a>
    <a class="btn btn-primary{if $page eq "attack_history"} active {/if}"href="index.php?m=pathfirewall&server={$server}&page=attack_history">Attack History</a>
</div>

<hr>

{if $page eq "rules"}
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalNewRule" style="width:100%; display:block; font-size:20px;">New rule</button>
{/if}
{if $page eq "filters"}
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalNewFilter" style="width:100%; display:block; font-size:20px;">New filter</button>
{/if}
{if $page eq "ratelimiters"}
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalNewRatelimiter" style="width:100%; display:block; font-size:20px;">New ratelimiter</button>
{/if}

<br>

{if $page eq 'filters'}
<div class="modal fade" id="modalNewFilter" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
aria-hidden="true">
<div class="modal-dialog">
  <div class="modal-content">

    <!-- Modal Header -->
    <div class="modal-header">
      <h4 class="modal-title">Create Filter</h4>
      <button type="button" class="close" data-dismiss="modal">&times;</button>
  </div>

  <!-- Modal body -->
  <div class="modal-body">
       <div class="alert alert-info">
        You are currently using: <b class="filterCount">1</b> / <b class="maxFilters">5</b> filters for this IP
      </div>
      <form role="form" method="POST" action="" id="newFilterForm">
        <input type="hidden" name="addr" value="{$ip}">

        <div class="form-group" id="filterOption">
            <label class="control-label">Filter</label>
            <div>
               <select class="form-control input-lg" name="name" id="filterSelect" required="">

               </select>
           </div>
        </div>
        <div class="filterOptions"></div>
    
    </form>
</div>

<!-- Modal footer -->
<div class="modal-footer">
  <button type="button" class="btn btn-primary" onclick="$('#newFilterForm').submit()">Create</button>
  <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
</div>

</div>
</div>
</div>
{/if}

{if $page eq 'rules'}
<div class="modal fade" id="modalNewRule" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
aria-hidden="true">
<div class="modal-dialog">
  <div class="modal-content">

    <!-- Modal Header -->
    <div class="modal-header">
      <h4 class="modal-title">Create Rule</h4>
      <button type="button" class="close" data-dismiss="modal">&times;</button>
  </div>

  <!-- Modal body -->
  <div class="modal-body">
      <div class="alert alert-info">
        You are currently using: <b class="ruleCount">1</b> / <b class="maxRules">20</b> rules for this IP
      </div>
      <div class="alert alert-warning errorRule" style="display: none;">
        <b>Error: </b><span id="errorRuleMsg"></span>
      </div>
      <form role="form" method="POST" action id="newRuleForm">
        <input type="hidden" name="destination" value="{$ip}/32">
    <div class="form-group">
        <label class="control-label">Protocol</label>
        <div>
          <select class="form-control input-lg" id="protocolSelect" name="protocol">
            <option value="">All</option>
          </select>
       </div>
   </div>       
   <div class="form-group">
    <label class="control-label">Source IP</label>
    <div>
        {literal}
        <input type="text" class="form-control input-lg" name="source" value="0.0.0.0/0" minlength="7" maxlength="20" size="20">
        {/literal}
    </div>
  </div>
  <div class="form-group" id="portOptionsDest"  style="display:none;">
      <label class="control-label">Destination Port</label>
      <div>
          <input type="number" class="form-control input-lg" min="1" max="65535" placeholder="1-65535" name="dst_port">
      </div>
  </div>
  <div class="form-group" id="portOptionsSrc" style="display:none;">
      <div style="display: flex; flex-direction: row; justify-items: center; align-items: center;">
        <label class="control-label" style="flex: 1">Source Port</label>
        <input id="toggleSourcePort" type="checkbox" style="height: 12px;">
        <label style="padding: 0; margin: 0;">Restrict source port?</label>
      </div>
      <div>
          <input style="display: none;" type="number" id="src_port" class="form-control input-lg"  min="1" max="65535" placeholder="1-65535" name="src_port">
      </div>
  </div>
  <div class="form-group">
      <label class="control-label">Type</label>
      <div>
        <select class="form-control input-lg" name="type" id="typeSelect">
          <option value="whitelist" selected="">Whitelist</option>
          <option value="blacklist">Blacklist</option>
          <option value="ratelimit">Ratelimit</option>
        </select>
      </div>
  </div>    
  <div class="form-group" id="rateLimiterOption" style="display:none;">
      <label class="control-label">Ratelimiter</label>
      <div>
         <select class="form-control input-lg" name="rate_limiter_id" id="rateLimitSelect">

         </select>
     </div>
  </div>
  <div class="form-group">
      <label class="control-label">Comment</label>
      <div>
          <input type="text" class="form-control input-lg" name="comment" placeholder="Comment" required="">
      </div>
  </div>

</form>
</div>

<!-- Modal footer -->
<div class="modal-footer">
  <button type="button" class="btn btn-primary" onclick="$('#newRuleForm').submit()">Create</button>
  <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
</div>

</div>
</div>
</div>

{/if}

{if $page eq 'ratelimiters'}
<div class="modal fade" id="modalNewRatelimiter" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
aria-hidden="true">
<div class="modal-dialog">
  <div class="modal-content">

    <!-- Modal Header -->
    <div class="modal-header">
      <h4 class="modal-title">Create Ratelimiter</h4>
      <button type="button" class="close" data-dismiss="modal">&times;</button>
  </div>

  <!-- Modal body -->
  <div class="modal-body">
      <form role="form" method="POST" action="" id="newRatelimiterForm">
        <input type="hidden" name="destination" value="{$ip}/32">

        <div class="form-group">
            <label class="control-label">Packets per second</label>
            <div>
                <input type="number" class="form-control input-lg"  min="1" value="1" name="packets_per_second" required="">
            </div>
        </div>
        <div class="form-group">
            <label class="control-label">Comment</label>
            <div>
                <input type="text" class="form-control input-lg" name="comment" placeholder="Comment" required="">
            </div>
        </div>

    </form>
</div>

<!-- Modal footer -->
<div class="modal-footer">
  <button type="button" class="btn btn-primary" onclick="$('#newRatelimiterForm').submit()">Create</button>
  <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
</div>

</div>
</div>
</div>
{/if}


<table id="{if $page eq 'rules'}tableFirewallList{/if}{if $page eq 'filters'}tableFiltersList{/if}{if $page eq 'attack_history'}tableAttackList{/if}{if $page eq 'ratelimiters'}tableRatelimitersList{/if}" class="table table-list nowrap">
    {if $page eq 'rules'}
        <thead>
            <tr>
                <th>ID</th>
                <th>Protocol</th>
                <th>Destination</th>
                <th>Dst Port</th>
                <th>Source</th>
                <th>Src Port</th>
                <th>Type</th>
                <th>Comment</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$rules item=$rule}
                <tr>
                    <td>{$rule['id']}</td>
                    <td>{if $rule['protocol']}{$rule['protocol']|upper}{else}ALL{/if}</td>
                    <td>{$rule['source']}</td>
                    <td>{if $rule['src_port']}{$rule['src_port']}{else}ANY{/if}</td>
                    <td>{$rule['destination']}</td>
                    <td>{if $rule['dst_port']}{$rule['dst_port']}{else}ANY{/if}</td>
                    <td>
                        {if $rule['whitelist']}
                            ALLOW{if $rule['priority']} WITH PRIORITY {/if}
                        {else}
                            DROP{if $rule['priority']} WITH PRIORITY {/if}
                        {/if}
                    </td>
                    <td>{$rule['comment']}</td>
                    <td><button type="button" class="btn btn-danger btn-xs" onclick="firewall.deleteRule(this, '{$rule['id']}')"><i class="fas fa-times"></i></button></td>
                </tr>
            {/foreach}
        </tbody>
    {/if}
    {if $page eq 'filters'}
        <thead>
            <tr>
                <th>ID</th>
                <th>Filter</th>
                <th>Attributes</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    {/if}
    {if $page eq 'attack_history'}
        <thead>
            <tr>
                <th>ID</th>
                <th>Reason</th>
                <th>Start</th>
                <th>End</th>
                <th>Peak PPS</th>
                <th>Peak BPS</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    {/if}
    {if $page eq 'ratelimiters'}
        <thead>
            <tr>
                <th>ID</th>
                <th>Packets per second</th>
                <th>Comment</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    {/if}
</table>
<div class="text-center" id="{if $page eq 'rules'}tableLoadingRules{/if}{if $page eq 'filters'}tableLoadingFilters{/if}{if $page eq 'attack_history'}tableLoadingAttack{/if}{if $page eq 'ratelimiters'}tableLoadingLimiters{/if}">
    <p><i class="fas fa-spinner fa-spin"></i> Loading...</p>
</div>

<script src="modules/addons/pathfirewall/templates/client/js/firewall.js"></script>

<script type="text/javascript">
	var firewall = undefined;
	$( document ).ready(function() {
		firewall = new firewallManager('{$ip}')
    });
</script>