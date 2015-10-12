<?php
require_once('init_racktables.php');
require_once('init_zabbix.php');

$remain_hostgroups = array(
	"Default",
	"Discovered hosts",
	"Hypervisors",
	"L2/L3-Switch",
	"Templates",
	"Virtual machines",
	"Zabbix servers"
);

switch ($_GET['action']) {
	// Delete RackTables data
	case 'deleteRackTables':
		deleteRackTablesData();
		echo 'RackTables data is deleted.';
		break;

	// Import Zabbix data into RackTables
	case 'initRackTables':
		$rackDatas = initRackTablesRack($remain_hostgroups);
		$objectDatas = initRackTablesObject($rackDatas);
		initRackTablesItem($objectDatas);
		echo 'Zabbix data is imported into RackTables.';
		break;

	// Delete Zabbix data
	case 'deleteZabbix':
		deleteZabbixData($remain_hostgroups);
		echo 'Zabbix data is deleted.';
		break;

	// Import RackTables into Zabbix
	case 'initZabbix':
		$hostgroupDatas = initZabbixHostgroup();
		initZabbixHost($hostgroupDatas);
		echo 'RackTables data is imported into Zabbix.';
		break;

	default:
		break;
}
?>
