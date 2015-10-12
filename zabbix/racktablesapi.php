<?php
# The API to link RackTables with Zabbix

# username and password of RackTables
$username = 'admin';
$password = 'admin';

# racktables api URL
$url = "http://$username:$password@localhost/racktables/api.php";

# zabbix default group
$groupname = 'Default';


/*
* execute GET request
*/
function doGet($params) {
	global $url;
	
	# create parameters of GET request
	$paramStr = '?' . http_build_query($params);

	# ignore response error
	$context = stream_context_create(array(
		'http' => array('ignore_errors' => true)
	));
	
	$response = file_get_contents($url . $paramStr, false, $context);
	return json_decode($response, true);
}

/*
* execute POST request
*/
function doPost($params) {
	global $url;

	$data = http_build_query($params, "", "&");

	$opts = array (
		'http' => array (
			'method' => 'POST',
			'header'=> 'Content-type: application/x-www-form-urlencoded',
			'content' => $data));
	$context = stream_context_create($opts);
	$response = file_get_contents($url, false, $context);

	# response
	return json_decode($response, true);;
}

/*
* add racktables Rack
*/
function addRack($name, $objectnames = array()) {
	# parameters
	$params = array(
		'method' => 'add_rack',
		'name' => $name);
	
	# do GET requeset
	$response = doGet($params);

	if(isset($response['error'])) {
		return $response;
	}

	$rackid = $response['result'];

	return massUpdateRackAllocation($rackid, $objectnames);
}

/*
* update racktables Rack
*/
function updateRack($oldname, $newname, $objectnames = array()) {
	# parameters
	$params = array(
		'method' => 'update_rack',
		'oldname' => $oldname,
		'newname' => $newname);

	# response
	$response = doGet($params);

	if(isset($response['error'])) {
		return $response;
	}	

	$rackid = $response['result'];

	return massUpdateRackAllocation($rackid, $objectnames);
}

/*
* delete racktables Rack
*/
function deleteRack($name) {
	# parameters
	$params = array(
		'method' => 'delete_rack',
		'name' => $name);

	# response
	return doGet($params);
}

/*
*
*/
function massUpdateRackAllocation($rackid, $objectnames) {
	# get mounted information of rack
	$params = array(
		'method' => 'get_rack_cell',
		'rack_id' => $rackid);

	$rackdata = doGet($params);

	$mountedObjects = $rackdata['mountedObjects'];
	
	# host id
	$noExistsObjects = array();
	$leftObjectIds = array();
	$params = array(
		'method' => 'spot_id',
		'element' => 'object');
	foreach($objectnames as $name) {
		$params['name'] = $name;

		# object id
		$objectid = doGet($params);
		if($objectid < 0) {
			array_push($noExistsObjects, $name);
			continue;
		}
		
		# update Object Allocation
		if(!in_array($objectid, $mountedObjects)) {
			# if the Object is new, change Allocation.
			updateRackAllocation($rackid, $objectid);
		} else {
			# if is exists, remove from mounted information array
			unset($mountedObjects[array_search($objectid, $mountedObjects)]);
		}
	}
	
	# the left Object is deleted.
	foreach($mountedObjects as $objectid) {
		$params = array(
			'method' => 'get_object_allocation',
			'object_id' => $objectid);

		$response = doGet($params);

		$rackids = array();
		foreach($response['response']['racks'] as $id => $data) {
			array_push($rackids, $id);
		}

		# set allocation
		$allocate = array();
		foreach($rackids as $id) {
			if($id == $rackid) {
				# if the rack is the same as current rack, do nothing
				continue;
			}
			$data = $response['response']['racks'][$id];
			for($i = $data['height']; $i > 0; $i--) {
				$cell = $data[$i];
				for($j = 0; $j < count($cell); $j++) {
					if($cell[$j]['state'] == 'T' and $cell[$j]['object_id'] == $objectid) {
						array_push($allocate, "atom_$id" . "_$i" . "_$j");
					}
				}
			}
		}

		# parameters
		$params = array(
			'method' => 'update_object_allocation',
			'object_id' => $objectid,
			'allocate_to' => $allocate);

		doGet($params);
	}


	if(count($noExistsObjects) > 0) {
		$names = "";
		foreach($noExistsObjects as $name) {
			$names .= $name . '、';
		}
		$names = substr($names, 0 , -1);
		return array('error' => "the racktables object $names is not exists.");
	}

	return array('result' => 'OK');
}

