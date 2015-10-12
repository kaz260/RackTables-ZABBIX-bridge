// select
var typeSelect;
var typeDisplayElements = [], typeInterfaceids = [], typeKey_s = [];
/*
 change elements : row_interfaceid, flex_intervals_rows, row_authtype, row_username, row_password, row_password_key,  row_publickey, row_privatekey,
                   row_snmp_oid, row_snmp_community, row_snmpv3_contextname, row_snmpv3_securityname, snmpv3_securitylevel_rows,
                   row_port, row_trapper_hosts, row_executed_script, row_params, row_ipmi_sensor, row_params_f, row_delay
*/
// Zabbix agent
typeDisplayElements[0] = ["row_interfaceid", "flex_intervals_rows", "row_delay"];
typeInterfaceids[0] = ["agentips"];
// Zabbix agent (active)
typeDisplayElements[7] = ["row_delay"];
typeInterfaceids[7] = [];
// Simple check
typeDisplayElements[3] = ["row_interfaceid", "row_username", "row_password", "flex_intervals_rows", "row_delay"];
typeInterfaceids[3] = ["agentips", "snmpips", "ipmiips", "jmxips"];
// SNMPv1 agent
typeDisplayElements[1] = ["row_interfaceid", "row_snmp_oid", "row_snmp_community", "row_port", "flex_intervals_rows", "row_delay"];
typeInterfaceids[1] = [ "snmpips"];
// SNMPv2 agent
typeDisplayElements[4] = ["row_interfaceid", "row_snmp_oid", "row_snmp_community", "row_port", "flex_intervals_rows", "row_delay"];
typeInterfaceids[4] = [ "snmpips"];
// SNMPv3 agent
typeDisplayElements[6] = ["row_interfaceid", "row_snmp_oid", "row_snmpv3_contextname", "row_snmpv3_securityname", 
                          "snmpv3_securitylevel_rows", "row_port", "flex_intervals_rows", "row_delay"];
typeInterfaceids[6] = [ "snmpips"];
// SNMP trap
typeDisplayElements[17] = ["row_interfaceid"];
typeInterfaceids[17] = [ "snmpips"];
// Zabbix internal
typeDisplayElements[5] = ["flex_intervals_rows", "row_delay"];
typeInterfaceids[5] = [];
// Zabbix trapper
typeDisplayElements[2] = ["row_trapper_hosts"];
typeInterfaceids[2] = [];
// Zabbix aggregate
typeDisplayElements[8] = ["flex_intervals_rows", "row_delay"];
typeInterfaceids[8] = [];
// External check
typeDisplayElements[10] = ["row_interfaceid", "flex_intervals_rows", "row_delay"];
typeInterfaceids[10] = ["agentips", "snmpips", "ipmiips", "jmxips"];
// Database monitor
typeDisplayElements[11] = ["row_username", "row_password", "row_params", "flex_intervals_rows", "row_delay"];
typeInterfaceids[11] = [];
// IPMI agent
typeDisplayElements[12] = ["row_interfaceid", "row_ipmi_sensor", "flex_intervals_rows", "row_delay"];
typeInterfaceids[12] = ["ipmiips"];
// SSH agent
typeDisplayElements[13] = ["row_interfaceid", "row_authtype", "row_username", "row_executed_script", "flex_intervals_rows", "row_delay"];
typeInterfaceids[13] = ["agentips", "snmpips", "ipmiips", "jmxips"];
// TELNET agent
typeDisplayElements[14] = ["row_interfaceid", "row_username", "row_password", "row_executed_script", "flex_intervals_rows", "row_delay"];
typeInterfaceids[14] = ["agentips", "snmpips", "jmxips", "ipmiips"];
// JMX agent
typeDisplayElements[16] = ["row_interfaceid", "row_username", "row_password", "flex_intervals_rows", "row_delay"];
typeInterfaceids[16] = ["jmxips"];
// Calculated
typeDisplayElements[15] = ["row_params_f", "flex_intervals_rows", "row_delay"];
typeInterfaceids[15] = [];


