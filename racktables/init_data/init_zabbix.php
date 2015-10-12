<?php
require_once('../inc/init.php');
require_once('../inc/zabbixapi.php');


function deleteZabbixData($remain_hostgroups) {
	// get item Data
	$params = array('output' => array('itemid'));
	$result = doPost('item.get', $params);
	$items = isset($result['result']) ? $result['result'] : array();

	$itemids = array();
	foreach($items as $item) {
		array_push($itemids, $item['itemid']);
	}

	// delete item
	if(count($itemids) > 0) {
		$result = doPost('item.delete', $itemids);
		if(isset($result['error'])) {
			http_response_code(500);
			return;
		}
	}

	// get host data
	$params = array('output' => array('hostid'));
	$result = doPost('host.get', $params);
	$hosts = isset($result['result']) ? $result['result'] : array();

	$hostids = array();
	foreach($hosts as $host) {
		array_push($hostids, $host['hostid']);
	}

	// delete host
	if(count($hostids) > 0) {
		$result = doPost('host.delete', $hostids);
		if(isset($result['error'])) {
			http_response_code(500);
			return;
		}
	}

	// get hostgroup data
	$params = array('output' => 'extend');
	$result = doPost('hostgroup.get', $params);
	$hostgroups = isset($result['result']) ? $result['result'] : array();

	$hostgroupids = array();
	foreach($hostgroups as $hostgroup) {
		if(in_array($hostgroup['name'], $remain_hostgroups)) {
			continue;
		}
		array_push($hostgroupids, $hostgroup['groupid']);
	}

	// delete hostgroup
	if(count($hostgroupids) > 0) {
		$result = doPost('hostgroup.delete', $hostgroupids);
		if(isset($result['error'])) {
			http_response_code(500);
			return;
		}
	}
}

function initZabbixHostgroup() {
	$hostgroupDatas = array();

	$allRacks = scanRealmByText('rack');
	foreach($allRacks as $rack) {
		$params = array('name' => $rack['name']);
		$result = doPost('hostgroup.create', $params);

		$id = isset($result['result']) ? $result['result']['groupids'][0] : -1;
		if($id < 0) {
			http_response_code(500);
			exit;
		}
		$hostgroupDatas[$rack['name']] = $id;
	}

	return $hostgroupDatas;
}

function initZabbixHost($hostgroupDatas) {
	$allObjects = scanRealmByText('object');
	foreach($allObjects as $object) {
		// get group data
		$groups = array();
		$parentRacks = getResidentRacksData ($object['id']);
		foreach($parentRacks as $key => $rack) {
			array_push($groups, array('groupid' => $hostgroupDatas[$rack['name']]));
		}

		// set interfaces
		$interfaces = array();
		$allocs = getObjectIPAllocations($object['id']);
		$current_ips = array();
		foreach($allocs as $alloc) {
			$interface = array(
				'type' => 1,
				"main" => 0,
				"useip" => 1,
				"ip" => $alloc["addrinfo"]["ip"],
				"dns" => "",
				"port" => "10050"
			);
			array_push($interfaces, $interface);
		}

		if(count($interfaces) < 1) {
			$interface = array(
				'type' => 1,
				"main" => 1,
				"useip" => 1,
				"ip" => "127.0.0.1",
				"dns" => "",
				"port" => "10050"
			);
			array_push($interfaces, $interface);
		} else {
			$interfaces[0]['main'] = 1;
		}

		// insert host
		$params = array(
			'host' => $object['name'],
			'groups' => $groups,
			'interfaces' => $interfaces,
		);

		$result = doPost('host.create', $params);

		// set result
		$id = isset($result['result']) ? $result['result']['hostids'][0] : -1;
		if($id < 0) {
			http_response_code(500);
			exit;
		}
	}

}

?>