/*
* upate Rack Allocation
*/
function updateRackAllocation($rackid, $objectid) {
	# object
	$params = array(
		'method' => 'get_object_allocation',
		'object_id' => $objectid);

	$response = doGet($params);

	$rackids = array();
	foreach($response['response']['racks'] as $id => $data) {
		array_push($rackids, $id);
	}
	
	if(in_array($rackid, $rackids)) {
		return array('result' => 'OK');
	}
	
	# set allocation
	$allocate = array();
	foreach($rackids as $id) {
		$data = $response['response']['racks'][$id];
		for($i = $data['height']; $i > 0; $i--) {
			$cell = $data[$i];
			for($j = 0; $j < count($cell); $j++) {
				if($cell[$j]['state'] == 'T' and $cell[$j]['object_id'] == $objectid) {
					array_push($allocate, "atom_$id" . "_$i" . "_$j");
				}
			}
		}
	}

	# get mounted information of rack
	$params = array(
		'method' => 'get_rack_cell',
		'rack_id' => $rackid);

	$rackdata = doGet($params);

	# if the object is already mounted, return.
	$mountedObjects = $rackdata['mountedObjects'];
	if(in_array($objectid, $mountedObjects)) {
		return array('result' => 'OK');
	}

	$height = $rackdata['height'];
	for($i = 0; $i < 3; $i++) {
		for($j = $height; $j > 0; $j--) {
			if($rackdata[$j][$i]['state'] == 'F') {
				# state == "T": mounted
				array_push($allocate, 'atom_'. $rackid . "_$j" . "_$i");
				break 2;
			}
		}
	}

	# parameters
	$params = array(
		'method' => 'update_object_allocation',
		'object_id' => $objectid,
		'allocate_to' => $allocate);

	return doGet($params);

}

/*
* clear Rack Allocation
*/
function clearRackAllocation($rackid) {
	# get mounted information of rack
	$params = array(
		'method' => 'get_rack_cell',
		'rack_id' => $rackid);

	$rackdata = doGet($params);

	# mounted object
	$mountedObjects = $rackdata['mountedObjects'];

	foreach($mountedObjects as $objectid) {
		# parameters
		$params = array(
			'method' => 'update_object_allocation',
			'object_id' => $objectid);

		doGet($params);
	}	
}

/*
* update racktables object
* @param name
* @param type    Object type of RackTables. Default：4(Server)
*/
function addObject($name, $racknames, $has_problems=0) {
	# api.php?method=my_add_object&name=server07&type=5&label=label&asset_no=001&taglist[]=&has_problems=yes
	$has_problems = ($has_problems == 1) ? 'yes' : 'no';
	$params = array(
		'method' => 'my_add_object',
		'name' => $name,
		'has_problems' => $has_problems);

	# response
	$response = doGet($params);

	if(isset($response['error'])) {
		return $response;
	}

	$objectid = $response['result'];

	return massUpdateObjectAllocation($racknames, $objectid);
}

/*
* update racktables object
*/
function updateObject($oldname, $newname, $racknames, $has_problems) {
	# create parameter
	$has_problems = ($has_problems == 1) ? 'yes' : 'no';
	$params = array(
		'method' => 'update_object',
		'oldname' => $oldname,
		'newname' => $newname);

	if(isset($has_problems)) {
		$params['has_problems'] = $has_problems;
	}

	# response
	$response = doGet($params);

	if(isset($response['error'])) {
		return $response;
	}

	$objectid = $response['result'];

	if(count($racknames) > 0) {
		return massUpdateObjectAllocation($racknames, $objectid);
	}

}

function updateObjectStatus($names, $has_problems) {
	$has_problems = ($has_problems == 1) ? 'yes' : 'no';
	$params = array(
		'method' => 'update_object_status',
		'names' => $names,
		'has_problems' => $has_problems
	);

	$response = doGet($params);

	/*
	if(isset($response['error'])) {
		return $response;
	}

	return $response['result'];
	*/
	return $response;
}