var value_typeSelect;
var value_typeDisplayElements = [];
/*
 change elements : row_data_type, "row_units", "row_formula", row_logtimefmt, row_trends, row_delta, "row_inventory_link"
*/
// Numeric (unsigned)
value_typeDisplayElements[3] = ["row_data_type", "row_units", "row_formula", "row_trends", "row_delta", "row_inventory_link"];
// Numeric (float)
value_typeDisplayElements[0] = ["row_units", "row_formula", "row_trends", "row_delta", "row_inventory_link"];
// Character
value_typeDisplayElements[1] = ["row_inventory_link"];
// Log
value_typeDisplayElements[2] = ["row_logtimefmt"];
// Text
value_typeDisplayElements[4] = ["row_inventory_link"];


var data_typeSelect;
var data_typeDisplayElements = [];
/*
 change elements : row_units, row_formula, row_delta
*/
// Boolean
data_typeDisplayElements[3] = [];
// Octal
data_typeDisplayElements[1] = ["row_units", "row_formula", "row_delta"];
// Decimal
data_typeDisplayElements[0] = ["row_units", "row_formula", "row_delta"];
// Hexadecimal
data_typeDisplayElements[2] = ["row_units", "row_formula", "row_delta"];


var snmpv3_securitylevelSelect;
var snmpv3_securitylevelDisplayElements = [];
/*
 change elements : row_snmpv3_authprotocol, row_snmpv3_authpassphrase, row_snmpv3_privprotocol, row_snmpv3_privpassphrase
*/
// noAuthNoPriv
snmpv3_securitylevelDisplayElements[0] = [];
// authNoPriv
snmpv3_securitylevelDisplayElements[1] = ["row_snmpv3_authprotocol", "row_snmpv3_authpassphrase"];
// authPriv
snmpv3_securitylevelDisplayElements[2] = ["row_snmpv3_authprotocol", "row_snmpv3_authpassphrase", "row_snmpv3_privprotocol", "row_snmpv3_privpassphrase"];


var authtypeSelect;
var authtypeDisplayElements = [];
/*
 change elements : row_password, row_publickey, row_privatekey, row_password_key
*/
// Password
authtypeDisplayElements[0] = ["row_password"];
// Public key file
authtypeDisplayElements[1] = ["row_publickey", "row_privatekey", "row_password_key"];

var multiplierCheckBox;

var agentips = document.getElementById("agentips");
var snmpips = document.getElementById("snmpips");
var jmxips = document.getElementById("jmxips");
var ipmiips = document.getElementById("ipmiips");

init();
onDataTypeChange();
onValueTypeChange();
onSnmpv3_securitylevelChange();
onTypeChange();
onMultiplierChange();

function init() {
	// add listener
	typeSelect = document.getElementById("type");
	typeSelect.addEventListener("change", onTypeChange, false);

	value_typeSelect = document.getElementById("value_type");
	value_typeSelect.addEventListener("change", onValueTypeChange, false);

	data_typeSelect = document.getElementById("data_type");
	data_typeSelect.addEventListener("change", onDataTypeChange, false);

	snmpv3_securitylevelSelect = document.getElementById("snmpv3_securitylevel");
	snmpv3_securitylevelSelect.addEventListener("change", onSnmpv3_securitylevelChange, false);

	authtypeSelect = document.getElementById("authtype");
	authtypeSelect.addEventListener("change", onAuthtypeChange, false);

	multiplierCheckBox = document.getElementById("multiplier");
	multiplierCheckBox.addEventListener("change", onMultiplierChange, false);
}

function display(undisplayElements, displayElements) {
	for(var i = 0; i < undisplayElements.length; i ++) {
		var element = document.getElementById(undisplayElements[i]);
		element.style.display = "none";

		var inputs = element.getElementsByTagName("input");
		for(var j = 0; j < inputs.length; j ++) {
			inputs[j].disabled = true;
		}

		var selects = element.getElementsByTagName("select");
		for(var j = 0; j < selects.length; j ++) {
			selects[j].disabled = true;
		}

		var textareas = element.getElementsByTagName("textarea");
		for(var j = 0; j < textareas.length; j ++) {
			textareas[j].disabled = true;
		}
	}

	for(var i = 0; i < displayElements.length; i ++) {
		var element = document.getElementById(displayElements[i]);
		element.style.display = "";

		var inputs = element.getElementsByTagName("input");
		for(var j = 0; j < inputs.length; j ++) {
			inputs[j].disabled = false;
		}

		var selects = element.getElementsByTagName("select");
		for(var j = 0; j < selects.length; j ++) {
			selects[j].disabled = false;
		}

		var textareas = element.getElementsByTagName("textarea");
		for(var j = 0; j < textareas.length; j ++) {
			textareas[j].disabled = false;
		}
	}
}


