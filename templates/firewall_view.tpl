{include file="$template/includes/tablelist.tpl" tableName="FirewallList" SortColumns="0"}
{include file="$template/includes/tablelist.tpl" tableName="RatelimitersList" SortColumns="0"}
{include file="$template/includes/tablelist.tpl" tableName="FiltersList" SortColumns="0"}
{include file="$template/includes/tablelist.tpl" tableName="AttackList" SortColumns="0"}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-sweetalert/1.0.1/sweetalert.min.css" integrity="sha512-hwwdtOTYkQwW2sedIsbuP1h0mWeJe/hFOfsvNKpRB3CkRxq8EW7QMheec1Sgd8prYxGm1OM9OZcGW7/GUud5Fw==" crossorigin="anonymous" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-sweetalert/1.0.1/sweetalert.min.js" integrity="sha512-MqEDqB7me8klOYxXXQlB4LaNf9V9S0+sG1i8LtPOYmHqICuEZ9ZLbyV3qIfADg2UJcLyCm4fawNiFvnYbcBJ1w==" crossorigin="anonymous"></script>
<link rel="stylesheet" type="text/css" href="modules/servers/dedicated/assets/bootstrap-select.min.css">
<link rel="stylesheet" href="modules/servers/dedicated/assets/bootstrap-editable.css">
<script type="text/javascript" src="modules/servers/dedicated/assets/bootstrap-editable.min.js" defer></script>
<script type="text/javascript" src="modules/servers/dedicated/assets/bootstrap-select.min.js"></script>
<script type="text/javascript" src="modules/servers/dedicated/assets/gaugeMeter.min.js"></script>
<script type="text/javascript" src="modules/servers/dedicated/assets/bootstrap-confirm.js" defer></script>



<style type="text/css">
	.sa-icon {
		margin-bottom: 20px;
	}
    #filterDescription {
        margin-top: 20px;
    }
</style>
<script type="text/javascript" src="templates/{$template}/js/firewall.js"></script>
<script type="text/javascript">
	var firewall = undefined;
	$( document ).ready(function() {

		firewall = new firewallManager('{$ip}')

    });
</script>

 <div class="panel panel-nav-tabs panel-default card">
            <div class="panel-heading card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="active nav-item"><a class='nav-link active' href="#rules" data-toggle="tab">Rules</a></li>
                    <li class='nav-item'><a class='nav-link' href="#ratelimiters" data-toggle="tab">Ratelimiters</a></li>
	            <li class='nav-item'><a class='nav-link' href="#filters" data-toggle="tab">Filters</a></li>
        	    <li class='nav-item'><a class='nav-link' href="#attacks" data-toggle="tab">Attack History</a></li>

		</ul>
            </div>
            
            <div class="panel-body card-body panel-body-table">
                <div class="tab-content">

            <div class="tab-pane active" id="rules">
                    
                <div class="table-container clearfix tableFirewallList">
                    <button type="button" class="btn btn-primary btn-block btn-lg disabled" style="margin-bottom: 10px;" disabled="true" onclick="firewall.showNewRule()">New Rule</button>

                    <table id="tableFirewallList" class="table table-list hidden">
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

                        </tbody>
                    </table>
                   </div>
            </div>


            <div class="tab-pane" id="ratelimiters">
                <div class="table-container clearfix tableRatelimitersList">
                    <button type="button" class="btn btn-primary btn-block btn-lg disabled" disabled="true" style="margin-bottom: 10px;" onclick="firewall.showNewRatelimiter()">New Ratelimiter</button>

                    <table id="tableRatelimitersList" class="table table-list hidden">
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
                    </table>                </div>
            </div>

            <div class="tab-pane" id="filters">
                <div class="alert alert-info">
                    Make sure you make a <b>whitelist</b> rule in rules for the ports you define in your filter or it will not <b>work</b> correctly. 
                </div>
                <div class="table-container clearfix tableFiltersList">
                    <button type="button" class="btn btn-primary btn-block btn-lg disabled" style="margin-bottom: 10px;" disabled="true" onclick="firewall.showNewFilter(this)">New Filter</button>

                    <table id="tableFiltersList" class="table table-list hidden">
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
                    </table>
                       </div> 
            </div>
            <div class="tab-pane" id="attacks">
                <div class="alert alert-info">
                    This is the last 30 days of attack history for this IP address.
                </div>
                <div class="table-container clearfix tableAttackList">
                    <table id="tableAttackList" class="table table-list hidden">
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
                    </table>
                      </div> 
            </div>
            
        </div>
    </div>
</div>




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
      <form role="form" method="POST" action="" id="newRuleForm">
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
               <div id="filterDescription"></div>
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

<script>

$('#toggleSourcePort').change(function() {
    if(this.checked) {
        $('#src_port').fadeIn();    
    } else {
        $('#src_port').fadeOut();
    }
});

</script>