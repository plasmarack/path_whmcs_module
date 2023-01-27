<table id="serversTable" class="table table-list">
    <thead>
        <tr>
            <th>IP Address</td>
            <th>Related Service</th>
            <th>Last attack</td>
            <th>Action</td>
        </tr>
    </thead>
    <tbody>
        {foreach from=$servers item=$server}
            <tr>
                <td class="text-center">
                    {$server['ip']}
                </td>
                <td class="text-center">
                    <a href="clientarea.php?action=productdetails&id={$server['id']}">{$server['service']}</a>
                </td>
                <td class="text-center">
                    <span class="attack-address label label-pending lastattack" data-ip="{$server['ip']}">Loading...</span>
                </td>
                <td class="text-center">
                    <div style="display: flex; flex-direction: row; position: relative;">
                        <a style="position: absolute; display: none;" href="submitticket.php?step=2&deptid=1&subject=IP%20Blocked%20{$ip.ip}&message=Hello,%0A%0AMy%20IP%20{$ip.ip}%20is%20marked%20as%20blocked%20on%20the%20Firewall%20page.%0A%0AThis%20message%20was%20automatically%20generated%20from%20the%20firewall%20overview." title="This IP is blocked." id="ip-{$ip.ip}" class="btn btn-small btn-danger blocked-ip" data-ip="{$ip.ip}">!</a>
                        <a href="index.php?m=pathfirewall&server={$server['ip']}" class="btn btn-block btn-danger">
                            Manage Rules
                        </a>
                    </div>
                </td>
            </tr>
        {/foreach}
    </tbody>
</table>

<script type="text/javascript">
    function timeSince(date) {

      var seconds = Math.floor((new Date() - date) / 1000);

      var interval = seconds / 31536000;

      if (interval > 1) {
        return Math.floor(interval) + " years";
    }
    interval = seconds / 2592000;
    if (interval > 1) {
        return Math.floor(interval) + " months";
    }
    interval = seconds / 86400;
    if (interval > 1) {
        return Math.floor(interval) + " days";
    }
    interval = seconds / 3600;
    if (interval > 1) {
        return Math.floor(interval) + " hours";
    }
    interval = seconds / 60;
    if (interval > 1) {
        return Math.floor(interval) + " minutes";
    }
    return Math.floor(seconds) + " seconds";
}

jQuery(document).ready( function ()
{
    var table = jQuery('#tableFirewallList').removeClass('hidden').DataTable();

    table.draw();
    jQuery('#tableLoading').css('display', 'none');

    table.on('draw', () => getIPBlockStatus());

    let blockedIPs = [];
    
    new Promise(resolve => {
        $.getJSON(`modules/addons/pathfirewall/lib/Rest/api.php?action=getBlockedIPs`, result => {
            if(result.success) {
                return resolve(result.data);
            } else {
                return resolve([]);
            }
        });
    }).then(ips => {
        blockedIPs = ips;

        getIPBlockStatus();
    });

    function getIPBlockStatus() {
        $('.blocked-ip').each(function() {
            const ip = $(this);

            if(blockedIPs.includes(ip.data("ip"))) {
                ip.css("display", "block");
            } else {
                ip.css("display", "none");
            }
        });
    }

    var attackObserver = new IntersectionObserver(function(entries) {
        for(let i=0; i<entries.length; i++) {
            if(entries[i].isIntersecting) {
                if($(entries[i].target).html() == "Loading..." && !$(entries[i].target).data("load")) {
                    attackObserver.unobserve(entries[i].target)
                    $.getJSON("modules/addons/pathfirewall/lib/Rest/api.php?action=getLastAttacked&ip=" + $(entries[i].target).data("ip"), function(result){
                        if(result.success) {
                            if(result.data !== null) {
                                if(result.data?.end) {
                                    $(entries[i].target).html(timeSince(new Date(result.data.start)) + " ago").addClass("label-success") 
                                } else {
                                    $(entries[i].target).html(`<i class="fas fa-sync fa-spin" style="color:orange;"></i>  Under Attack`).addClass("label-danger")
                                }
                            } else {
                                $(entries[i].target).html(`No recent attack`).addClass("label-success");
                            }
                        } else {
                            console.log(result.data)
                        }
                    });
                }
            }
        }

    }, { threshold: [1] });

    $(".lastattack").each(function() {
        attackObserver.observe(this);
    })
});

</script>