function massUpdateObjectAllocation($racknames, $objectid) {
	global $groupname;

	# get mounted information of object
	$params = array(
		'method' => 'get_object_allocation',
		'object_id' => $objectid);

	$response = doGet($params);

	$rackids = array();
	foreach($response['response']['racks'] as $id => $data) {
		array_push($rackids, $id);
	}

	# set allocation
	$allocate = array();
	$noExistsRacks = array();
	
	$params = array(
		'method' => 'spot_id',
		'element' => 'object');
	foreach($racknames as $name) {
		if($name == $groupname) {
			continue;
		}
		
		$params['name'] = $name;
		$rackid = doGet($params);

		if($rackid < 0) {
			array_push($noExistsRacks, $name);
			continue;
		}

		if(!in_array($rackid, $rackids)) {
			# if the Rack is new, get new alocation
			array_push($allocate, getRackAllocation($rackid));
		} else {
			# if the Rack is exists, get originally alocation.
			$data = $response['response']['racks'][$rackid];
			for($i = $data['height']; $i > 0; $i--) {
				$cell = $data[$i];
				for($j = 0; $j < count($cell); $j++) {
					if($cell[$j]['state'] == 'T' and $cell[$j]['object_id'] == $objectid) {
						array_push($allocate, "atom_$rackid" . "_$i" . "_$j");
					}
				}
			}
		}
	}

	# parameters
	$params = array(
		'method' => 'update_object_allocation',
		'object_id' => $objectid,
		'allocate_to' => $allocate);

	doGet($params);

	if(count($noExistsRacks) > 0) {
		$names = '';
		foreach($noExistsRacks as $name) {
			$names .= $name . '、';
		}
		$names = substr($names, 0, -1);
		return array('error' => "In racktables the rack $names is not exists.");
	}

	return array('result' => 'OK');
}

function getRackAllocation($rackid) {
	# get mounted information of rack
	$params = array(
		'method' => 'get_rack_cell',
		'rack_id' => $rackid);

	$rackdata = doGet($params);

	$allocation = '';
	/*
	for($i = $rackdata['height']; $i > 0; $i--) {
		$cell = $rackdata[$i];
		for($j = 0; $j < count($cell); $j++) {
			if($cell[$j]['state'] == 'F') {
				# state == "T" : mounted
				$allocation = 'atom_'. $rackid . "_$i" . "_$j";
				break 2;
			}

		}
	}
	*/
	$height = $rackdata['height'];
	for($i = 0; $i < 3; $i++) {
		for($j = $height; $j > 0; $j--) {
			if($rackdata[$j][$i]['state'] == 'F') {
				# state == "T" : mounted
				$allocation = 'atom_'. $rackid . "_$j" . "_$i";
				break 2;
			}
		}
	}

	return $allocation;
}

function deleteObject($name) {
	# parameters
	$params = array(
		'method' => 'my_delete_object',
		'name' => $name);

	return doGet($params);

}

/*
*
*/
function updateObjectIP($objectname, &$interfaces) {
	# get ip
	$ips = array();
	foreach($interfaces as $interface) {
		if($interface['ip'] == '127.0.0.1') {
			continue;
		}
		array_push($ips, $interface['ip']);
	}

	# parameters
	$params = array(
		'method' => 'update_object_ip',
		'object_name' => $objectname,
		'ip' => $ips);

	$response = doGet($params);

	if(isset($response['error'])) {
		return $response;
	}
	if(isset($response['errors'])) {
		$error_msg = '';
		foreach($response['errors'] as $error) {
			$error_msg .= $error;
		}
		return array('error' => $error_msg);
	}

	$result = $response['result'];
	array_push($result, '127.0.0.1');
	$no_exists = $response['no_exists'];
	# if the main ip is deleted, remember interface type
	$main_interface_types = array();

	foreach($interfaces as $key => $interface) {
		if(!in_array($interface['ip'], $result)) {
			if($interface['main'] == 1) {
				array_push($main_interface_types, $interface['type']);
			}
			unset($interfaces[$key]);
		}
	}

	foreach($interfaces as $key => $interface) {
		if(in_array($interface['type'], $main_interface_types)) {
			$interfaces['key']['main'] = 1;
		}
	}

	if(count($no_exists) > 0) {
		#  if the IP is not exists, set the response
		$no_exists_str = "";
		foreach($no_exists as $ip) {
			 $no_exists_str .= $ip .' ,';
		}
		$no_exists_str = substr($no_exists_str, 0, -1);
		
		return array('error' => 'In racktables the IP：' . $no_exists_str . 'is not exists.');
	}
}

function addItem($hostName, $item) {
	$item['method'] = 'add_item';
	$item['object_name'] = $hostName;

	# response
	return doPost($item);
}


function updateItem($item) {
	# parameters
	$item['method'] = 'update_item';

	# response
	return doPost($item);
}

function updateItemStatus($itemids, $status) {
	$params = array(
		'method' => 'update_item_status',
		'itemids' => $itemids,
		'status' => $status,
	);

	return doGet($params);
}

function deleteItem($itemid) {
	# parameters
	$params = array(
		'method' => 'delete_item',
		'itemid' => $itemid,
	);

	# response
	$response = doGet($params);

	if(isset($response['error'])) {
		return $response;
	}

	return $response;

}


?>
