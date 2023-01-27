$.fn.serializeObject = function() {
  var data = {};

  function buildInputObject(arr, val) {
    if (arr.length < 1) {
      return val;  
    }
    var objkey = arr[0];
    if (objkey.slice(-1) == "]") {
      objkey = objkey.slice(0,-1);
    }  
    var result = {};
    if (arr.length == 1){
      result[objkey] = val;
    } else {
      arr.shift();
      var nestedVal = buildInputObject(arr,val);
      result[objkey] = nestedVal;
    }
    return result;
  }

  function gatherMultipleValues( that ) {
    var final_array = [];
    $.each(that.serializeArray(), function( key, field ) {
            // Copy normal fields to final array without changes
            if( field.name.indexOf('[]') < 0 ){
              final_array.push( field );
                return true; // That's it, jump to next iteration
              }

            // Remove "[]" from the field name
            var field_name = field.name.split('[]')[0];

            // Add the field value in its array of values
            var has_value = false;
            $.each( final_array, function( final_key, final_field ){
              if( final_field.name === field_name ) {
                has_value = true;
                final_array[ final_key ][ 'value' ].push( field.value );
              }
            });
            // If it doesn't exist yet, create the field's array of values
            if( ! has_value ) {
              final_array.push( { 'name': field_name, 'value': [ field.value ] } );
            }
          });
    return final_array;
  }

    // Manage fields allowing multiple values first (they contain "[]" in their name)
    var final_array = gatherMultipleValues( this );

    // Then, create the object
    $.each(final_array, function() {
      var val = this.value;
      var c = this.name.split('[');
      var a = buildInputObject(c, val);
      $.extend(true, data, a);
    });

    return data;
  };

  var protocols = [
  "tcp",
  "udp",
  "icmp",
  "hopopt",
  "igmp",
  "ggp",
  "ipv4",
  "st",
  "cbt",
  "egp",
  "igp",
  "bbn-rcc-mon",
  "nvp-ii",
  "pup",
  "argus",
  "emcon",
  "xnet",
  "chaos",
  "mux",
  "dcn-meas",
  "hmp",
  "prm",
  "xns-idp",
  "trunk-1",
  "trunk-2",
  "leaf-1",
  "leaf-2",
  "rdp",
  "irtp",
  "iso-tp4",
  "netblt",
  "mfe-nsp",
  "merit-inp",
  "dccp",
  "3pc",
  "idpr",
  "xtp",
  "ddp",
  "idpr-cmtp",
  "tp++",
  "il",
  "ipv6",
  "sdrp",
  "ipv6-route",
  "ipv6-frag",
  "idrp",
  "rsvp",
  "dsr",
  "bna",
  "esp",
  "ah",
  "i-nlsp",
  "swipe",
  "narp",
  "mobile",
  "tlsp",
  "skip",
  "ipv6-icmp",
  "ipv6-nonxt",
  "ipv6-opts",
  "cftp",
  "sat-expak",
  "kryptolan",
  "rvd",
  "ippc",
  "sat-mon",
  "visa",
  "ipcv",
  "cpnx",
  "cphb",
  "wsn",
  "pvp",
  "br-sat-mon",
  "sun-nd",
  "wb-mon",
  "wb-expak",
  "iso-ip",
  "vmtp",
  "secure-vmtp",
  "vines",
  "ttp",
  "iptm",
  "nsfnet-igp",
  "dgp",
  "tcf",
  "eigrp",
  "ospfigp",
  "sprite-rpc",
  "larp",
  "mtp",
  "ax.25",
  "ipip",
  "micp",
  "scc-sp",
  "etherip",
  "encap",
  "gmtp",
  "ifmp",
  "pnni",
  "pim",
  "aris",
  "scps",
  "qnx",
  "a/n",
  "ipcomp",
  "snp",
  "compaq-peer",
  "ipx-in-ip",
  "vrrp",
  "pgm",
  "l2tp",
  "ddx",
  "iatp",
  "stp",
  "srp",
  "uti",
  "smp",
  "sm",
  "ptp",
  "isis over ipv4",
  "fire",
  "crtp",
  "crudp",
  "sscopmce",
  "iplt",
  "sps",
  "pipe",
  "sctp",
  "fc",
  "rsvp-e2e-ignore",
  "mobility header",
  "udplite",
  "mpls-in-ip",
  "manet",
  "hip",
  "shim6",
  "wesp",
  "rohc"
  ];


  class firewallManager {
   constructor(ip) {
    var table =  $("#tableFirewallList");
    var dataTable = table.DataTable();
    this.table = table
    this.dataTable = dataTable
    $("#tableFirewallList_wrapper").hide();

    var table_limiters =  $("#tableRatelimitersList");
    var dataTable_limiters = table_limiters.DataTable();
    this.table_limiters = table_limiters
    this.dataTable_limiters = dataTable_limiters
    $("#tableRatelimitersList_wrapper").hide();   

    var  table_filters =  $("#tableFiltersList");
    var dataTable_filters = table_filters.DataTable();
    this.table_filters = table_filters
    this.dataTable_filters = dataTable_filters
    $("#tableFiltersList_wrapper").hide();

    var  table_attack_history =  $("#tableAttackList");
    var dataTable_attack_history = table_attack_history.DataTable();
    this.table_attack_history = table_attack_history
    this.dataTable_attack_history = dataTable_attack_history
    $("#tableAttackList_wrapper").hide();

    this.ip = ip
    var self = this;



    $('#filterSelect').on('change', function (e) {
      var optionSelected = $("option:selected", this);
      var valueSelected = this.value;

      var filter = self.availableFilters.find(filter => filter.name == valueSelected);

      if(!filter) {
        return;
      }
      $(".filterOptions").hide().html("")
      for(var id in filter.fields) {
        var field = filter.fields[id];
        switch(field.value.type) {
          case "port":
          $(".filterOptions").append(`  <div class="form-group">
            <label class="control-label">${field.label}</label>
            <div>
            <input type="number" class="form-control input-lg"  min="1" max="65535" placeholder="1-65535" name="${field.name}" required>
            </div>
            </div>`);
          break;

          case "bool":
            $('.filterOptions').append(`
              <div class="form-group">
                <label class="control-label">${field.label}</label>
                <div>
                  <select class="form-control input-lg" name="${field.name}">
                    <option value="false">No</option>  
                    <option value="true">Yes</option>
                  </select>
                </div>
              </div>
            `);
            break;

          case "integer":
            if(field.name == "max_conn_pps") {
              $('.filterOptions').append(`
              <div class="form-group">
                <label class="control-label">${field.label}</label>
                <div>
                  <input type="number" class="form-control input-lg" name="${field.name}" placeholder="Unlimited">
                </div>
              </div>
            `);
            }
            else {
              $('.filterOptions').append(`
                <div class="form-group">
                  <label class="control-label">${field.label}</label>
                  <div>
                    <input type="number" class="form-control input-lg" name="${field.name}" required>
                  </div>
                </div>
              `);
            }

          case "ip": case "cidr":
          break;
          default:
          console.log(`Error! I'm unaware of value type ${field.value.type}`)

          break;
        }

      }
      $(".filterOptions").fadeIn();

    });   

    $('#typeSelect').on('change', function (e) {
      var optionSelected = $("option:selected", this);
      var valueSelected = this.value;

      switch(valueSelected) {
		    	//Show rate limiter options
		    	case "ratelimit":
          $("#rateLimitSelect").html("");

          self.loadRatelimiters(function(limiters) {

           if(!limiters || limiters.length < 1) {
            $("#rateLimitSelect").append(`<option value="">None found</option>`).prop("disabled", true)
          } else {
            for(var id in limiters) {
             var limiter = limiters[id];

             $("#rateLimitSelect").append(`<option value="${limiter.id}">${limiter.comment.replace(`[${self.ip}]`, "")} (PPS: ${limiter.packets_per_second})</option>`)
           }
           $("#rateLimitSelect").prop("disabled", false)
         }
         $('#rateLimiterOption').fadeIn();
       })

          break;

		    	//Hide rate limiter options
		    	default:
          $('#rateLimiterOption').fadeOut();
          break;
        }

      });   


    $('#protocolSelect').on('change', function (e) {
      var optionSelected = $("option:selected", this);
      var valueSelected = this.value;

      if(valueSelected == "") {
        $("#portOptionsSrc").fadeOut();
        $("#portOptionsDest").fadeOut();
      } else{
        $("#portOptionsSrc").fadeIn();
        $("#portOptionsDest").fadeIn();
      } 

    });

    $('#newFilterForm').on('submit', function(e) {
      e.preventDefault();
      if($(this)[0].checkValidity()) {
       var data = $(this).serializeObject();
       delete data.token;

       $.ajax({
        type: "POST",
        url: "modules/addons/pathfirewall/lib/Rest/api.php?action=addFilter&type=" + data.name + "&ip=" + self.ip,
        data: JSON.stringify(data),
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        success: function( data ) {
         if(data.success) {
          var field = data.data;
          var filter = self.availableFilters.find(filter => filter.name == field.name);

          if(!filter) {
            return;
          }

          delete field.settings.addr;
          var attributes = Object.keys(field.settings).map(setting => {
            var fieldData = filter.fields.find(field => field.name == setting);
            return fieldData.label + " = " + field.settings[setting]; 
          });
          var attributesStr = "";
          if(attributes.length > 1) {
            attributesStr = attributes.join(`<br/>`)
          } else {
            attributesStr = attributes.join('')
          }


          self.dataTable_filters.row.add( [
           field.id,
           filter.label,
           attributesStr,
           '<button type="button" class="btn btn-danger btn-xs" onclick="firewall.deleteFilter(this, \'' + field.name + '\', \'' + field.id + '\')"><i class="fas fa-times"></i></button>'
           ] ).draw( false );
          self.filterCount = self.filterCount + 1;
          $(".filterCount").html(self.filterCount)

          $("#modalNewFilter").modal("hide");
          swal({
            title: `Filter Created!`,
            text: "This may take up to 15 - 30 minutes to apply",
            type: "success",
            timer: 1500,
            showConfirmButton: false

          });
        } else {
          $("#modalNewFilter").modal("hide");
          swal({
            title: `Error!`,
            text: (data.data.includes("\"") == false) ? data.data : "Failed to create filter",
            type: "error",
            timer: 1500,
            showConfirmButton: false

          });
        }
      }})
     } else {
      $(this)[0].reportValidity();
    }
  })

    $('#newRuleForm').on('submit', function(e) {
      e.preventDefault();
      if($(this)[0].checkValidity()) {
        $(this).addClass("disabled").prop("disabled", true);
        var data = $(this).serializeObject();
        var validated = {};

        for(var key in data) {
          if(key == "token") continue;
          switch(key) {
            //Validate source && destination
            case "destination":
            case "source":
            if(!data[key].includes("/")) {
              data[key] = data[key] + "/32"
            }
            if(/((\b|\.)(1|2(?!5(?=6|7|8|9)|6|7|8|9))?\d{1,2}){4}(-((\b|\.)(1|2(?!5(?=6|7|8|9)|6|7|8|9))?\d{1,2}){4}|\/((1|2|3(?=1|2))\d|\d))\b/g.test(data[key])) {
              validated[key] = data[key]
            } else{
              self.showErrorRule(`Invalid ${key} address. Must be a valid subnet`)
              return;
            }
            break;

            //Validate ports
            case "src_port":
            case "dst_port":
            if(data[key]) {
              if(data[key] > 0 && data[key] <= 65535) {
                validated[key] = parseInt(data[key])
              } else {
                self.showErrorRule("Invalid port. Must be within 1 to 65535")
              }
            }
            break;

            //Handle type:
            case "type":
            switch(data[key]) {
              case "whitelist":
              validated["whitelist"] = true;
              break;
              case "blacklist":
              validated["whitelist"] = false;
              break;
              case "ratelimit":
              validated["whitelist"] = false;
              if(data["rate_limiter_id"]) {
                validated["rate_limiter_id"] = data["rate_limiter_id"]
              }
              break;
            }
            break;

            case "protocol":
            if(data[key]) {
              validated[key] = data[key]
            }
            break;

            case "priority":
            if(data[key]) validated[key] = (data[key] == "true")
              break;


            case "comment":
            validated[key] = data[key]
            break;
          }  
        }

        $.ajax({
          type: "POST",
          url: "modules/addons/pathfirewall/lib/Rest/api.php?action=addRule&ip=" + self.ip,
          data: JSON.stringify(validated),
          contentType: "application/json; charset=utf-8",
          dataType: "json",
          success: function( data ) {
            if(data.success) {
              var field = data.data;
              self.dataTable.row.add( [
               field.id,
               (!field.protocol) ? "all" : field.protocol,
               field.destination,
               field.dst_port,
               field.source,
               field.src_port,
               (field.whitelist == true) ? "Whitelist" + ((field.priority) ? " with priority" : "")  : ((field.rate_limiter_id) ? `Ratelimit` : "Blacklist" + ((field.priority) ? " with priority" : "")),
               field.comment + ((field.rate_limiter_id) ? (" - Using limiter: " + field.rate_limiter_id) : ""),
               '<button type="button" class="btn btn-danger btn-xs" onclick="firewall.deleteRule(this, \'' + field.id + '\')"><i class="fas fa-times"></i></button>'
               ] ).draw( false );

              self.ruleCount = self.ruleCount + 1;
              $(".ruleCount").html(self.ruleCount)

              $("#modalNewRule").modal("hide");
              swal({
                title: `Rule Created!`,
                text: "This may take up to 15 - 30 minutes to apply",
                type: "success",
                timer: 1500,
                showConfirmButton: false

              });
            } else {
              $("#modalNewRule").modal("hide");
              swal({
                title: `Error!`,
                text: (data.data.includes("\"") == false) ? data.data : "Failed to create rule",
                type: "error",
                timer: 1500,
                showConfirmButton: false

              });
            }
            $(this).removeClass("disabled").prop("disabled", false);
          }
        });
      } else {
        $(this)[0].reportValidity();
      }
    })


$('#newRatelimiterForm').on('submit', function(e) {
 e.preventDefault();
 if($(this)[0].checkValidity()) {
   var data = $(this).serializeObject();
   var validated = {};

   for(var key in data) {
    if(key == "token") continue;
    switch(key) {

            //Validate ports
            case "packets_per_second":

            if(data[key]) {
              if(data[key] > 0) {
                validated[key] = parseInt(data[key])
              } else {
                showErrorRule("Invalid packets per second. Must be above 0")
              }
            }
            break;

            case "comment":
            validated[key] = data[key]
            break;
          }  
        }

        console.log(validated)

        $.ajax({
          type: "POST",
          url: "modules/addons/pathfirewall/lib/Rest/api.php?action=addRatelimiter&ip=" + self.ip,
          data: JSON.stringify(validated),
          contentType: "application/json; charset=utf-8",
          dataType: "json",
          success: function( data ) {
            if(data.success) {
              var field = data.data;
              self.dataTable_limiters.row.add( [
               field.id,
               field.packets_per_second,
               field.comment,
               '<button type="button" class="btn btn-danger btn-xs" onclick="firewall.deleteRatelimiter(this, \'' + field.id + '\')"><i class="fas fa-times"></i></button>'
               ] ).draw( false );

              self.ratelimiterCount = self.ratelimiterCount + 1;
              $(".ratelimiterCount").html(self.ratelimiterCount)

              $("#modalNewRatelimiter").modal("hide");
              swal({
                title: `Ratelimiter Created!`,
                text: "You can now go to rules and create rules with it.",
                type: "success",
                timer: 1500,
                showConfirmButton: false

              });
            } else {
              $("#modalNewRatelimiter").modal("hide");
              swal({
                title: `Error!`,
                text: (data.data.includes('"')) ? data.data : "Failed to create ratelimiter",
                type: "error",
                timer: 1500,
                showConfirmButton: false

              });
            }
          }
        });

      } else {
        $(this)[0].reportValidity();
      }
    })



$.each(protocols, function(i, protocol) {
 $('#protocolSelect').append(`<option>${protocol}</option>`);
});

$("#toggleSourcePort").change(function() {
  if(this.checked) {
    $('#src_port').show();
  } else {
    $('#src_port').hide();
    $('#src_port').val('');
  }
});

 //$(caller).prop("disabled", true).html(`<i class="fas fa-spinner fa-spin"></i> Loading...`).addClass("disabled");
$(".filterOptions").html("");
this.loadAvailableFilters(function(filters) {
  $("#filterSelect").html("")
  $("#filterSelect").append(`<option value="" disabled selected>Choose a filter</option>`)
  filters.sort((a, b) => a.label.localeCompare(b.label, 'es', {sensitivity: 'base'}))

  self.availableFilters = filters;
  for(var id in filters) {
    var filter = filters[id];

    $("#filterSelect").append(`<option value="${filter.name}">${filter.label}</option>`)
  }
  //$("#modalNewFilter").modal('show');
  //$(caller).prop("disabled", false).html(`New Filter`).removeClass("disabled");

})

this.loadRules(function() {
  $("#tableFirewallList_wrapper").fadeIn();
  jQuery('#tableLoadingRules').addClass('hidden');
  $("#rules > div > button").removeClass("disabled").prop("disabled", false);
  self.table.removeClass('hidden')
  self.dataTable.draw();
})

this.loadRatelimitersTable(function() {
  $("#tableRatelimitersList_wrapper").fadeIn();
  jQuery('#tableLoadingLimiters').addClass('hidden');
  $("#ratelimiters > div > button").removeClass("disabled").prop("disabled", false);
  self.table_limiters.removeClass('hidden')
  self.dataTable_limiters.draw();
})

this.loadFilters(function() {
  $("#tableFiltersList_wrapper").fadeIn();
  jQuery('#tableLoadingFilters').addClass('hidden');
  $("#filters > div > button").removeClass("disabled").prop("disabled", false);
  self.table_filters.removeClass('hidden')
  self.dataTable_filters.draw();
})

this.loadAttackHistory(function() {
  $("#tableAttackList_wrapper").fadeIn();
  jQuery('#tableLoadingAttack').addClass('hidden');
  $("#attacks > div > button").removeClass("disabled").prop("disabled", false);
  self.table_attack_history.removeClass('hidden')
  self.dataTable_attack_history.draw();
})

$('#rules').addClass('show');

}

showErrorRule(msg) {
  $("#errorRuleMsg").html(msg).parent().fadeIn().delay(15000).fadeOut();
}

showNewRule() {
 var self = this;
 if(self.ruleCount >= self.maxRules) {
   alert("Rule limit has been reached");
   return;
 } 
 $("#modalNewRule").modal('show');
}  

showNewRatelimiter() {
 var self = this;
 if(self.ratelimiterCount >= 20) {
   alert("Ratelimiter limit has been reached");
   return;
 } 
 $("#modalNewRatelimiter").modal('show');
}

showNewFilter(caller) {
 var self = this;
 if(self.filterCount >= self.maxFilters) {
   alert("Filter limit has been reached");
   return;
 }



}

newRule(caller, data) {

}


	//Actually send ajax to delete rule
	deleteRule(caller, id) {
    var self = this;
    swal({
      title: "Are you sure?",
      text: "This could cause some services to be exposed or stop working.",
      type: "warning",
      showCancelButton: true,
      confirmButtonClass: "btn-danger",
      confirmButtonText: "Yes, delete rule!",
      cancelButtonText: "No, cancel!",
      closeOnConfirm: false,
      closeOnCancel: false,
      showLoaderOnConfirm: true
    },
    function(isConfirm) {
      if (isConfirm) {

        $.getJSON("modules/addons/pathfirewall/lib/Rest/api.php?action=removeRule&ip=" + self.ip +  "&id=" + id, function(result){
          if(result.success) {
            swal({
              title: `Rule Deleted`,
              text: "This may take up to 15 - 30 minutes to apply",
              type: "success",
              timer: 1500,
              showConfirmButton: false

            });
            self.ruleCount = self.ruleCount - 1;
            $(".ruleCount").html(self.ruleCount)
            self.dataTable.row( $(caller).parents('tr') )
            .remove()
            .draw();
          } else {
            swal("Error", "Failed to delete rule", "error");
          }
        });
        

      } else {
        swal("Cancelled", "Rule has not been deleted.", "error");
      }
    });


  } 

  deleteRatelimiter(caller, id) {
    var self = this;
    swal({
      title: "Are you sure?",
      text: "This could cause some services to be exposed or stop working.",
      type: "warning",
      showCancelButton: true,
      confirmButtonClass: "btn-danger",
      confirmButtonText: "Yes, delete ratelimiter!",
      cancelButtonText: "No, cancel!",
      closeOnConfirm: false,
      closeOnCancel: false,
      showLoaderOnConfirm: true
    },
    function(isConfirm) {
      if (isConfirm) {

        $.getJSON("modules/addons/pathfirewall/lib/Rest/api.php?action=removeRatelimiter&ip=" + self.ip +  "&id=" + id, function(result){
          if(result.success) {
            swal({
              title: `Ratelimiter Deleted`,
              text: "This may take up to 15 - 30 minutes to apply",
              type: "success",
              timer: 1500,
              showConfirmButton: false

            });
            self.ratelimiterCount = self.ratelimiterCount - 1;
            $(".ratelimiterCount").html(self.ratelimiterCount)
            self.dataTable_limiters.row( $(caller).parents('tr') )
            .remove()
            .draw();
          } else {
            swal("Error", result.data, "error");
          }
        });
        

      } else {
        swal("Cancelled", "Ratelimiter has not been deleted.", "error");
      }
    });
    

  }  

  deleteFilter(caller, type, id) {
    var self = this;
    swal({
      title: "Are you sure?",
      text: "This could cause some services to be exposed or stop working.",
      type: "warning",
      showCancelButton: true,
      confirmButtonClass: "btn-danger",
      confirmButtonText: "Yes, delete filter!",
      cancelButtonText: "No, cancel!",
      closeOnConfirm: false,
      closeOnCancel: false,
      showLoaderOnConfirm: true
    },
    function(isConfirm) {
      if (isConfirm) {

        $.getJSON("modules/addons/pathfirewall/lib/Rest/api.php?action=removeFilter&ip=" + self.ip +  "&type=" + type + "&id=" + id, function(result){
          if(result.success) {
            swal({
              title: `Filter Deleted`,
              text: "This may take up to 15 - 30 minutes to apply",
              type: "success",
              timer: 1500,
              showConfirmButton: false

            });
            self.filterCount = self.filterCount - 1;
            $(".filterCount").html(self.filterCount)
            self.dataTable_filters.row( $(caller).parents('tr') )
            .remove()
            .draw();
          } else {
            swal("Error", result.data, "error");
          }
        });
        

      } else {
        swal("Cancelled", "Filter has not been deleted.", "error");
      }
    });
    

  }

  loadRules(callback) {
    var self = this;
    var dataTable = this.dataTable;
    $.getJSON("modules/addons/pathfirewall/lib/Rest/api.php?action=getRules&ip=" + this.ip, function(result){
     self.rules = result.data;
     self.ruleCount = 0;
     $.each(result.data, function(i, field){

       self.dataTable.row.add( [
         field.id,
         (!field.protocol) ? "all" : field.protocol,
         field.destination,
         field.dst_port,
         field.source,
         field.src_port,
         (field.whitelist == true) ? "Whitelist" + ((field.priority) ? " with priority" : "")  : ((field.rate_limiter_id) ? `Ratelimit` : "Blacklist" + ((field.priority) ? " with priority" : "")),
         ((field.comment) ? field.comment : "") + ((field.rate_limiter_id) ? (" - Using limiter: " + field.rate_limiter_id) : ""),
         `<button type="button" class="btn btn-danger btn-xs ${ field.nodelete ? "disabled" : "" }" onclick="${ field.nodelete ? "javascript:void(0)" : `firewall.deleteRule(this, '${ field.id }')` }"><i class="fas fa-times"></i></button>`
         ] ).draw( false );

       if(field.id !== "DEFAULT") self.ruleCount = self.ruleCount + 1;

     });
     self.maxRules = result.maxrules;

     $(".maxRules").html(self.maxRules)
     $(".ruleCount").html(self.ruleCount)
     if(callback) callback();

   });
  }
  loadFilters(callback) {
    var self = this;
    var dataTable = this.dataTable;
    this.loadAvailableFilters(function(filters) {
      self.availableFilters = filters;
      $.getJSON("modules/addons/pathfirewall/lib/Rest/api.php?action=getFilters&ip=" + self.ip, function(result){
       self.filters = result.data;
       self.filterCount = 0;
       $.each(result.data, function(i, field){
        var filter = self.availableFilters.find(filter => filter.name == field.name);

        if(!filter) {
          return;
        }

        delete field.settings.addr;
        var attributes = Object.keys(field.settings).map(setting => {
          var fieldData = filter.fields.find(field => field.name == setting);
          if(fieldData.name == "max_conn_pps" && field.settings["max_conn_pps"] == null) {
            return fieldData.label + " = " + "Unlimited";
          }
          return fieldData.label + " = " + field.settings[setting]; 
        });
        var attributesStr = "";
        if(attributes.length > 1) {
          attributesStr = attributes.join(`<br/>`)
        } else {
          attributesStr = attributes.join('')
        }


        self.dataTable_filters.row.add( [
         field.id,
         filter.label,
         attributesStr,
         '<button type="button" class="btn btn-danger btn-xs" onclick="firewall.deleteFilter(this, \'' + field.name + '\', \'' + field.id + '\')"><i class="fas fa-times"></i></button>'
         ] ).draw( false );

        self.filterCount = self.filterCount + 1;
        

      });

       self.maxFilters = result.maxfilters;
       $(".maxFilters").html(self.maxFilters)
       $(".filterCount").html(self.filterCount)
       if(callback) callback();

     });
    });
  } 

  getReadableFileSizeString(fileSizeInBytes) {

    var i = -1;
    var byteUnits = [' kbps', ' Mbps', ' Gbps', ' Tbps', 'Pbps', 'Ebps', 'Zbps', 'Ybps'];
    do {
        fileSizeInBytes = fileSizeInBytes / 1024;
        i++;
    } while (fileSizeInBytes > 1024);

    return Math.max(fileSizeInBytes, 0.1).toFixed(1) + byteUnits[i];
  }

  loadAttackHistory(callback) {
    var self = this;
    var dataTable = this.dataTable;
    $.getJSON("modules/addons/pathfirewall/lib/Rest/api.php?action=getAttackHistory&ip=" + this.ip, function(result){
     self.attack_history = result.data;
     self.attackHistoryCount = 0;
     $.each(result.data, function(i, field){

       self.dataTable_attack_history.row.add( [
         self.attackHistoryCount+1,
         field.reason,
         (field.start) ? new Date(field.start).toUTCString() : "unknown",
         (field.end) ? new Date(field.end).toUTCString() : `<i class="fas fa-sync fa-spin" style="color:orange;"></i> Filtering...`,
         field.peak_pps.value,
         self.getReadableFileSizeString(field.peak_bps.value)
         ] ).draw( false );

       self.attackHistoryCount = self.attackHistoryCount + 1;
     });
     if(callback) callback();

   });
  }

  loadRatelimitersTable(callback) {
    var self = this;
    var dataTable = this.dataTable;
    $.getJSON("modules/addons/pathfirewall/lib/Rest/api.php?action=getRatelimiters&ip=" + this.ip, function(result){
     self.ratelimiters = result.data;
     $.each(result.data, function(i, field){

       self.dataTable_limiters.row.add( [
         field.id,
         field.packets_per_second,
         field.comment,
         '<button type="button" class="btn btn-danger btn-xs" onclick="firewall.deleteRatelimiter(this, \'' + field.id + '\')"><i class="fas fa-times"></i></button>'
         ] ).draw( false );

       self.ratelimiterCount = self.ratelimiterCount + 1;
     });
     if(callback) callback();

   });
  } 



  loadRatelimiters(callback) {
    if(!callback) return;
    var self = this;

    $.getJSON("modules/addons/pathfirewall/lib/Rest/api.php?action=getRatelimiters&ip=" + this.ip, function(result){
     callback(result.data);
   });
  }  

  loadAvailableFilters(callback) {
    if(!callback) return;
    var self = this;
    if(self.availableFilters) {
      callback(self.availableFilters)
      return;
    }
    $.getJSON("modules/addons/pathfirewall/lib/Rest/api.php?action=getAvailableFilters&ip=" + this.ip, function(result){
      callback(result.data);
   });
  }

}