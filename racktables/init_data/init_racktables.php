<?php
require_once('../inc/init.php');
require_once('../inc/zabbixapi.php');

$row_id = 272;
$rack_height = 42;


function deleteRackTablesData() {
	// delete racktables items data
	$query = 'delete from item_information;';
	usePreparedExecuteBlade ($query);

	// delete racktables object data
	$allObjects = scanRealmByText('object');
	foreach($allObjects as $object) {
		$racklist = getResidentRacksData ($object["id"], FALSE);
		commitDeleteObject ($object["id"]);
		foreach ($racklist as $rack_id)
			usePreparedDeleteBlade ('RackThumbnail', array ('rack_id' => $rack_id));
	}

	// delete racktables rack data
	$allRacks = scanRealmByText('rack');
	foreach($allRacks as $rack) {
		releaseFiles ('rack', $rack['id']);
		destroyTagsForEntity ('rack', $rack['id']);
		usePreparedDeleteBlade ('RackSpace', array ('rack_id' => $rack['id']));
		commitDeleteObject ($rack['id']);
		resetRackSortOrder ($rack['row_id']);
	}

	$query = 'delete from rack_position;';
	usePreparedExecuteBlade ($query);

	$query = 'delete from rack_airconditioner;';
	usePreparedExecuteBlade ($query);
}

function initRackTablesRack($remain_hostgroups) {
	// get the data of zabbix hostgroup
	$params = array('output' => 'extend', 'sortfield' => array('name'));
	$result = doPost('hostgroup.get', $params);
	$hostgroups = isset($result['result']) ? $result['result'] : array();

	global $row_id;
	global $rack_height;
	$rowInfo = getRowInfo($row_id);
	$sort_order = $rowInfo['count'];

	foreach($hostgroups as $hostgroup) {
		if(in_array($hostgroup['name'] ,$remain_hostgroups)) {
			continue;
		}
		$taglist = genericAssertion ('taglist', 'array0');
		$sort_order += 1;
		$rack_id = commitAddObject ($hostgroup['name'], NULL, 1560, "", $taglist);

		// set height
		$params = array(
			'output' => array('hostids'),
			'groupids' => array($hostgroup['groupid']),
		);
		$result = doPost('host.get', $params);
		$height = isset($result['result']) ? count($result['result']) : 0;
		if($height < $rack_height) {
			$height = $rack_height;
		}

		commitUpdateAttrValue ($rack_id, 27, $height);
		commitUpdateAttrValue ($rack_id, 29, $sort_order);
		// Link it to the row
		commitLinkEntities ('row', $row_id, 'rack', $rack_id);
	}

	$rackDatas = array();

	$allRacks = scanRealmByText('rack');
	foreach($allRacks as $rack_id => $rack) {
		$rackDatas[$rack['name']] = $allRacks[$rack_id];
	}

	return $rackDatas;
}

