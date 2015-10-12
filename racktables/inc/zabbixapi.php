<?php
/*
* The API to link racktables with zabbix. 
*/

# zabbix API URL
$url = 'http://localhost/zabbix/api_jsonrpc.php';

# zabbix username and password
$username = 'Admin';
$password = 'zabbix';

# default group
$defaultGroupName = 'Default';


if(!isset($_SESSION['auth'])) {
	login();
}

$defaultGroupId = searchGroup($defaultGroupName)[$defaultGroupName];

/*
* exceute post request
*
* @param method    zabbix api method
* @param params    json parameters
* @param id    request id
*
* return    result. If OK, return result.
*                   If NG, return error message.
*/
function doPost($method, $params, $id = 1) {
	global $url;
	$paramStr = buildParamStr($method, $params, $id);

	#echo "paramStr=$paramStr</br>";

	$opts = array (
		'http' => array (
			'method' => 'POST',
			'header'=> 'Content-type: application/json-rpc',
			'content' => $paramStr));
	$context = stream_context_create($opts);
	$response = file_get_contents($url, false, $context);

	#echo "response=$response</br>";
	
	# decode response
	$jsonArray = json_decode($response, true);
	
	# If OK, return result.
	$result = $jsonArray['result'];
	if(isset($result)) {
		return array('result' => $result);
	}
	
	# If error, return error message.
	$error = $jsonArray['error'];
	$errorMessage = $error['message'] . $error['data'];
	return array('error' => $errorMessage);	

}

/*
* create json string parameters.
*
* @param method    zabbix api method
* @param params    parameters
* @param id    request id
*
* return    result. If OK, return result.
*                   If NG, return error message.
*/
function buildParamStr($method, $params, $id = 1) {
	$postParams = array(
		'jsonrpc' => '2.0',
		'method' => $method,
		'params' => $params,
		'id' => $id,
		'auth' => $_SESSION['auth']);
	return json_encode($postParams);
}

/*
* login and setting session
*
*/
function login() {
	# parameters
	global $username;
	global $password;
	$params = array('user' => $username, 'password' => $password);
	
	# decode response
	$result = doPost('user.login', $params);
	$result = $result['result'];
	if(isset($result)) {
		# if login is success, put the auth string into session
		$_SESSION['auth'] = $result;
		return array('result' => $result);
	}

	return array('error' => 'login failed.');
}

/*
* add group.
*
* @param name    group name
*
* return
*/
function addGroup($name) {
	# parameters
	$params = array('name' => $name);
	
	# response
	$result =  doPost('hostgroup.create', $params);
	
	return $result;
}

/*
* get group id by name
* 
* @param name    group name
*
* return     group id array. Key：group name、 Value：group id.
*/
function searchGroup($name) {
	# parameters
	$params = array(
		'output' => 'extend',
		'filter' => array('name' => $name));

	# response
	$resultArray = doPost('hostgroup.get', $params);

	$groupidArray = array();
	foreach($resultArray['result'] as $result) {
		$groupidArray += array($result['name'] => $result['groupid']);
	}

	return $groupidArray;
}

/*
* update group name
*
* @param oldName    old name
* @param newName    new name
*
*/
function updateGroup($oldName, $newName) {
	# get id
	$groupid = searchGroup($oldName)[$oldName];
	
	# parameters
	$params = array('groupid' => $groupid, 'name' => $newName);

	# response
	$result = doPost('hostgroup.update', $params);

	return $result;
}

/*
* delete group
*
* @param name     group name
*
*/
function deleteGroup($name) {
	# get group id
	$groupid = searchGroup($name)[$name];

	# parameters
	$params = array($groupid);

	# response
	$result = doPost('hostgroup.delete', $params);

	return $result;
}