var selectedInterfaceid;

function onTypeChange() {
	var undisplayElements = [
		"row_interfaceid", "flex_intervals_rows", "row_authtype", "row_username", "row_password", "row_password_key", "row_publickey",
		"row_privatekey", "row_snmp_oid", "row_snmp_community", "row_snmpv3_contextname", "row_snmpv3_securityname",
		"snmpv3_securitylevel_rows", "row_port", "row_trapper_hosts", "row_executed_script",
		"row_params", "row_ipmi_sensor", "row_params_f", "row_delay"
	];

	var displayElements = typeDisplayElements[typeSelect.value];

	display(undisplayElements, displayElements);

	onSnmpv3_securitylevelChange();
	onAuthtypeChange();

	// set value_type
	// 8 : Zabbix aggregate
	// 15 : Calculated
	var disabled = false;
	if(typeSelect.value == 8 || typeSelect.value == 15) {
		disabled = true;

		var value_typeSelect = document.getElementById("value_type");
		if ([ '1', '2', '4' ].indexOf(value_typeSelect.value) >= 0) {
			// 3 : Numeric (unsigned)
			value_typeSelect.value = 3;
			onValueTypeChange();
		}

		// 0 : Decimal
		document.getElementById("data_type").value = 0;
		onDataTypeChange();
	}
	// 1 : Character
	document.getElementById("value_type_1").disabled = disabled;
	// 2 : Log
	document.getElementById("value_type_2").disabled = disabled;
	// 4 : Text
	document.getElementById("value_type_4").disabled = disabled;

	var childNodes = document.getElementById("data_type").childNodes;
	for(var i = 0; i < childNodes.length; i ++) {
		var child = childNodes[i];
		if(child.value != 0) {
			childNodes[i].disabled = disabled;
		}
	}


	// set interfaceids
	if(agentips) {
		agentips.disabled = true;
	}
	if(snmpips) {
		snmpips.disabled = true;
	}
	if(jmxips) {
		jmxips.disabled = true;
	}
	if(ipmiips) {
		ipmiips.disabled = true;
	}
	var displayInterfaceids = typeInterfaceids[typeSelect.value];
	var showError = true;
	for(var i = 0; i < displayInterfaceids.length; i ++) {
		var optgroup = document.getElementById(displayInterfaceids[i]);
		if(optgroup) {
			optgroup.disabled = false;
			showError = false;
		}
	}

	// if there is no interfaceid of this type, show error message.
	if(showError) {
		document.getElementById("interface_not_defined").style.display = '';
		var interfaceSelected = document.getElementById("interfaceid");
		interfaceSelected.style.display = 'none';
		interfaceSelected.disabled = true;
		return;
	} else {
		document.getElementById("interface_not_defined").style.display = 'none';
		var interfaceSelected = document.getElementById("interfaceid");
		interfaceSelected.style.display = '';
		interfaceSelected.disabled = false;
	}

	if(!selectedInterfaceid) {
		selectedInterfaceid = document.getElementById("interfaceidSelected");
	}
	var selected = document.getElementById(displayInterfaceids[0] + "InterfaceidMain");
	if(selectedInterfaceid && displayInterfaceids.indexOf(selectedInterfaceid.parentNode.id) >= 0) {
		selected = selectedInterfaceid;
	}
	if(selected) {
		document.getElementById("interfaceid").value = selected.value;
	}

}

function onValueTypeChange() {
	var undisplayElements = ["row_data_type", "row_units", "row_formula", "row_logtimefmt", "row_trends", "row_delta", "row_inventory_link"];

	var displayElements = value_typeDisplayElements[value_typeSelect.value];

	display(undisplayElements, displayElements);

	onDataTypeChange();
}

