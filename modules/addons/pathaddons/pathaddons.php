<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

function pathaddons_config()
{
    return [
        'name' => 'Path Network',
        'description' => 'Management Module for Path Network Customers.',
        'author' => 'Path Network, Inc.',
        'language' => 'english',
        'version' => '1.0',
    ];
}

function pathaddons_activate()
{
    try {
        if(!Capsule::schema()->hasTable('path_firewall_limits')) {
            Capsule::schema()
                ->create(
                    'path_firewall_limits',
                    function ($table) {
                        /** @var \Illuminate\Database\Schema\Blueprint $table */
                        $table->increments('id');
                        $table->integer('clientid');
                        $table->integer('rulelimit');
                        $table->integer('filterlimit');
                        $table->integer('cidrlimit');
                    }
                );
        }
    } catch(\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Error: ' . $e
        ];
    }

    return [
        'status' => 'success',
        'description' => 'Successfully enabled Path WHMCS Addon',
    ];
}

function pathaddons_deactivate()
{
    return [
        'status' => 'success',
        'description' => 'Successfully disabled Path WHMCS Addon',
    ];
}

function pathaddons_sidebar($vars) {

    $page = "home";

    if(isset($_GET["page"])) {
        $page = $_GET["page"];
    }

    $sidebar = '
    <div class="sidebar-header">
        Firewall Manager
    </div>
    <ul class="menu">
        <li><a href="' . $vars["modulelink"] . '&page=view_filtered">' . (($page == "view_filtered") ? "- " : "") . 'View Filtered IPs</a></li>
        <li><a href="' . $vars["modulelink"] . '&page=view_user_limits">' . (($page == "view_user_limits") ? "- " : "") . 'View User Limits</a></li>
        <li><a href="' . $vars["modulelink"] . '&page=add_user_resource_limit">' . (($page == "add_user_resource_limit") ? "- " : "") . 'Add User Limit</a></li>
        <li><a href="' . $vars["modulelink"] . '&page=view_dns_zones">' . (($page == "view_dns_zones") ? "- " : "") . 'View DNS Zones</a></li>
    </ul>
';

    return $sidebar;
}

function initPath() {
    if (!class_exists("Path")) {
        if(file_exists('../../../path.class.php')) {
            require '../../../path.class.php';
        }
    }

    $path = new Path();
    $path->token("change_me", "change_me");

    return $path;
}