function initRackTablesObject($rackDatas) {
	// zabbix host data
	$params = array('output' => 'extend');
	$result = doPost('host.get', $params);
	$hosts = isset($result['result']) ? $result['result'] : array();

	$objectDatas = array();

	foreach($hosts as $host) {
		$object_type_id  = 4;
		$object_name     = isset($host['host']) ? $host['host'] : "";
		$object_label    = "";
		$object_asset_no = "";
		$taglist         = array();
		$has_problems    = $host['status'] == 0 ? 'no' : 'yes';

		$object_id = commitAddObject
		(
			$object_name,
			$object_label,
			$object_type_id,
			$object_asset_no,
			$taglist
		);
		usePreparedUpdateBlade
		(
			'Object',
			array('has_problems' => $has_problems,),
			array('id' => $object_id)
		);

		$objectDatas[$host['hostid']] = $object_id;

		// set hostgroup
		$params = array(
			'output' => array('name'),
			'hostids' => array($host['hostid'])
		);
		$result = doPost('hostgroup.get', $params);
		$hostgroups = isset($result['result']) ? $result['result'] : array();

		$rack_ids = array();
		$_REQUEST = array();

		foreach($hostgroups as $hostgroup) {
			if(isset($rackDatas[$hostgroup['name']])) {
				$rack = $rackDatas[$hostgroup['name']];

				amplifyCell($rack);
				array_push($rack_ids, $rack['id']);

				$height = $rack['height'];
				for($i = 0; $i < 3; $i++) {
					for($j = $height; $j > 0; $j--) {
						if($rack[$j][$i]['state'] == 'F') {
							# state == "T" : mounted
							$_REQUEST['atom_'. $rack['id'] . "_$j" . "_$i"] = 'on';
							break 2;
						}
					}
				}
			}
		}

		$_REQUEST['object_id'] = $object_id;
		$_REQUEST['rackmulti'] = $rack_ids;

		$object = spotEntity ('object', $object_id);
		$changecnt = 0;
		// Get a list of rack ids which are parents of the object
		$parentRacks = reduceSubarraysToColumn (getParents ($object, 'rack'), 'id');
		$workingRacksData = array();
		foreach ($_REQUEST['rackmulti'] as $cand_id)
		{
			if (!isset ($workingRacksData[$cand_id]))
			{
				$rackData = spotEntity ('rack', $cand_id);
				amplifyCell ($rackData);
				$workingRacksData[$cand_id] = $rackData;
			}
			else
				$rackData = $workingRacksData[$cand_id];
			$is_ro = !rackModificationPermitted ($rackData, 'updateObjectAllocation', FALSE);
			// It's zero-U mounted to this rack on the form, but not in the DB.  Mount it.
			if (isset($_REQUEST["zerou_${cand_id}"]) && !in_array($cand_id, $parentRacks))
			{
				if ($is_ro)
					continue;
				$changecnt++;
				commitLinkEntities ('rack', $cand_id, 'object', $object_id);
			}
			// It's not zero-U mounted to this rack on the form, but it is in the DB.  Unmount it.
			if (!isset($_REQUEST["zerou_${cand_id}"]) && in_array($cand_id, $parentRacks))
			{
				if ($is_ro)
					continue;
				$changecnt++;
				commitUnlinkEntities ('rack', $cand_id, 'object', $object_id);
			}
		}
		foreach ($workingRacksData as &$rd)
			applyObjectMountMask ($rd, $object_id);

		$oldMolecule = getMoleculeForObject ($object_id);
		foreach ($workingRacksData as $rack_id => $rackData)
		{
			$is_ro = !rackModificationPermitted ($rackData, 'updateObjectAllocation', FALSE);
			if ($is_ro || !processGridForm ($rackData, 'F', 'T', $object_id))
				continue;
			$changecnt++;
			// Reload our working copy after form processing.
			$rackData = spotEntity ('rack', $cand_id);
			amplifyCell ($rackData);
			applyObjectMountMask ($rackData, $object_id);
			$workingRacksData[$rack_id] = $rackData;
		}
		if ($changecnt)
		{
			// Log a record.
			$newMolecule = getMoleculeForObject ($object_id);
			global $remote_username, $sic;
			usePreparedInsertBlade
			(
				'MountOperation',
				array
				(
					'object_id' => $object_id,
					'old_molecule_id' => count ($oldMolecule) ? createMolecule ($oldMolecule) : NULL,
					'new_molecule_id' => count ($newMolecule) ? createMolecule ($newMolecule) : NULL,
					'user_name' => $remote_username,
					'comment' => empty ($sic['comment']) ? NULL : $sic['comment'],
				)
			);
		}

		// set IP 
		$params = array(
			'output' => 'extend',
			'hostids' => $host['hostid']
		);

		$result = doPost('hostinterface.get', $params);
		$hostinterfaces = isset($result['result']) ? $result['result'] : array();
		foreach($hostinterfaces as $interface) {
			if(isset($interface['ip']) && $interface['ip'] != '127.0.0.1') {

				$allocs = getObjectIPAllocations($object_id);
				$current_ips = array();
				foreach($allocs as $alloc) {
					$ip = $alloc["addrinfo"]["ip"];
					$current_ips[$ip] = $ip;
				}

				$ip = $interface['ip'];
				if(!in_array($ip, array_values($current_ips))) {
					// new IP
					$ip_bin = ip_parse($ip);
					if(null == getIPAddressNetworkId ($ip_bin)) {
						// if ip is not exists, adding it into RackTables IPv4Prefix.
						$range = substr($ip, 0, strripos($ip, '.')) . '.0/24';
						$vlan_ck = NULL;
						$net_id = createIPv4Prefix ($range, 'admim', isCheckSet ('is_connected'), $taglist);
						$net_cell = spotEntity ('ipv4net', $net_id);
						if (isset ($vlan_ck))
						{
							if (considerConfiguredConstraint ($net_cell, 'VLANIPV4NET_LISTSRC'))
								commitSupplementVLANIPv4 ($vlan_ck, $net_id);
						}
					}

					bindIPToObject($ip_bin, $object_id, "", "");
				}
			}
		}


	}

	return $objectDatas;
}

function initRackTablesItem($objectDatas) {
	// zabbix item data
	$params = array('output' => 'extend');
	$result = doPost('item.get', $params);
	$items = isset($result['result']) ? $result['result'] : array();

	foreach($items as $item) {
		usePreparedInsertBlade
		(
			'item_information',
			array
			(
				'itemid' => $item['itemid'],
				'objectid' => $objectDatas[$item['hostid']],
				'hostid' => $item['hostid'],
				'name' => $item['name'],
				'type' => $item['type'],
				'key_' => $item['key_'],
				'interfaceid' => $item['interfaceid'],
				'delay' => $item['delay'],
				'history' => $item['history'],
				'trends' => $item['trends'],
				'value_type' => $item['value_type'],
				'trapper_hosts' => $item['trapper_hosts'],
				'units' => $item['units'],
				'multiplier' => $item['multiplier'],
				'delta' => $item['delta'],
				'snmp_community' => $item['snmp_community'],
				'snmp_oid' => $item['snmp_oid'],
				'snmpv3_securityname' => $item['snmpv3_securityname'],
				'snmpv3_securitylevel' => $item['snmpv3_securitylevel'],
				'snmpv3_authpassphrase' => $item['snmpv3_authpassphrase'],
				'snmpv3_privpassphrase' => $item['snmpv3_privpassphrase'],
				'snmpv3_authprotocol' => $item['snmpv3_authprotocol'],
				'snmpv3_privprotocol' => $item['snmpv3_privprotocol'],
				'snmpv3_contextname' => $item['snmpv3_contextname'],
				'formula' => $item['formula'],
				'error' => $item['error'],
				'lastlogsize' => $item['lastlogsize'],
				'logtimefmt' => $item['logtimefmt'],
				'templateid' => $item['templateid'],
				'valuemapid' => $item['valuemapid'],
				'delay_flex' => $item['delay_flex'],
				'params' => $item['params'],
				'ipmi_sensor' => $item['ipmi_sensor'],
				'data_type' => $item['data_type'],
				'authtype' => $item['authtype'],
				'username' => $item['username'],
				'password' => $item['password'],
				'publickey' => $item['publickey'],
				'privatekey' => $item['privatekey'],
				'mtime' => $item['mtime'],
				'flags' => $item['flags'],
				'filter' => $item['filter'],
				'port' => $item['port'],
				'description' => $item['description'],
				'inventory_link' => $item['inventory_link'],
				'lifetime' => $item['lifetime'],
				'status' => $item['status'],
			)
		);
	}
}

?>