/*
* add host
*
* @param name
* @param main
* @param ip
* @param port
* @param type
* @param dns
* @param useip
*
*/
function addHost($name, $main = 1, $ip = '127.0.0.1', $port = '10050', $type = 1, $dns = '', $useip = 1) {
	global $defaultGroupId;
	
	# parameters
	$params = array(
		'host' => $name, 
		'groups' => array(array('groupid' => $defaultGroupId)), 
		'interfaces' => array(
			'type' => $type,
			'main' => $main,
			'useip' => $useip,
			'ip' => $ip,
			'dns' => $dns,
			'port' => $port));

	# response
	$result = doPost('host.create', $params);

	return $result;
}

function addHostWithInterfaces($name, $agentips, $snmpips, $jmxips, $ipmiips) {
	global $defaultGroupId;
	
	$interfaces = array();
	$main  = 1;
	foreach($agentips as $ip => $port) {
		if($port < 0) {
			$port = 10050;
		}
		$interface = array(
			'type' => 1,
			'main' => $main,
			'useip' => 1,
			'ip' => $ip,
			'dns' => '',
			'port' => $port);
		$main = 0;
		array_push($interfaces, $interface);
	}
	$main  = 1;
	foreach($snmpips as $ip => $port) {
		if($port < 0) {
			$port = 161;
		}
		$interface = array(
			'type' => 2,
			'main' => $main,
			'useip' => 1,
			'ip' => $ip,
			'dns' => '',
			'port' => $port);
		$main = 0;
		array_push($interfaces, $interface);
	}
	$main  = 1;
	foreach($jmxips as $ip => $port) {
		if($port < 0) {
			$port = 12345;
		}
		$interface = array(
			'type' => 4,
			'main' => $main,
			'useip' => 1,
			'ip' => $ip,
			'dns' => '',
			'port' => $port);
		$main = 0;
		array_push($interfaces, $interface);
	}
	$main  = 1;
	foreach($ipmiips as $ip => $port) {
		if($port < 0) {
			$port = 623;
		}
		$interface = array(
			'type' => 3,
			'main' => $main,
			'useip' => 1,
			'ip' => $ip,
			'dns' => '',
			'port' => $port);
		$main = 0;
		array_push($interfaces, $interface);
	}

	# parameters
	$params = array(
		'host' => $name,
		'groups' => array(array('groupid' => $defaultGroupId)),
		'interfaces' => $interfaces);

	# response
	return doPost('host.create', $params);
}

/*
* get host id by host name
*/
function searchHost($name) {
	# parameters
	$params = array(
		'output' => 'extend', 
		'filter' => array('host' => $name));

	# response
	$resultArray = doPost('host.get', $params);

	$hostidArray = array();
	foreach($resultArray['result'] as $result) {
		$hostidArray += array($result['host'] => $result['hostid']);
	}
	return $hostidArray;
}

/*
* update host name
*/
function updateHostName($oldName, $newName, $status=0) {
	# host id
	$hostid = searchHost($oldName)[$oldName];

	# parameters
	$params = array('hostid' => $hostid, 'host' => $newName, 'name' => $newName, 'status' => $status);

	# response
	$resutl = doPost('host.update', $params);

	return $result;
}

/*
* update group of host
* @param hostName    host name
* @param groupNames    group name array
*/
function updateHostGroup($hostName, $groupNames) {
	# host id
	$hostid = searchHost($hostName)[$hostName];

	global $defaultGroupId;

	#group id
	$groupids = array();
	/*
	$i = 0;
	if(count($groupNames) <= 0) {
		$groupids[$i] = array("groupid" => $defaultGroupId);
		$i++;
	}
	*/
	$groupids[0] = array('groupid' => $defaultGroupId);
	$i = 1;
	foreach($groupNames as $name) {
		$groupids[$i]= array('groupid' => searchGroup($name)[$name]);
		$i++;
	}
	
	# parameters
	$params = array('hostid' => $hostid, 'groups' => $groupids);

	# response
	$result = doPost('host.update', $params);

	return $result;
}