function onDataTypeChange() {
	var undisplayElements = ["row_units", "row_formula", "row_delta"];

	var displayElements = data_typeDisplayElements[data_typeSelect.value];

	if(document.getElementById("row_data_type").style.display == "none") {
		return;
	}

	display(undisplayElements, displayElements);
}

function onSnmpv3_securitylevelChange() {
	var undisplayElements = ["row_snmpv3_authprotocol", "row_snmpv3_authpassphrase", "row_snmpv3_privprotocol", "row_snmpv3_privpassphrase"];

	var displayElements = snmpv3_securitylevelDisplayElements[snmpv3_securitylevelSelect.value];

	if(document.getElementById("snmpv3_securitylevel_rows").style.display == "none") {
		displayElements = [];
	}

	display(undisplayElements, displayElements);
}

function onAuthtypeChange() {
	var undisplayElements = ["row_password", "row_publickey", "row_privatekey", "row_password_key"];

	var displayElements = authtypeDisplayElements[authtypeSelect.value];

	if(document.getElementById("row_authtype").style.display == "none") {
		return;
	}

	display(undisplayElements, displayElements);
}

var delayFlexCount = 0;
var delayFlexTable = document.getElementById("delayFlexTable");
var formlist = document.getElementById("itemFormList");

function addDelayFlex(delay, period) {
	if(! (document.getElementById("delay_flex_message").style.display == "none")) {
		document.getElementById("delay_flex_message").style.display = "none";
	}

	// table row
	var row = delayFlexTable.insertRow(-1);
	row.setAttribute("id", "delay_flex_" + delayFlexCount);
	var cell = row.insertCell(-1);
	if(!delay) {
		delay =  document.getElementById("new_delay_flex_delay").value;
	}
	cell.innerHTML = delay;
	var cell = row.insertCell(-1);
	if(!period) {
		period = document.getElementById("new_delay_flex_period").value;
	}
	cell.innerHTML = period;
	var cell = row.insertCell(-1);
	cell.innerHTML = "<input class='input link_menu' type=button value=削除 onclick=javascript:removeDelayFlex(" + delayFlexCount + ");>";

	// input hidden
	var hiddenDelay = document.createElement("input");
	hiddenDelay.setAttribute("type", "hidden");
	hiddenDelay.setAttribute("id", "delay_flex_" + delayFlexCount +"_delay");
	hiddenDelay.setAttribute("name", "delay_flex[" + delayFlexCount + "][delay]");
	hiddenDelay.setAttribute("value", delay);
	formlist.appendChild(hiddenDelay);

	var hiddenPeriod = document.createElement("input");
	hiddenPeriod.setAttribute("type", "hidden");
	hiddenPeriod.setAttribute("id", "delay_flex_" + delayFlexCount +"_period");
	hiddenPeriod.setAttribute("name", "delay_flex[" + delayFlexCount + "][period]");
	hiddenPeriod.setAttribute("value", period);
	formlist.appendChild(hiddenPeriod);

	delayFlexCount ++;
}

function removeDelayFlex(deleteRowCount) {
	document.getElementById("delay_flex_" + deleteRowCount).remove();
	document.getElementById("delay_flex_" + deleteRowCount +"_delay").remove();
	document.getElementById("delay_flex_" + deleteRowCount +"_period").remove();

	if(delayFlexTable.getElementsByTagName("input").length == 0) {
		// if no input, show error message
		document.getElementById("delay_flex_message").style.display = "";
	}
}

function onMultiplierChange() {
	var formula = document.getElementById("formula"); 
	if (formula) {
		formula.disabled = !multiplierCheckBox.checked; 
	}
}

function validateNumericBox(obj, allowempty, defaultnumber) {
	if (obj != null) {
		if (allowempty) {
			if (obj.value.length == 0 || obj.value == null) {
				obj.value = '';
			}
			else {
				if (isNaN(parseInt(obj.value, 10))) {
					obj.value = defaultnumber;
				}
				else {
					obj.value = parseInt(obj.value, 10);
				}
			}
		}
		else {
			if (isNaN(parseInt(obj.value, 10))) {
				obj.value = defaultnumber;
			}
			else {
				obj.value = parseInt(obj.value, 10);
			}
		}
	}
}