function pathaddons_output($vars)
{

    $page = "home";
    if(isset($_GET["page"])) { $page = $_GET["page"]; }
    
    $path = initPath();

    if($_SERVER["REQUEST_METHOD"] === "POST") {
        $action = "add";

        if(isset($_POST["action"])) {
            $action = $_POST["action"];
        }

        try {
            if($action === "delete_user_limit") {
                $id = $_POST["id"];

                Capsule::table('path_firewall_limits')->where('id', $id)->delete();
            }

            if($action === "add_user_limit") {
                $clientid = $_POST["clientid"];
                $filterlimit = $_POST["filterlimit"];
                $rulelimit = $_POST["rulelimit"];
                $cidrlimit = $_POST["cidrlimit"];

                Capsule::table('path_firewall_limits')->insert([
                    ['clientid' => $clientid, 'filterlimit' => $filterlimit, 'rulelimit' => $rulelimit, 'cidrlimit' => $cidrlimit]
                ]);
            }

            if($action === "add_dns_record") {
                $path->add_dns_zone_record($_POST["zone"], $_POST["record"]);
            }

            if($action === "edit_dns_record") {
                $path->update_dns_zone_record($_POST["zone"], $_POST["record"], $_POST["updated_record"]);
            }

            if($action === "delete_dns_record") {
                $path->delete_dns_zone_record($_POST["zone"], $_POST["record"]);
            }

            return header("Status: 200");
        } catch(\Exception $e) {
            echo "Error: " . $e;
            return header("Status: 501");
        }
    }
?>

<?php if ($page === "home") { ?>
    <h1>View commissions</h1>
    <?php if (sizeof($commissions) > 0) { ?>

        <?php foreach ($users as $staffmember) { ?>
            <h2 style="padding-top: 2rem"><?php echo $staffmember["user"]["firstname"] . " " . $staffmember["user"]["lastname"] ?></h2>
            
            <?php
            $total_amount = 0;
            $total_commission = 0;
            ?>

            <ul>
                <?php foreach ($staffmember["commissions"] as $commission) { ?>
                    <?php if($commission["status"] && $commission["status"] === "Active") { ?>
                        <li><a href="clientsservices.php?userid=<?php echo $commission["clientid"] ?>&id=<?php echo $commission["id"] ?>"><?php echo $commission["name"] . " - $" . number_format($commission["recurringamount"] / $BILLING_CYCLES[$commission["billingcycle"]], 2) . " ($" . number_format(($commission["recurringamount"] / $BILLING_CYCLES[$commission["billingcycle"]]) * ($commission["percentage"] / 100), 2) . ") [" . $commission["percentage"] . "%] | " . $commission["billingcycle"] ?></a> <span onclick="deleteCommission(<?php echo $commission['com_id'] ?>)" style="color: red; cursor: pointer;">[X]</a></li>

                        <?php
                        $total_amount += ($commission["recurringamount"] / $BILLING_CYCLES[$commission["billingcycle"]]);
                        $total_commission += ($commission["recurringamount"] / $BILLING_CYCLES[$commission["billingcycle"]]) * ($commission["percentage"] / 100);
                        ?>
                    <?php } else { ?>
                        <li><?php echo $commission["name"] . " - NOT ACTIVE" ?> <span onclick="deleteCommission(<?php echo $commission['com_id'] ?>)" style="color: red; cursor: pointer;">[X]</a></li>
                    <?php } ?>
                <?php } ?>
            </ul>
            <p>Total value: $<?php echo number_format($total_amount, 2) ?> per month | Commission: $<?php echo number_format($total_commission, 2) ?> per month</p>
        <?php } ?>

    <?php } else { ?>
        <p>No commissions have been registered. Register one by visiting the <a style="color: blue;" href="<?php echo $vars["modulelink"] . "&page=add" ?>">register commission</a> page.</p>
    <?php } ?>

    <script type="text/javascript">
        function deleteCommission(id) {
            $.post('<?php echo $vars["modulelink"] ?>', { action: "remove", id })
                .done(() => window.location.reload())
                .fail(() => alert("Deleting commission " + id + " failed"));
        }
    </script>

<?php } ?>

<?php if ($page === "add") { ?>
<h1>Register commission</h1>
<div class="row">
    <div class="col-md-8">
        <table style="width: 100%" class="form">
            <tbody>
                <tr>
                    <td width="130" class="fieldlabel">Client</td>
                    <td class="fieldarea">
                        <select id="selected-user" onchange="updateSelectedUser()" name="user" class="form-control select-inline">
                            <?php foreach ($queryClients["clients"]["client"] as $client) { ?>
                                <option value="<?php echo $client["id"] ?>"><?php echo $client["firstname"] . " " . $client["lastname"] . " - #" . $client["id"] ?></option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td width="130" class="fieldlabel">Order</td>
                    <td class="fieldarea">
                        <select onchange="updateSaleValue()" id="selected-product" name="product" class="form-control select-inline">
                            <?php foreach ($selectedUserProducts as $product) { ?>
                                <option value="<?php echo $product["id"] ?>"><?php echo $product["name"] . " - " . "#" . $product["id"] ?></option>
                            <?php } ?>

                            <?php if(!$selectedUserProducts || sizeof($selectedUserProducts) == 0 || $selectedUserProducts == "") { ?>
                                <option value="none">------</option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td width="130" class="fieldlabel">Staff Member</td>
                    <td class="fieldarea">
                        <select id="staff-member" name="staff-member" class="form-control select-inline">
                            <?php foreach ($queryAdmins["admin_users"] as $admin) { ?>
                                <option value="<?php echo $admin["id"] ?>"><?php echo $admin["firstname"] . " " . $admin["lastname"] . " - #" . $admin["id"] ?></option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td width="130" class="fieldlabel">Percentage</td>
                    <td class="fieldarea">
                        <input onchange="updateSaleValue()" id="percentage" type="number" value="5" min="0" max="100" /> %
                    </td>
                </tr>
                <tr>
                    <td width="130" class="fieldlabel"></td>
                    <td class="fieldarea">
                        Sale Value: $<span id="sale-value">0</span> per month | Commission Value: $<span id="commission-value">0</span> per month
                    </td>
                </tr>
            </tbody>
        </table>
        <a id="register-commission" href="#" class="btn btn-default btn-sm"><img src="images/icons/add.png" border="0" align="absmiddle" /> Register Commission</a>
    </div>
</div>

<script type="text/javascript">
    $('#selected-user').val(<?php echo $user ?>);

    const products = JSON.parse('<?php echo json_encode($productsQuery["products"]["product"]) ?>') || [];
    const billingCycles = JSON.parse('<?php echo json_encode($BILLING_CYCLES) ?>');

    function updateSelectedUser() {
        let selectedUserID = $('#selected-user').val();

        window.location.href = `<?php echo $vars["modulelink"] ?>&page=add&user=${ selectedUserID }`;
    }

    function updateSaleValue() {
        let selectedProduct = $('#selected-product').val();
        let percentage = $('#percentage').val();

        let product = products.find(x => x.id == selectedProduct);

        if(product) {
            $('#sale-value').text((parseFloat(product.recurringamount) / billingCycles[product.billingcycle]).toFixed(2));
            $('#commission-value').text((parseFloat(product.recurringamount / billingCycles[product.billingcycle]) * (percentage / 100)).toFixed(2));
        } else {
            $('#sale-value').text("0.00");
            $('#commission-value').text("0.00");

            $('#register-commission').attr("disabled", true);
        }
    }

    $('#register-commission').click((e) => {
        e.preventDefault();

        let attr = $('#register-commission').attr("disabled");
        if(attr === "disabled") return;

        let [product, staffMember, selectedUser, percentage] = [$('#selected-product').val(), $('#staff-member').val(), $('#selected-user').val(), $('#percentage').val()];

        $('#register-commission').attr("disabled", true);

        $.post('<?php echo $vars["modulelink"] ?>', { clientid: selectedUser, orderid: product, staffid: staffMember, percentage })
            .done(() => {
                window.location.href = "<?php echo $vars["modulelink"] ?>&page=home";
            })
            .fail(() => {
                $("#register-commission").attr("disabled", false);
            });
    });

    updateSaleValue();
</script>

<?php } ?>

<?php if($page === "view_protected") { ?>
    <h1>View protected users</h1>
    <?php
    
    $protected_users = Capsule::table('protected_users')->get();

    $result = localAPI("GetClients", [ 'limitnum' => 50000 ]);
    
    $users = array();

    foreach($result['clients']['client'] as $user) {
        $users[$user['id']] = $user;
    }

    foreach($protected_users as $protected) {
        $user = $users[$protected->clientid];
    ?>
        <h2 style="padding-top: 2rem"><?php echo $user["firstname"] . " " . $user["lastname"] ?></h2>
        <ul>
            <li>ID: <?php echo $user["id"] ?></li>
            <li>Email: <?php echo $user["email"] ?></li>
        </ul>
        <a href="#" onclick="removeProtection(<?php echo $protected->id ?>)" class="btn btn-danger btn-sm">Remove Protection</a>
    <?php } ?>

    <script type="text/javascript">
        function removeProtection(id) {
            $.post("<?php echo $vars['modulelink'] ?>", { action: "remove_protected", id })
                .done(() => window.location.reload())
                .fail(() => alert("Deleting protected user " + id + " failed"));
        }
    </script>
<?php } ?>

<?php if($page === "add_protected") { ?>
    <?php if($_GET["query"]) { ?>
        <h1>Add Protected User - Results</h1>
        <div class="row">
            <div class="col-md-8">
                <table style="width: 100%" class="form">
                    <tbody>
                    <tr>
                            <td width="130" class="fieldlabel">Client ID</td>
                            <td class="fieldarea">
                                <?php
                                
                                $response = localAPI("GetClients", [ 'limitnum' => 50000, search => $_GET["query"] ]);

                                $client = $response['clients']['client'][0];

                                echo '<span id="clientid">' . $client['id'] . '</span>';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td width="130" class="fieldlabel">Client Name</td>
                            <td class="fieldarea">
                                <?php echo $client['firstname'] . ' ' . $client['lastname'] ?>
                            </td>
                        </tr>
                        <tr>
                            <td width="130" class="fieldlabel">Client Email</td>
                            <td class="fieldarea">
                                <?php echo $client['email'] ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <a id="protect-client" href="#" onclick="protectClient()" class="btn btn-default btn-sm"><img src="images/icons/add.png" border="0" align="absmiddle" /> Protect client</a>
                <a id="go-back" href="<?php echo $vars["modulelink"] ?>&page=add_protected" class="btn btn-danger btn-sm">Go back</a>
            </div>
            <script type="text/javascript">
                function protectClient() {
                    $("#protect-client").attr("disabled", true);
                    $.post('<?php echo $vars['modulelink'] ?>&page=add_protected', { action: "protect", clientid: $('#clientid').text() })
                        .done(() => window.location.href = "<?php echo $vars["modulelink"] ?>&page=view_protected")
                        .fail(() => {
                            alert("Failed to add client to protected users.");
                            $("#protect-client").attr("disabled", false);
                        });
                }
            </script>
        </div>
    <?php } else { ?>
        <h1>Add Protected User - Search</h1>
        <div class="row">
            <div class="col-md-8">
                <table style="width: 100%" class="form">
                    <tbody>
                        <tr>
                            <td width="130" class="fieldlabel">Client Search</td>
                            <td class="fieldarea">
                                <input class="form-control" id="search" type="text" placeholder="Enter name, email or id...." />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <a id="lookup-client" onclick="lookupClient()" href="#" class="btn btn-default btn-sm"><img src="images/icons/add.png" border="0" align="absmiddle" /> Lookup client</a>
                <script type="text/javascript">
                function lookupClient() {
                    window.location.href = '<?php echo $vars["modulelink"] ?>&page=add_protected&query=' + encodeURIComponent($('#search').val());
                }
            </script>
            </div>
        </div>    
    <?php } ?>
<?php } ?>

<?php if($page === "view_filtered") { ?>
    <h1>View Filtered IPs</h1>
    
    <?php
        $abuse_filtered = $path->abuse_filtered();

        usort($abuse_filtered, function ($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });
    ?>

    <table class="table table-list">
        <thead>
            <tr>
                <th>IP Address</th>
                <th>Time Filtered</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($abuse_filtered as $filtered) { ?>
                <tr>
                    <td><?php echo $filtered["source"] ?></td>
                    <td><?php $date = new DateTime($filtered["created_at"]); echo $date->format('G:i:s D M j Y') ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
<?php } ?>

<?php if($page === "view_user_limits") { ?>
    <h1>User Firewall Limits</h1>

    <?php
    
    $limits = Capsule::table("path_firewall_limits")->get();

    ?>

    <table class="table table-list">
        <thead>
            <tr>
                <th>Client ID</th>
                <th>Filter Limit</th>
                <th>Rule Limit</th>
                <th>CIDR Limit</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($limits as $limit) { ?>
                <tr>
                    <td><?php echo $limit->clientid ?></td>
                    <td><?php echo $limit->filterlimit ?></td>
                    <td><?php echo $limit->rulelimit ?></td>
                    <td><?php echo $limit->cidrlimit ?></td>
                    <td><a onclick="RemoveUserLimit(<?php echo $limit->id ?>)" href="javascript:void(0)">Delete</a></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <script>
        function RemoveUserLimit(id) {
            $.post('<?php echo $vars['modulelink'] ?>', { action: "delete_user_limit", id })
                .done(() => (window.location.reload()))
                .fail(() => {});
        }
    </script>
<?php } ?>

<?php if($page === "add_user_resource_limit") { ?>
    <h1>Add User Firewall Limit</h1>

    <div class="row">
            <div class="col-md-8">
                <table style="width: 100%" class="form">
                    <tbody>
                        <tr>
                            <td width="130" class="fieldlabel">Client ID</td>
                            <td class="fieldarea">
                                <input class="form-control" id="clientid" type="text" placeholder="1">
                            </td>
                        </tr>
                        <tr>
                            <td width="130" class="fieldlabel">Rule limit</td>
                            <td class="fieldarea">
                                <input class="form-control" id="rulelimit" type="number" min="0" value="50">
                            </td>
                        </tr>
                        <tr>
                            <td width="130" class="fieldlabel">Filter limit</td>
                            <td class="fieldarea">
                                <input class="form-control" id="filterlimit" type="number" min="0" value="50">
                            </td>
                        </tr>
                        <tr>
                            <td width="130" class="fieldlabel">CIDR Limit</td>
                            <td class="fieldarea">
                                <input class="form-control" id="cidrlimit" type="number" min="0" max="32" value="22">
                            </td>
                        </tr>
                    </tbody>
                </table>
                <a id="add-resource-limit" href="javascript:void(0)" onclick="AddUserLimit()" class="btn btn-default btn-sm"><img src="images/icons/add.png" border="0" align="absmiddle" /> Add resource limit</a>
            </div>
        </div>
    </div>

    <script>
        function AddUserLimit() {
            $.post('<?php echo $vars["modulelink"] ?>', { action: "add_user_limit", clientid: $('#clientid').val(), filterlimit: $('#filterlimit').val(), rulelimit: $('#rulelimit').val(), cidrlimit: $('#cidrlimit').val() })
                .done(() => (window.location.href = '<?php echo $vars["modulelink"] ?>&page=view_user_limits'))
                .fail(() => {});
        }
    </script>
<?php } ?>

<?php

if($page === "toggle_dark_mode") {
    $exists = Capsule::table('darkblend_activate')->where('staffid', $_SESSION['adminid'])->first();
    if(!$exists) {
        Capsule::table('darkblend_activate')->insert([ 'staffid' => $_SESSION['adminid'] ]);
    } else {
        Capsule::table('darkblend_activate')->where('staffid', $_SESSION['adminid'])->delete();
    }

    return header('Location: ' . $vars['modulelink']);
}

?>

<?php if($page === "view_dns_zones") { ?>
    <h1>DNS Zones</h1>

    <?php
    $dns_zones = $path->dns_zones();
    ?>

    <table id="dns_zones" class="table table-list">
        <thead>
            <tr>
                <th>Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($dns_zones['zones'] as $zone) { ?>
                <tr>
                    <td><?php echo $zone['name'] ?></td>
                    <td><a href="<?php echo $vars['modulelink'] . '&page=view_dns_zone&zone=' . $zone['id'] . '&name=' . $zone['name'] ?>">View Zone</a></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <script>
        $('#dns_zones').DataTable();
    </script>
<?php } ?>

<?php
if($page === "view_dns_zone") {
    $dns_zone = $path->dns_zone($_GET["zone"]);
?>
    <h1 style="display: flex; flex-direction: row; align-items: center;">DNS Zone - <?php echo $_GET["name"] ?><div style="flex: 1"></div><div style="display: flex; flex-direction: row; gap: 1rem;"><button type="button" class="btn btn-primary" onclick="OpenAddPTRModal()">Quick Add PTR</button><button type="button" class="btn btn-primary" onclick="OpenAddRecordModal()">Add DNS Record</button></div></h1>

    <table id="dns_zone_table" class="table table-list no-footer">
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Content</th>
                <th>TTL</th>
                <th>Priority</th>
                <th>Disabled</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($dns_zone['records'] as $record) { ?>
                <tr>
                    <td><?php echo $record['name'] ?></td>
                    <td><?php echo $record['type'] ?></td>
                    <td><?php echo $record['content'] ?></td>
                    <td><?php echo $record['ttl'] ?></td>
                    <td><?php echo $record['priority'] ?></td>
                    <td><?php echo $record['disabled'] ? "Yes" : "No" ?></td>
                    <td><div style="display: flex; flex-direction: row; gap: 1rem;"><a onclick="OpenRecordEditModal('<?php echo $record['id'] ?>')" href="javascript:void(0)">Edit</a><a onclick="DeleteRecord('<?php echo $record['id'] ?>')" href="javascript:void(0)">Delete</a></div></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <div class="modal fade" id="modalAddPTR">
        <div class="modal-dialog">
            <div class="modal-content">
                <div style="display: flex; flex-direction: row; align-items: center;" class="modal-header">
                    <h4 class="modal-title">Add PTR Record</h4>
                    <div style="flex: 1"></div>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body">
                    <form role="form">
                        <div class="form-group">
                            <label class="control-label">IP Address</label>
                            <div>
                                <input id="ip" placeholder="1.3.3.7" type="text" class="form-control input-lg" name="ip">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Domain</label>
                            <div>
                                <input id="domain" placeholder="subdomain.example.org" type="text" class="form-control input-lg" name="domain">
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="QuickAddPTR()">Confirm</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditRecord">
        <input id="id" type="hidden" value="">
        <input id="modalType" type="hidden" value="edit">
        <div class="modal-dialog">
            <div class="modal-content">
                <div style="display: flex; flex-direction: row; align-items: center;" class="modal-header">
                    <h4 class="modal-title">Edit DNS Record</h4>
                    <div style="flex: 1"></div>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body">
                    <form role="form">
                        <div class="form-group">
                            <label class="control-label">Name</label>
                            <div>
                                <input id="name" placeholder="subdomain.example.org" type="text" class="form-control input-lg" name="name">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Type</label>
                            <div>
                                <select id="type" value="type" class="form-control input-lg">
                                    <option>A</option>
                                    <option>AAAA</option>
                                    <option>CERT</option>
                                    <option>CNAME</option>
                                    <option>DNSKEY</option>
                                    <option>DS</option>
                                    <option>MX</option>
                                    <option>NAPTR</option>
                                    <option>NS</option>
                                    <option>PTR</option>
                                    <option>SPF</option>
                                    <option>SRV</option>
                                    <option>SSHFP</option>
                                    <option>TLSA</option>
                                    <option>TXT</option>
                                    <option>URI</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Content</label>
                            <div>
                                <input id="content" placeholder="1.3.3.7" type="text" class="form-control input-lg" name="content">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">TTL</label>
                            <div>
                                <input id="ttl" type="number" class="form-control input-lg" name="ttl">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Priority</label>
                            <div>
                                <input id="priority" type="number" min="0" max="100" class="form-control input-lg" name="priority">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Disabled</label>
                            <div>
                                <select class="form-control input-lg" id="disabled" name="disabled">
                                    <option value="false">No</option>
                                    <option value="true">Yes</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="Confirm()">Confirm</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script>

        const records = JSON.parse('<?php echo json_encode($dns_zone['records']) ?>');

        let table;
        $(document).ready(function() {
            table = $('#dns_zone_table').DataTable();
        });

        function OpenRecordEditModal(id) {
            $('#modalEditRecord').modal('show');

            const record = records.find(x => x.id === id);
            if(record) {
                $('#modalEditRecord #name').val(record.name);
                $('#modalEditRecord #type option:contains("' + record.type + '")').prop('selected', true);
                $('#modalEditRecord #content').val(record.content);
                $('#modalEditRecord #ttl').val(record.ttl);
                $('#modalEditRecord #priority').val(record.priority);
                $('#modalEditRecord #disabled option[value="' + record.disabled + '"]').prop('selected', true);

                $('#modalEditRecord #id').val(id);
                $('#modalEditRecord .modal-title').text("Edit DNS Record");
                $('#modalEditRecord #modalType').val("edit");
            }
        }

        function DeleteRecord(record) {
            $.post('<?php echo $vars['modulelink'] ?>', { action: 'delete_dns_record', zone: '<?php echo $_GET["zone"] ?>', record })
                    .done(() => window.location.reload())
                    .fail(() => alert("Deleting dns record failed"));
        }

        function OpenAddPTRModal() {
            $('#modalAddPTR').modal('show');
        }

        function QuickAddPTR() {
            const record = {
                name: `${$('#modalAddPTR #ip').val().split(".").reverse().join(".")}.in-addr.arpa`,
                type: 'PTR',
                content: $('#modalAddPTR #domain').val(),
                ttl: 3600,
                priority: 0,
                disabled: false
            };

            $.post('<?php echo $vars['modulelink'] ?>', { action: 'add_dns_record', zone: '<?php echo $_GET["zone"] ?>', record })
                    .done(() => window.location.reload())
                    .fail(() => alert("Adding dns record failed"));
        }

        function OpenAddRecordModal() {
            $('#modalEditRecord').modal('show');
            $('#modalEditRecord #modalType').val("create");
            $('#modalEditRecord .modal-title').text("Create DNS Record");

            $('#modalEditRecord #name').val("");
            $('#modalEditRecord #content').val("");
            $('#modalEditRecord #ttl').val(3600);
            $('#modalEditRecord #priority').val(0);

            $('#modalEditRecord #disabled option:eq(0)').prop('selected', true);
            $('#modalEditRecord #type option:eq(0)').prop('selected', true);
        }

        function Confirm() {
            const modalType = $('#modalEditRecord #modalType').val();
            const recordId = $('#modalEditRecord #id').val();

            const record = {
                type: $('#modalEditRecord #type').val(),
                name: $('#modalEditRecord #name').val(),
                content: $('#modalEditRecord #content').val(),
                ttl: $('#modalEditRecord #ttl').val(),
                priority: $('#modalEditRecord #priority').val(),
                disabled: $('#modalEditRecord #disabled').val()
            };

            if(modalType === "edit") {
                return $.post('<?php echo $vars['modulelink'] ?>', { action: 'edit_dns_record', zone: '<?php echo $_GET['zone'] ?>', record: recordId, updated_record: record })
                    .done(() => window.location.reload())
                    .fail(() => alert("Editing dns record " + $('#modalEditRecord #id').val() + " failed"));
            }

            if(modalType === "create") {
                return $.post('<?php echo $vars['modulelink'] ?>', { action: 'add_dns_record', zone: '<?php echo $_GET["zone"] ?>', record })
                    .done(() => window.location.reload())
                    .fail(() => alert("Adding dns record failed"));
            }
        }
    </script>

<?php } ?>

<?php

}

?>