/*
* reset host
*/
function resetHost($name) {
	# host id
	$hostid = searchHost($name)[$name];

	global $defaultGroupId;

	$interface = array(
			'type' => 1,
			'main' => 1,
			'useip' => 1,
			'ip' => '127.0.0.1',
			'dns' => '',
			'port' => '1005');

	# parameters
	$params = array(
		'hostid' => $hostid,
		'groups' => array(array('groupid' => $defaultGroupId)),
		'interfaces' => array($interface)
	);

	# response
	return doPost('host.update', $params);
}

/*
* delete host
*/
function deleteHost($name) {
	# host id
	$hostid = searchHost($name)[$name];
	
	# parameters
	$params = array($hostid);

	# response
	$result = doPost('host.delete', $params);

	return $result;
}

/*
* add interface (IP)
*/
function addInterface($hostName, $ip, $main = 0, $port = '10050', $type = 1, $useip = 1, $dns = '') {
	# host id
	$hostid = searchHost($hostName)[$hostName];

	# get interface in the host
	$interfaces = searchInterfaces($hostid);

	if(count($interfaces) < 2) {
		if($interfaces[0]['ip'] == '127.0.0.1') {
			updateInterface($interfaces[0]['interfaceid'], 1, $ip);	
			return;
		}
	}

	# parameters
	$params = array(
		'hostid' => $hostid,
		'ip' => $ip,
		'port' => $port,
		'type' => $type,
		'main' => $main,
		'useip' => $useip,
		'dns' => $dns);
	# response
	$result = doPost('hostinterface.create', $params);

	return $result;
}

/*
* get interface of specified host
*/
function searchInterfaces($hostid) {
	# parameters
	$params = array('output' => 'extend', 'hostids' => $hostid);

	# response
	$resultArray = doPost('hostinterface.get', $params);

	$interfaceidArray = array();

	return $resultArray['result'];
}

/*
* get interface by id
*/
function searchInterfaceById($id) {
	# parameters
	$params = array('output' => 'extend', 'interfaceids' => $id);

	# response
	$resultArray = doPost('hostinterface.get', $params);

	return $resultArray['result'];
}

/*
* update interface
*/
function updateInterface($id, $main = 0, $ip = null) {
	# parameters
	$params = array('interfaceid' => $id, 'main' => $main);

	if(isset($ip)) {
		$params['ip'] = $ip;
	}

	# response
	$result = doPost('hostinterface.update', $params);

	return $result;
}

/*
* delete interface.
*/
function deleteInterface($hostName, $ip) {
	# host id
	$hostid =  searchHost($hostName)[$hostName];

	# get interface of host
	$interfaces = searchInterfaces($hostid);

	$interfaceid = -1;

	$flag = false;
	$length = count($interfaces);

	for($i = 0; $i < $length; $i++) {
		if($interfaces[$i]['ip'] == $ip ) {
			# interface id
			$interfaceid = $interfaces[$i]['interfaceid'];
			
			if($interfaces[$i]['main'] == 1) {
				# if deleted interface type is main, change main interface into next interface.
				if($length > 1) {
					$mainIndex = $i + 1 >= $length ? $i + 1 - $length : $i +1;
					updateInterface($interfaces[$mainIndex]['interfaceid'], 1);
				} else {
					updateInterface($interfaceid, 0, '127.0.0.1');
					return;
				}
			}

			# delete interface
			$params = array($interfaceid);
			doPost('hostinterface.delete', $params);

			$flag = true;
		}
	}
	
	if($flag) {
		return array('result' => 'OK');
	} else {
		return array('error' => "interface $ip is not exists.");
	}
}

/*
* add item
*/
function addItem($item) {

	# response
	$result = doPost('item.create', $item);

	return $result;
}

/*
* update item
*/
function updateItem($item) {

	# response
	$result = doPost('item.update', $item);

	return $result;
}

/*
* delete item
*/
function deleteItem($itemid) {
	# parameters
	$params = array($itemid);

	# response
	$result = doPost('item.delete', $params);

	return $result;
}


/*
* get appllication
*/
function getApplication($hostid) {
	# parameters
	$params = array(
		'output' => 'extend',
		'hostids' => $hostid,
		'sortfield' => 'name'
	);

	return doPost('application.get', $params);
}


?>
