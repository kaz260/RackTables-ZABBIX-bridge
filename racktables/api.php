<?php
// TODO: look into using global $sic, which has merged GET and POST parameters, instead of $_REQUEST['blah'].
//       Apparently it may handle UTF-8 arguments better. Created in transformRequestData().
ob_start();
require_once 'inc/pre-init.php';

##############################################################################################################
# to link with zabbix added.

$defaultRowId = 272;

// get the entity id
function getEntityIdByName($element , $name) {
	// set table name
	$id = -1;
	$table = "";
	switch($element) {
	case "rack":
		$table = "Rack";
		break;
	case "object":
		$table = "Object";
		break;
	default:
		return $id;
	}

	$query = "SELECT id, name FROM $table WHERE name = '$name'";
	$result = usePreparedSelectBlade($query);
	
	foreach($result as $key => $value) {
		$id = $value["id"];
		break;
	}
	return $id;
}
#############################################################################################################

try {
	switch ( $_REQUEST['method'] )
	{

	#######################################################################################################
	//  api.php?method=spot_entity&element=rack&name=test
	case "spot_entity":
		require_once('inc/init.php');
		$id = getEntityIdByName($_REQUEST["element"], $_REQUEST["name"]);
		$element = spotEntity($_REQUEST["element"], $id);

		echo json_encode($element);
		return;
	
	//
	// api.php?method=spot_id&element=object&name=test1
	case "spot_id":
		require_once('inc/init.php');
		
		$id = getEntityIdByName($_REQUEST["element"], $_REQUEST["name"]);
		echo $id;
		return;
	
	// api.php?method=add_rack&name=test4&row_id=2&height=42&asset_no=
	case "add_rack":
		require_once('inc/init.php');

		# confirm that rack is already exists or not
		$query = "SELECT id,name FROM Rack WHERE name = '" . $_REQUEST["name"] . "';";
		$result = usePreparedSelectBlade($query);
		foreach($result as $key => $rack) {
			if($rack) {
				# if exists, then return
				echo json_encode( array("result" => $rack["id"]));
				return;
			}
		}

		$taglist = genericAssertion ('taglist', 'array0');
		
		// default row id 
		global $defaultRowId;
		if(!isset($_REQUEST["row_id"]))
			$_REQUEST["row_id"] = $defaultRowId;
		$rowInfo = getRowInfo($_REQUEST["row_id"]);
		$sort_order = $rowInfo['count']+1;
		
		if(!isset($_REQUEST["asset_no"]))
			$_REQUEST["asset_no"] = "";

		$rack_id = commitAddObject ($_REQUEST['name'], NULL, 1560, $_REQUEST["asset_no"], $taglist);
		
		// Set the height and sort order
		if(!isset($_REQUEST["height"]))
			$_REQUEST["height"] = 42;
		commitUpdateAttrValue ($rack_id, 27, $_REQUEST["height"]);
		commitUpdateAttrValue ($rack_id, 29, $sort_order);

		// Link it to the row
		commitLinkEntities ('row', $_REQUEST['row_id'], 'rack', $rack_id);

		echo json_encode(array("result" => $rack_id));
		return;
	
	// api.php?method=update_rack&oldname=test1&newname=test2
	case "update_rack":
		require_once('inc/init.php');
		
		$id = getEntityIdByName("rack", $_REQUEST["oldname"]);
		if($id < 0) {
			echo json_encode(array("error" => "In racktables the rack ". $_REQUEST["oldname"] . "is not existed."));
			return;
		}

		$rack = spotEntity("rack", $id);
		
		$taglist = genericAssertion ("taglist", "array0");

		#usePreparedDeleteBlade ("RackThumbnail", array ("rack_id" => $rack["id"]));
		commitUpdateRack
		(
			$rack["id"],
			$rack["row_id"],
			$_REQUEST["newname"],
			$rack["height"],
			$rack["has_problem"],
			$rack["asset_no"],
			$rack["comment"]
		);
		#updateObjectAttributes ($rack["id"]);
		#rebuildTagChainForEntity ("rack", $rack["id"], buildTagChainFromIds ($taglist), TRUE);
		
		echo json_encode(array("result" => $rack["id"]));
		return;

	// api.php?method=delete_rack&name[]=test1
	case "delete_rack":
		require_once('inc/init.php');

		$id = getEntityIdByName("rack", $_REQUEST["name"]);
		if($id < 0) {
			echo json_encode(array("error" => "In racktables the rack ". $_REQUEST["name"] . "is not existed."));
			return;
		}

		$rack = spotEntity("rack", $id);

		amplifyCell ($rack);
		if (count ($rack["mountedObjects"]))
		{
			echo json_encode(array("error" => "There are mounted Objects in the rack ". $_REQUEST["name"] . "."));
			return;
		}
		releaseFiles ("rack", $rack["id"]);
		destroyTagsForEntity ("rack", $rack['id']);
		usePreparedDeleteBlade ("RackSpace", array ("rack_id" => $rack["id"]));
		commitDeleteObject ($rack["id"]);
		resetRackSortOrder ($rack["row_id"]);
	
		# delete position information
		usePreparedDeleteBlade ('rack_position', array ('rack_id' => $id));
		# delete airconditioner information
		usePreparedDeleteBlade ('rack_airconditioner', array ('rack_id' => $id));

		echo json_encode(array("result" => $rack["id"]));
		return;
	
	// add_object
	// api.php?method=my_add_object&type=4&name=test&label=&asset_no=&taglist[]=&has_problems=no
	case "my_add_object":
		require_once "inc/init.php";
		$object_type_id  = isset($_REQUEST["type"])        ? $_REQUEST["type"]     : 4;
		$object_name     = isset ( $_REQUEST["name"] )     ? $_REQUEST["name"]     : "";
		$object_label    = isset ( $_REQUEST["label"] )    ? $_REQUEST["label"]    : "";
		$object_asset_no = isset ( $_REQUEST["asset_no"] ) ? $_REQUEST["asset_no"] : "";
		$taglist         = isset ( $_REQUEST["taglist"] )  ? $_REQUEST["taglist"]  : array();
		global $dbxlink;
		$dbxlink->beginTransaction();
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
			array
			(
			'has_problems' => $_REQUEST['has_problems'],
			),
			array('id' => $object_id)
		);

		$dbxlink->commit();
		echo json_encode(array("result" => $object_id));
		
		return;

	// update_object
	// api.php?method=update_object&oldname=apitest&newname=apitest2&has_problems=yes
	case "update_object":
		require_once('inc/init.php');

		$taglist = genericAssertion ('taglist', 'array0');
	
		$id = getEntityIdByName("object", $_REQUEST["oldname"]);
		if($id < 0) {
			echo json_encode(array("error" => "In racktables the object ". $_REQUEST["oldname"] . "is not existed."));
			return;
		}

		$object = spotEntity("object", $id);
	
		global $dbxlink;
		$dbxlink->beginTransaction();
		commitUpdateObject
		(
			$object['id'],
			$_REQUEST['newname'],
			$ojbect['label'],
			$_REQUEST['has_problems'],
			$object['asset_no'],
			$object['comment']
		);	
		$dbxlink->commit();
		rebuildTagChainForEntity ('object', $object["id"], buildTagChainFromIds ($taglist), TRUE);
		
		echo json_encode(array("result" => $object["id"]));
		return;

	// api.php?method=update_object_status&names[]=test&has_problems=yes
	case 'update_object_status':
		require_once 'inc/init.php';

		foreach($_REQUEST['names'] as $name) {
			usePreparedUpdateBlade
			(
				'Object',
				array
				(
					'has_problems' => $_REQUEST['has_problems'],
				),
				array('name' => $name)
			);
		}

		echo json_encode(array("result" => 'OK'));

		return;


	// delete_object
	// api.php?method=my_delete_object&name=test
	case "my_delete_object":
		require_once 'inc/init.php';
		
		$id = getEntityIdByName("object", $_REQUEST["name"]);
		if($id < 0) {
			echo json_encode(array("error" => "In racktables the object ". $_REQUEST["oldname"] . "is not existed."));
			return;
		}
		
		$object = spotEntity("object", $id);

		// determine racks the object is in
		$racklist = getResidentRacksData ($object["id"], FALSE);
		commitDeleteObject ($object["id"]);
		foreach ($racklist as $rack_id)
			usePreparedDeleteBlade ('RackThumbnail', array ('rack_id' => $rack_id));

		// delete items in the Object
		usePreparedDeleteBlade ('item_information', array ('objectid' => $id));

		echo json_encode(array("result" => $object["id"]));
		return;

	// get_rackspace_cell
	// api.php?method=get_rack_cell&rack_id=99&rack_name=test
	case "get_rack_cell":
		require_once('inc/init.php');
		
		$rack = null;
		
		if(isset($_REQUEST["rack_id"])) {
			$rack = spotEntity("rack", $_REQUEST["rack_id"]);
		} else {
			$id = getEntityIdByName("rack", $_REQUEST["rack_name"]);

			$rack = spotEntity("rack", $id);
		}
		if(!isset($rack["id"])) {
			echo json_encode(array("error" => "In racktables the rack ". $_REQUEST["name"] . "is not existed."));
			return;
		}

		amplifyCell ($rack);

		echo json_encode($rack);

		return;

	// api.php?method=update_object_ip&object_name=test&ip[]=192.168.10.1&ip[]=192.168.10.2
	case "update_object_ip":
		require_once('inc/init.php');

		$object_id = getEntityIdByName("object", $_REQUEST["object_name"]);

		if($object_id < 0) {
			echo json_encode(array("error", "In racktables the object " . $_REQUEST["object_name"] ."is not existed."));
			return;
		}
		
		$allocs = getObjectIPAllocations($object_id);
		$current_ips = array();	
		foreach($allocs as $alloc) {
			$ip = $alloc["addrinfo"]["ip"];
			$current_ips[$ip] = $ip;
		}
		
		$ips = $_REQUEST["ip"];
		// delete already exists IP
		$ips = array_flip(array_flip($ips));

		$result = array();
		$no_exists = array();
		foreach($ips as $ip) {
			if(in_array($ip, array_values($current_ips))) {
				// already exists IP
				unset($current_ips[$ip]);
				array_push($result, $ip);
			} else {
				// new IP
				$ip_bin = ip_parse($ip);
				if(null == getIPAddressNetworkId ($ip_bin)) {
					// ip is not exists
					array_push($no_exists, $ip);
					continue;
				}
				
				bindIPToObject($ip_bin, $object_id, "", "");
				array_push($result, $ip);
			}
		}
	
		// left is deleted
		foreach($current_ips as $ip) {
			$ip_bin = ip_parse($ip);
			unbindIPFromObject($ip_bin, $object_id);
		}

		echo json_encode(array("result" => $result, "no_exists" => $no_exists));
		
		return;

	# racktables rack offset
	// api.php?method=update_rack_position&row_id=2&rack_id=99&pos_x=25&pos_y=200&pos_z=25
	case "update_rack_position":
		require_once('inc/init.php');
		
		$rack_id = $_REQUEST["rack_id"];
			
		$rack = spotEntity("rack", $rack_id);
		if(!isset($rack["id"])) {
			// if rack is not exists, return
			echo json_encode(array("error" => "rack is not exists."));
			return;
		}

		$query = "select rack_id from rack_position where rack_id = '$rack_id'";
		$result = usePreparedSelectBlade($query);
	
		$flag = false;
		foreach($result as $key => $value) {
			$flag = true;
			break;
		}
		
		if($flag) {
			usePreparedUpdateBlade
			(
				'rack_position',
				array
				(
					'row_id' => $_REQUEST['row_id'],
					'pos_x' => $_REQUEST['pos_x'],
					'pos_y' => $_REQUEST['pos_y'],
					'pos_z' => $_REQUEST['pos_z'],
				),
				array('rack_id' => $rack_id)
			);

		} else {
			usePreparedInsertBlade
			(
				'rack_position',
				array
				(
					'row_id' => $_REQUEST['row_id'],
					'rack_id' => $rack_id,
					'pos_x' => $_REQUEST['pos_x'],
					'pos_y' => $_REQUEST['pos_y'],
					'pos_z' => $_REQUEST['pos_z'],
				)
			);
		}

		return;	

	// api.php?method=update_room_size&row_id=2&width=20&depth=2.5&height=20
	case 'update_room_size':
		require_once('inc/init.php');
		
		$width = $_REQUEST['width'];
		$depth = $_REQUEST['depth'];
		$height = $_REQUEST['height'];

		if($width < 4 || $depth < 4 || $height < 1 ) {
			return;
		}
		
		$row_id = $_REQUEST["row_id"];

		$query = "select width, depth, height from room_size where row_id = '$row_id'";
		$result = usePreparedSelectBlade($query);
		$flag = false;
		foreach($result as $key => $value) {
			$flag = true;
			break;
		}

		if($flag) {
			usePreparedUpdateBlade
			(
				'room_size',
				array
				(
					'width' => $width,
					'depth' => $depth,
					'height' => $height,
				),
				array("row_id" => $row_id)
			);

		} else {
			usePreparedInsertBlade
			(
				'room_size',
				array
				(
					'row_id' => $row_id,
					'width' => $width,
					'depth' => $depth,
					'height' => $height,
				)
			);
		}
		
		return;

	// api.php?method=add_rack_with_position&name=test4&row_id=2&height1=42&asset_no=&type=rack&pos_x=25&pos_y=200&pos_z=25
	case 'add_rack_with_position':
		require_once('inc/init.php');
		require_once 'inc/ophandlers.php';

		$_REQUEST['got_data'] = true;

		addRack();

		redirectUser('http://'.$_SERVER['HTTP_HOST'].'/racktables/index.php?page=top');

		return;

	// api.php?method=add_item&itemid=99&object_name=test&hostid=2&name=item1&type=0&key_=key1&interfaceid=99&value_type=0&data_type=3&units=s&multiplier=0&formula=10&delay=30
	case 'add_item':
		require_once('inc/init.php');

		$objectid = getEntityIdByName('object', $_REQUEST['object_name']);
		if($objectid < 0) {
			echo json_encode(array('error' => 'In racktables the object '. $_REQUEST['object_name'] . 'is not existed.'));
			return;
		}

		# insert into DB
		usePreparedInsertBlade
		(
			'item_information',
			array
			(
				'itemid' => $_REQUEST['itemid'],
				'objectid' => $objectid,
				'hostid' => $_REQUEST['hostid'],
				'name' => $_REQUEST['name'],
				'description' => $_REQUEST['description'],
				'key_' => $_REQUEST['key_'],
				'interfaceid' => $_REQUEST['interfaceid'],
				'delay' => $_REQUEST['delay'],
				'history' => $_REQUEST['history'],
				'status' => $_REQUEST['status'],
				'type' => $_REQUEST['type'],
				'snmp_community' => $_REQUEST['snmp_community'],
				'snmp_oid' => $_REQUEST['snmp_oid'],
				'value_type' => $_REQUEST['value_type'],
				'trapper_hosts' => $_REQUEST[''],
				'port' => $_REQUEST['trapper_hosts'],
				'units' => $_REQUEST['units'],
				'multiplier' => $_REQUEST['multiplier'],
				'delta' => $_REQUEST['delta'],
				'snmpv3_contextname' => $_REQUEST['snmpv3_contextname'],
				'snmpv3_securityname' => $_REQUEST['snmpv3_securityname'],
				'snmpv3_securitylevel' => $_REQUEST['snmpv3_securitylevel'],
				'snmpv3_authprotocol' => $_REQUEST['snmpv3_authprotocol'],
				'snmpv3_authpassphrase' => $_REQUEST['snmpv3_authpassphrase'],
				'snmpv3_privprotocol' => $_REQUEST['snmpv3_privprotocol'],
				'snmpv3_privpassphrase' => $_REQUEST['snmpv3_privpassphrase'],
				'formula' => $_REQUEST['formula'],
				'trends' => $_REQUEST['trends'],
				'logtimefmt' => $_REQUEST['logtimefmt'],
				'valuemapid' => $_REQUEST['valuemapid'],
				'delay_flex' => $_REQUEST['delay_flex'],
				'authtype' => $_REQUEST['authtype'],
				'username' => $_REQUEST['username'],
				'password' => $_REQUEST['password'],
				'publickey' => $_REQUEST['publickey'],
				'privatekey' => $_REQUEST['privatekey'],
				'params' => $_REQUEST['params'],
				'ipmi_sensor' => $_REQUEST['ipmi_sensor'],
				'data_type' => $_REQUEST['data_type'],
				'inventory_link' => $_REQUEST['inventory_link']
			)
		);

		echo json_encode(array('result' => $_REQUEST['itemid']));
		
		return;

	// api.php?method=update_item&itemid=99&name=item1&type=0&key_=key1&interfaceid=99&value_type=0&data_type=3&units=s&multiplier=0&formula=10&delay=30
	case 'update_item':
		require_once('inc/init.php');
		# parameters check
		assertUIntArg('itemid', true);

		# update DB
		usePreparedUpdateBlade
		(
			'item_information',
			array
			(
				'name' => $_REQUEST['name'],
				'description' => $_REQUEST['description'],
				'key_' => $_REQUEST['key_'],
				'interfaceid' => $_REQUEST['interfaceid'],
				'delay' => $_REQUEST['delay'],
				'history' => $_REQUEST['history'],
				'status' => $_REQUEST['status'],
				'type' => $_REQUEST['type'],
				'snmp_community' => $_REQUEST['snmp_community'],
				'snmp_oid' => $_REQUEST['snmp_oid'],
				'value_type' => $_REQUEST['value_type'],
				'trapper_hosts' => $_REQUEST['trapper_hosts'],
				'port' => $_REQUEST['port'],
				'units' => $_REQUEST['units'],
				'multiplier' => $_REQUEST['multiplier'],
				'delta' => $_REQUEST['delta'],
				'snmpv3_contextname' => $_REQUEST['snmpv3_contextname'],
				'snmpv3_securityname' => $_REQUEST['snmpv3_securityname'],
				'snmpv3_securitylevel' => $_REQUEST['snmpv3_securitylevel'],
				'snmpv3_authprotocol' => $_REQUEST['snmpv3_authprotocol'],
				'snmpv3_authpassphrase' => $_REQUEST['snmpv3_authpassphrase'],
				'snmpv3_privprotocol' => $_REQUEST['snmpv3_privprotocol'],
				'snmpv3_privpassphrase' => $_REQUEST['snmpv3_privpassphrase'],
				'formula' => $_REQUEST['formula'],
				'trends' => $_REQUEST['trends'],
				'logtimefmt' => $_REQUEST['logtimefmt'],
				'valuemapid' => $_REQUEST['valuemapid'],
				'delay_flex' => $_REQUEST['delay_flex'],
				'authtype' => $_REQUEST['authtype'],
				'username' => $_REQUEST['username'],
				'password' => $_REQUEST['password'],
				'publickey' => $_REQUEST['publickey'],
				'privatekey' => $_REQUEST['privatekey'],
				'params' => $_REQUEST['params'],
				'ipmi_sensor' => $_REQUEST['ipmi_sensor'],
				'data_type' => $_REQUEST['data_type'],
				'inventory_link' => $_REQUEST['inventory_link']
			),
			array('itemid' => $_REQUEST['itemid'])
		);

		echo json_encode(array('result' => $_REQUEST['itemid']));
		
		return;

	case 'update_item_status':
		require_once('inc/init.php');
		foreach($_REQUEST['itemids'] as $itemid) {
			usePreparedUpdateBlade
			(
				'item_information',
				array('status' => $_REQUEST['status']),
				array('itemid' => $itemid)
			);
		}

		echo json_encode(array('result' => 'OK'));

		return;

	// api.php?method=delete_item&itemid[]=99&itemid[]=88
	case 'delete_item':
		require_once('inc/init.php');
		# parameters check
		#assertUIntArg('itemid', true);

		$itemids = $_REQUEST['itemid'];

		# delete item information in DB
		foreach($itemids as $itemid)
			usePreparedDeleteBlade ('item_information', array ('itemid' => $itemid));

		echo json_encode(array('result' => $_REQUEST['itemid']));
		
		return;
		
	# END
	##################################################################################################################

        case 'get_status':
		require_once 'inc/init.php';
                require_once 'inc/config.php'; // for CODE_VERSION
                global $configCache;
                sendAPIResponse( array( 'site_conf'    => $configCache,
                                        'code_version' => CODE_VERSION ) );
                break;
        // get overall 8021q status
        //    UI equivalent: /index.php?page=8021q
        //    UI handler: renderVLANIPLinks()
        case 'get_8021q':
                require_once 'inc/init.php';
                // get data for all VLAN domains: ID, name, VLAN count, switch count, ipv4net count, port count
                $vdlist = getVLANDomainStats();
                // TODO: also get deploy queues and switch templates?
                sendAPIResponse($vdlist);
                break;
        // gets user-defined tags as a list
        //    UI equivalent: none
        //    UI handler: none
        case 'get_taglist':
	        require_once 'inc/init.php';
                // just display the global
                sendAPIResponse($taglist);
                break;
        // gets user-defined tags as a tree
        //    UI equivalent: /index.php?page=tagtree&tab=default
        //    UI handler: none
        case 'get_tagtree':
	        require_once 'inc/init.php';
                // just display the global
                sendAPIResponse($tagtree);
                break;
        // get overall IPv4 space
        //    UI equivalent: /index.php?page=ipv4space
        //    UI handler: renderIPSpace() (realm == 'ipv4net')
        case 'get_ipv4space':
                require_once 'inc/init.php';
                $ipv4space = listCells ('ipv4net');
                // TODO: probably add hierarchy?
                // TODO: also show capacity for each network
                // TODO: also show router for each network
                sendAPIResponse($ipv4space);
                break;
        
        // get a single IPv4 network
        //    UI equivalent: /index.php?page=ipv4net&id=8
        //    UI handler: renderIPNetwork()
        case 'get_ipv4network':
	        require_once 'inc/init.php';
                genericAssertion ('network_id', 'uint0');
                $range = spotEntity ('ipv4net', $_REQUEST['network_id']);
                loadIPAddrList ($range);
                sendAPIResponse($range);
                break;
        // get all VLAN domains
        //    UI equivalent: /index.php?page=
        //    UI handler: ()
        case 'get_vlan_domains':
	        require_once 'inc/init.php';
                sendAPIResponse(getVLANDomainStats());
                break;
        // get VLANs in one domain
        //    UI equivalent: /index.php?page=
        //    UI handler: ()
        case 'get_vlan_domain':
	        require_once 'inc/init.php';
                assertUIntArg ('domain_id');
                $domain = getDomainVLANs($_REQUEST['domain_id']);
                if (!$domain)
                {
                        throw new EntityNotFoundException('domain_id', $_REQUEST['domain_id']);
                }
                sendAPIResponse($domain);
                break;
        // get info for one VLAN
        //    UI equivalent: /index.php?page=
        //    UI handler: ()
        case 'get_vlan':
	        require_once 'inc/init.php';
                assertStringArg ('vlan_ck', TRUE);
                sendAPIResponse(getVLANInfo($_REQUEST['vlan_ck']));
                break;
        // get overall rackspace status
        //    UI equivalent: /index.php?page=rackspace
        //    UI handler: renderRackspace()
        case 'get_rackspace':
		require_once 'inc/init.php';
                // taken straight from interface.php::renderRackspace()
                $found_racks = array();
                $rows = array();
                $rackCount = 0;
                foreach (getAllRows() as $row_id => $rowInfo)
                {
                        $rackList = listCells ('rack', $row_id);
                        $found_racks = array_merge ($found_racks, $rackList);
                        $rows[] = array (
                                         'location_id'   => $rowInfo['location_id'],
                                         'location_name' => $rowInfo['location_name'],
                                         'row_id'        => $row_id,
                                         'row_name'      => $rowInfo['name'],
                                         'racks'         => $rackList
                                         );
                        $rackCount += count($rackList);
                }
                sendAPIResponse($rows);
                break;
        // get info for a rack
        //    UI equivalent: /index.php?page=rack&rack_id=967
        //    UI handler: renderRackPage()
        case 'get_rack':
                require_once 'inc/init.php';
                assertUIntArg ("rack_id", TRUE);
                $rackData = spotEntity ('rack', $_REQUEST['rack_id']);
                amplifyCell ($rackData);
                sendAPIResponse( $rackData );
                break;
        // get info for a single IP address
        //    UI equivalent: /index.php?page=ipaddress&hl_object_id=911&ip=10.200.1.66
        //    UI handler: renderIPAddress()
        case 'get_ipaddress':
                require_once 'inc/init.php';
                assertStringArg ("ip", TRUE);
                // basic IP address info
                $address = getIPAddress (ip_parse ( $_REQUEST['ip'] ));
                $address['network_id'] = getIPAddressNetworkId( ip_parse( $_REQUEST['ip'] ));
                // TODO: add some / all of the following data
                // virtual services 
                //  ! empty $address['vslist']
                //      foreach $address['vslist'] as $vs_id
                //         $blah = spotEntity ('ipv4vs', $vs_id)
                // RS pools
                // allocations
                // departing NAT rules
                // arriving NAT rules
                sendAPIResponse($address);
                break;
        // get one object
        //    UI equivalent: /index.php?page=object&object_id=909
        //    UI handler: renderObject()
	case 'get_object':
		require_once 'inc/init.php';
                assertUIntArg ("object_id", TRUE);
                $info = spotEntity ('object', $_REQUEST['object_id']);
                amplifyCell ($info);
                // optionally get attributes
                if (isset ($_REQUEST['include_attrs']))
                {
                        // return the attributes in an array keyed on 'name', unless otherwise requested
                        $key_attrs_on = 'name';
                        if (isset ($_REQUEST['key_attrs_on']))
                                $key_attrs_on = $_REQUEST['key_attrs_on'];
                        $attrs = array();
                        foreach (getAttrValues ( $_REQUEST['object_id'] ) as $record)
                        {
                          // check that the key exists for this record. we'll assume the default 'name' is always ok
                          if (! isset ($record[ $key_attrs_on ]))
                          {
                                  throw new InvalidRequestArgException ('key_attrs_on',
                                                                        $_REQUEST['key_attrs_on'],
                                                                        'requested keying value not set for all attributes' );
                          }
                          // include only attributes with value set, unless requested via include_unset_attrs param
                          // TODO: include_unset_attrs=0 currently shows the attributes...not intuitive
                          if ( strlen ( $record['value'] ) or isset( $_REQUEST['include_unset_attrs'] ) )
                                  $attrs[ $record[ $key_attrs_on ] ] = $record;
                        }
                        $info['attrs'] = $attrs;
                }
                // TODO: remove ip_bin data from response, or somehow encode in UTF-8 -safe format
                //       note that get_ipaddress doesn't error, even though ip_bin key is present
                sendAPIResponse($info);
		break;
        // get the location of an object
        //    UI equivalent: /index.php?page=object&tab=rackspace&object_id=1013
        //    UI handler: renderRackSpaceForObject()
	case 'get_object_allocation':
		require_once 'inc/init.php';
                assertUIntArg ("object_id", TRUE);
                // get physical allocations
                $racksData = getResidentRacksData ($_REQUEST['object_id']);
                // get zero-U allocations
                $zeroURacks = array();
                $objectParents = getEntityRelatives('parents', 'object', $_REQUEST['object_id']);
                foreach ($objectParents as $parentData)
                  if ($parentData['entity_type'] == 'rack')
                    array_push($zeroURacks, $parentData['entity_id']);
                // TODO: possibly just pull out the atoms the server is in?
                sendAPIResponse( array( 'racks' => $racksData, 'zerou_racks' => $zeroURacks ) );
                break;
        // update where an object is installed in rackspace
        //    UI equivalent: submitting form at /index.php?page=object&tab=rackspace&object_id=1013
        //    UI handler: updateObjectAllocation()
        case 'update_object_allocation':
		require_once 'inc/init.php';
                assertUIntArg ('object_id');
                $object_id = $_REQUEST['object_id'];
                global $remote_username, $loclist, $dbxlink;
                $zeroURacksOld = array();
                $allocationsOld = array();
                $zeroURacksNew = array();
                $allocationsNew = array();
                $changecnt = 0;
                // determine current zero-u allocations
                foreach ( getEntityRelatives('parents', 'object', $object_id) as $parentData)
                  if ($parentData['entity_type'] == 'rack')
                    // this is exactly as in updateObjectAllocation(), but it means there can
                    // only ever be one rack that an object is zero-U mounted in
                    $zeroURacksOld[] = $parentData['entity_id'];
                // determine current "normal" allocations
                foreach ( array_keys( getResidentRacksData ( $object_id ) ) as $rack_id )
                {
                  $allocationsOld[] = $rack_id;
                }
                // get the object's new allocations from the request parameters (might not be any)
                if ( isset( $_REQUEST['allocate_to'] ) ) {
                  foreach ( $_REQUEST['allocate_to'] as $allocation )
                  {
                    // zero-U
                    if ( preg_match( '/^zerou_(\d+)$/', $allocation, $matches ) ) {
                      $rack_id = $matches[1];
                      $zeroURacksNew[] = $rack_id;
                    // "normal"
                    } elseif ( preg_match( '/^atom_(\d+)_(\d+)_(\d+)$/', $allocation, $matches ) ) {
                      $rack_id  = $matches[1];
                      $position = $matches[2];
                      $locidx   = $matches[3];
                      $allocationsNew[$rack_id][$position][$locidx] = TRUE;
                    // unexpected
                    } else {
                      throw new InvalidArgException ('allocate_to[]', $allocation,
                                                     'invalid argument format, must be "zerou_<RACK>" or ' .
                                                     '"atom_<RACK>_<UNIT>_<ATOM>"');
                    }
                  }
                }
                // validate new zero-U allocations (exception thrown if the rack doesn't exist)
                foreach ( $zeroURacksNew as $rack_id )
                  spotEntity('rack', $rack_id);
                // validate new normal allocations
                foreach ( $allocationsNew as $rack_id => $rack_alloc)
                {
                  $rackData = spotEntity ('rack', $rack_id);
                  foreach ( $rack_alloc as $position => $pos_atoms )
                  {
                    foreach ( array_keys($pos_atoms) as $locidx )
                    {
                      if ( !isset( $loclist[$locidx] ) )
                      {
                        throw new InvalidArgException ('allocate_to[]', "atom_$rack_id_$position_$locidx",
                                                       "invalid argument: $locidx is too deep/shallow for the rack");
                      }
                      if ( $position > $rackData['height'] or $position < 1 )
                      {
                        throw new InvalidArgException ('allocate_to[]', "atom_$rack_id_$position_$locidx",
                                                       'invalid argument: rack is not that high/low');
                      }
                    }
                  }
                }
                $workingRacksData = array();
                // iterate over all involved racks (old and new) to get the detailed rack data
                // also deal with zero-U allocations and de-allocations
                foreach ( array_unique( array_merge( $allocationsOld, $zeroURacksNew, array_keys($allocationsNew) ) ) as $rack_id )
                {
                  if (!isset ($workingRacksData[$rack_id]))
                  {
                    $rackData = spotEntity ('rack', $rack_id);
                    amplifyCell ($rackData);
                    $workingRacksData[$rack_id] = $rackData;
                  }
                  // It's zero-U allocated to this rack in the API request, but not in the DB.  Mount it.
                  if ( in_array($rack_id, $zeroURacksNew) && !in_array($rack_id, $zeroURacksOld) )
                  {
                    $changecnt++;
                    error_log("zero-u mounting object id: $object_id from rack id: $rack_id");
                    commitLinkEntities ('rack', $rack_id, 'object', $object_id);
                  }
                  // It's not zero-U allocated to this rack in the API request, but it is in the DB.  Unmount it.
                  if ( !in_array($rack_id, $zeroURacksNew) && in_array($rack_id, $zeroURacksOld) )
                  {
                    $changecnt++;
                    error_log("zero-u UN- mounting object id: $object_id from rack id: $rack_id");
                    commitUnlinkEntities ('rack', $rack_id, 'object', $object_id);
                  }
                }
                foreach ($workingRacksData as &$rd)
                  applyObjectMountMask ($rd, $object_id);
                // quick DB operation to save old data for logging
                $oldMolecule = getMoleculeForObject ($object_id);
                foreach ($workingRacksData as $rack_id => $rackData)
                {
                  $rackchanged = FALSE;
                  $dbxlink->beginTransaction();
                  for ($position = $rackData['height']; $position > 0; $position--)
                  {
                    for ($locidx = 0; $locidx < 3; $locidx++)
                    {
                      $atom = $loclist[$locidx];
                      // atom can't be assigned to, skip.
                      //     XXX: maybe should warn if attempted? (UI similarly ignores)
                      if ($rackData[$position][$locidx]['enabled'] != TRUE)
                        continue;
                      // F => free, can be assigned to
                      // T => taken, has something in it, can't be assigned to
                      // U => unusable, has problems
                      // A => set aside, can't be assigned to
                      // W => ???
                      $state = $rackData[$position][$locidx]['state'];
                      // atom has something in it already. see if it's this object
                      if ( $state == 'T' )
                      {
                        // some other object is there.
                        //     TODO: should probably throw an exception
                        if ( $rackData[$position][$locidx]['object_id'] != $object_id )
                          continue;
                        // this object was in there, and still is
                        elseif ( isset( $allocationsNew[$rack_id][$position][$locidx] ) )
                          continue;
                        // this object was in there, but isn't anymore
                        else
                        {
                          error_log("removing assignment for object id: $object_id ($rack_id, $position, $atom)");
                          usePreparedDeleteBlade ('RackSpace', array ('rack_id' => $rack_id,
                                                                      'unit_no' => $position,
                                                                      'atom'    => $atom));
                          $rackchanged = TRUE;
                        }
                      }
                      // atom is free, can be assigned to
                      elseif ( $state == 'F' )
                      {
                        // allocate the space to the object
                        if ( isset( $allocationsNew[$rack_id][$position][$locidx] ) )
                        {
                          error_log("new assignment for object id: $object_id ($rack_id, $position, $atom)");
                          usePreparedInsertBlade ('RackSpace', array ('rack_id'   => $rack_id,
                                                                      'unit_no'   => $position,
                                                                      'atom'      => $atom,
                                                                      'state'     => 'T',
                                                                      'object_id' => $object_id ));
                          $rackchanged = TRUE;
                        }
                      }
                    } // each atom
                  } // each position
                  if ($rackchanged)
                  {
                    // remove the thumbnail and commit the change
                    usePreparedDeleteBlade ('RackThumbnail', array ('rack_id' => $rack_id));
                    $dbxlink->commit();
                    $changecnt++;
                  }
                  else
                  {
                    $dbxlink->rollBack();
                  }
                }
                if ($changecnt)
                {
                  // Log a record.
                  $newMolecule = getMoleculeForObject ($object_id);
                  usePreparedInsertBlade
                    (
                     'MountOperation',
                     array
                     (
                      'object_id' => $object_id,
                      'old_molecule_id' => count ($oldMolecule) ? createMolecule ($oldMolecule) : NULL,
                      'new_molecule_id' => count ($newMolecule) ? createMolecule ($newMolecule) : NULL,
                      'user_name' => $remote_username,
                      'comment' => 'updated via API',
                      )
                     );
                }
                // TODO: add metadata on updates that took place
		redirectUser($_SERVER['SCRIPT_NAME'] . "?method=get_object_allocation&object_id=$object_id");
                break;
        // link two entities (most often used for server / chassis mounting)
        //    UI equivalent: /index.php?module=redirect&page=object&tab=edit&op=linkEntities& ...
        //    UI handler: linkEntities()
        case 'link_entities':
	        require_once 'inc/init.php';
                assertStringArg ('child_entity_type', TRUE);
                assertUIntArg ('child_entity_id', TRUE);
                assertStringArg ('parent_entity_type', TRUE);
                assertUIntArg ('parent_entity_id', TRUE);
                usePreparedInsertBlade
                  (
                   'EntityLink',
                   array
                   (
                    'parent_entity_type' => $_REQUEST['parent_entity_type'],
                    'parent_entity_id' => $_REQUEST['parent_entity_id'],
                    'child_entity_type' => $_REQUEST['child_entity_type'],
                    'child_entity_id' => $_REQUEST['child_entity_id'],
                    )
                   );
                sendAPIResponse( array(), array( 'message' => 'entities linked successfully',
                                                 'parent_entity_id' => $_REQUEST['parent_entity_id'],
                                                 'child_entity_id' => $_REQUEST['child_entity_id'] ) );
                break;
        // add one object
        //    UI equivalent: submitting form at /index.php?page=depot&tab=addmore
        //    UI handler: addMultipleObjects()
        case 'add_object':
		require_once 'inc/init.php';
                // only the Type ID is required at creation -- everything else can be set later
                assertUIntArg ("object_type_id", TRUE);
                $object_type_id = $_REQUEST['object_type_id'];
                // virtual objects don't have labels or asset tags
                if (isset ($_REQUEST['virtual_objects']))
                {
                        $_REQUEST["object_label"] = '';
                        $_REQUEST["object_asset_no"] = '';
                }
                $object_name     = isset ( $_REQUEST['object_name'] )     ? $_REQUEST['object_name']     : '';
                $object_label    = isset ( $_REQUEST['object_label'] )    ? $_REQUEST['object_label']    : '';
                $object_asset_no = isset ( $_REQUEST['object_asset_no'] ) ? $_REQUEST['object_asset_no'] : '';
                $taglist         = isset ( $_REQUEST['taglist'] )         ? $_REQUEST['taglist']         : array();
                $object_id = commitAddObject
                (
                        $object_name,
                        $object_label,
                        $object_type_id,
                        $object_asset_no,
                        $taglist
                );
	        redirectUser($_SERVER['SCRIPT_NAME'] . "?method=get_object&object_id=$object_id");
		break;
        // edit an existing object
        //    UI equivalent: submitting form at /index.php?page=object&tab=edit&object_id=911
        //    UI handler: updateObject()
        case 'edit_object':
		require_once 'inc/init.php';
                // check required args
                genericAssertion ('object_id', 'uint0');
                genericAssertion ('object_name', 'string0');
                genericAssertion ('object_label', 'string0');
                genericAssertion ('object_asset_no', 'string0');
                genericAssertion ('object_comment', 'string0');
                genericAssertion ('object_type_id', 'uint'); // TODO: really required for API?
                $object_id = $_REQUEST['object_id'];
                // make this transactional, so we can double check the whole upate before committing at the end
                global $dbxlink, $sic;
                $dbxlink->beginTransaction();
                // TODO: may need to wrap this in a try/catch block to redirect to API exception response
                commitUpdateObject
                (
                        $object_id,
                        $_REQUEST['object_name'],
                        $_REQUEST['object_label'],
                        isCheckSet ('object_has_problems', 'yesno'), // not really a checkbox, but easier than writing it myself
                        $_REQUEST['object_asset_no'],
                        $_REQUEST['object_comment']
                );
                // update optional attributes
                // get the valid / old values for the object
                $oldvalues = getAttrValues ($object_id);
                // look for values to be updated.
                //   note: in UI, a "num_attrs" input is used to loop and search for update fields
                foreach ( $_REQUEST as $name => $value )
                {
                        if ( preg_match( '/^attr_(\d+)$/', $name, $matches ) )
                        {
                                $attr_id = $matches[1];
                                // make sure the attribute actually exists in the object
                                if (! array_key_exists ($attr_id, $oldvalues))
                                        throw new InvalidRequestArgException ('attr_id', $attr_id, 'malformed request');
                                // convert date arguments
                                if ('date' == $oldvalues[$attr_id]['type']) {
                                        // if given date looks like UNIX timestamp, leave as-is,
                                        // otherwise try to parse it just like the UI
                                        if ( preg_match( '/^\d{10}$/', $value ) ) {
                                                error_log( "assuming argument '$value' for attribute $attr_id is a UNIX timestamp" );
                                        } else {
                                                assertDateArg ( $name, TRUE);
                                                if ($value != '')
                                                        $value = strtotime ($value);
                                        }
                                }
                                // Delete attribute and move on, when the field is empty or if the field
                                // type is a dictionary and it is the "--NOT SET--" value of 0.
                                if ($value == '' || ($oldvalues[$attr_id]['type'] == 'dict' && $value == 0))
                                {
                                        commitUpdateAttrValue ($object_id, $attr_id);
                                        continue;
                                }
                                assertStringArg ( $name );
                                // normalize dict values
                                switch ($oldvalues[$attr_id]['type'])
                                {
                                        case 'uint':
                                        case 'float':
                                        case 'string':
                                        case 'date':
                                                $oldvalue = $oldvalues[$attr_id]['value'];
                                                break;
                                        case 'dict':
                                                $oldvalue = $oldvalues[$attr_id]['key'];
                                                break;
                                        default:
                                }
                                // skip noops
                                if ($value === $oldvalue)
                                        continue;
                                // finally update our value
                                error_log( "update attribute ID $attr_id from $oldvalue to $value" );
                                commitUpdateAttrValue ($object_id, $attr_id, $value);
                        }
                }
                // see if we also need to update the object type
                $object = spotEntity ('object', $object_id);
                if ($sic['object_type_id'] != $object['objtype_id'])
                {
                        error_log( "object type id for object $object_id will be changed from " . $object['objtype_id'] . ' to ' . $sic['object_type_id'] );
                        // check that the two types are compatible
                        if (! array_key_exists ($sic['object_type_id'], getObjectTypeChangeOptions ($object_id)))
                                throw new InvalidRequestArgException ('new type_id', $sic['object_type_id'], 'incompatible with requested attribute values');
                        usePreparedUpdateBlade ('RackObject', array ('objtype_id' => $sic['object_type_id']), array ('id' => $object_id));
                }
                // Invalidate thumb cache of all racks objects could occupy.
                foreach (getResidentRacksData ($object_id, FALSE) as $rack_id)
                        usePreparedDeleteBlade ('RackThumbnail', array ('rack_id' => $rack_id));
                // ok, now we're good
                $dbxlink->commit();
                // redirect to the get_object URL for the edited object
                redirectUser( $_SERVER['SCRIPT_NAME'] . "?method=get_object&object_id=$object_id" );
                break;
        // update user-defined tags for an object
        //    UI equivalent: /index.php?module=redirect&page=object&tab=tags&op=saveTags
        //    UI handler: saveEntityTags()
        case 'update_object_tags':
	        require_once 'inc/init.php';
                genericAssertion ('object_id', 'uint0');
                $tags = isset ($_REQUEST['taglist']) ? $_REQUEST['taglist'] : array();
                $num_tags = count($tags);
                rebuildTagChainForEntity ('object', $_REQUEST['object_id'], buildTagChainFromIds ($tags), TRUE);
                sendAPIResponse( array(), array( 'message' => 'updated tags successfully', 'object_id' => $_REQUEST['object_id'], 'num_tags' => $num_tags ) );
                break;
        // sync a switch or PDU's ports using SNMP
        //    UI equivalent: /index.php?module=redirect&page=object&tab=snmpportfinder
        //    UI handler: querySNMPData()
        case 'snmp_sync_object':
	        require_once 'inc/init.php';
	        require_once 'inc/snmp.php';
                global $log_messages;
                genericAssertion ('object_id', 'uint0');
                genericAssertion ('ver', 'uint');
                $object_id = $_REQUEST['object_id'];
                $snmpsetup = array ();
                switch ($_REQUEST['ver']){
                case 1:
                case 2:
                        genericAssertion ('community', 'string');
                        $snmpsetup['community'] = $_REQUEST['community'];
                        break;
                case 3:
                        assertStringArg ('sec_name');
                        assertStringArg ('sec_level');
                        assertStringArg ('auth_protocol');
                        assertStringArg ('auth_passphrase', TRUE);
                        assertStringArg ('priv_protocol');
                        assertStringArg ('priv_passphrase', TRUE);
                        $snmpsetup['sec_name'] = $_REQUEST['sec_name'];
                        $snmpsetup['sec_level'] = $_REQUEST['sec_level'];
                        $snmpsetup['auth_protocol'] = $_REQUEST['auth_protocol'];
                        $snmpsetup['auth_passphrase'] = $_REQUEST['auth_passphrase'];
                        $snmpsetup['priv_protocol'] = $_REQUEST['priv_protocol'];
                        $snmpsetup['priv_passphrase'] = $_REQUEST['priv_passphrase'];
                        break;
                default:
                        throw new InvalidRequestArgException ('ver', $_REQUEST['ver']);
                }
                $snmpsetup['version'] = $_REQUEST['ver'];
                doSNMPmining ($object_id, $snmpsetup);
                $snmp_result = '';
                // look inside $log_messages -- fragile but all we
                // have since doSNMPminint() is meant to return data
                // via the UI
                if (count($log_messages)){
                  $msg = array_shift($log_messages);
                  if (in_array('a', $msg)) {
                    $snmp_result = $msg['a'][0];
                  }
                }
                if ($snmp_result) {
                  sendAPIResponse(array(), array('message'    => "SNMP sync for object id $object_id successful",
                                                 'model_data' => $snmp_result));
                } else {
                  throw new InvalidArgException('(unknown)',
                                                '(unknown)',
                                                "unknown problem syncing object id $object_id via SNMP v$_REQUEST[ver]");
                }
                break;
        // update an object's IP address
        //    UI equivalent: /index.php?page=   ?module=redirect&page=object&tab=ip&op=add
        //    UI handler: addIPAllocation()
        case 'add_object_ip_allocation':
		require_once 'inc/init.php';
                $ip_bin = assertIPArg ('ip');
                assertUIntArg ('object_id');
                // default value for bond_name
                if ( ! isset ($_REQUEST['bond_name']) )
                        $_REQUEST['bond_name'] = '';
                // default value for bond_type
                // note on meanings of on 'bond_type' values:
                //     'regular': Connected
                //     'virtual': Loopback
                //     'shared':  Shared
                //     'router':  Router
                if ( ! isset ($_REQUEST['bond_type']) )
                        $_REQUEST['bond_type'] = 'regular';
                // confirm that a network exists that matches the IP address
                if  (getConfigVar ('IPV4_JAYWALK') != 'yes' and NULL === getIPAddressNetworkId ($ip_bin)) 
                {
                        throw new InvalidRequestArgException ('ip',
                                                              $_REQUEST['ip'],
                                                              'no network covering the requested IP address');
                }
                bindIPToObject ($ip_bin, $_REQUEST['object_id'], $_REQUEST['bond_name'], $_REQUEST['bond_type']);
                redirectUser( $_SERVER['SCRIPT_NAME'] . '?method=get_object&object_id=' . $_REQUEST['object_id'] );
                break;
        // delete an IP address allocation for an object
        //    UI equivalent: /index.php?
        //    UI handler: delIPAllocation()
        case 'delete_object_ip_allocation':
                require_once 'inc/init.php';
                $ip_bin = assertIPArg ('ip');
                assertUIntArg ('object_id');
                // TODO: raise exception if the IP doesn't exist
                unbindIPFromObject ($ip_bin, $_REQUEST['object_id']);
                redirectUser( $_SERVER['SCRIPT_NAME'] . '?method=get_object&object_id=' . $_REQUEST['object_id'] );
                break;
        // add a port to an object
        //    UI equivalent: /index.php?page=
        //    UI handler: addPortForObject()
        case 'add_port':
	        require_once 'inc/init.php';
                assertUIntArg ('object_id');
                assertStringArg ('port_name', TRUE);
                genericAssertion ('port_l2address', 'l2address0');
                genericAssertion ('port_name', 'string');
                $new_port_id = commitAddPort
                (
                       $_REQUEST['object_id'],
                       trim ($_REQUEST['port_name']),
                       $_REQUEST['port_type_id'],
                       trim ($_REQUEST['port_label']),
                       trim ($_REQUEST['port_l2address'])
                );
                sendAPIResponse( array(), array( 'message' => 'port added successfully', 'port_id' => $new_port_id ) );
                break;
        // delete a port from an object
        //    UI equivalent: /index.php?page=
        //    UI handler: tableHandler()
        case 'delete_port':
	        require_once 'inc/init.php';
                assertUIntArg ('object_id');
                assertUIntArg ('port_id');
                // TODO: add confirmation that there is such a port
                usePreparedDeleteBlade ( 'Port', array ( 'id'        => $_REQUEST['port_id'],
                                                         'object_id' => $_REQUEST['object_id'] ) );
                redirectUser( $_SERVER['SCRIPT_NAME'] . '?method=get_object&object_id=' . $_REQUEST['object_id'] );
                break;
        // link a port
        //    UI equivalent: /index.php?module=popup&helper=portlist&port=<<ID>>&in_rack=off&in_rack=on&remote_port=<<ID>>&cable=<<>>&do_link=Link
        //    UI handler: ()
        case 'link_port':
	        require_once 'inc/init.php';
                assertUIntArg ('port');
                assertUIntArg ('remote_port');
                assertStringArg ('cable', TRUE);
                $port_info = getPortInfo ($_REQUEST['port']);
                $remote_port_info = getPortInfo ($_REQUEST['remote_port']);
                $POIFC = getPortOIFCompat();
                // (removed the ability to specify remote and local port types)
                $matches = FALSE;
                foreach ($POIFC as $pair)
                {
                        if ($pair['type1'] == $port_info['oif_id'] && $pair['type2'] == $remote_port_info['oif_id'])
                        {
                                $matches = TRUE;
                                break;
                        }
                }
                if (!$matches)
                {
                        $port_type = $port_info['oif_name'];
                        $remote_port_type = $remote_port_info['oif_name'];
                        throw new InvalidArgException ('remote_port', $_REQUEST['remote_port'],
                                                       "invalid argument: port types $port_type (local) and $remote_port_type (remote) can't be linked");
                }
                linkPorts ($port_info['id'], $remote_port_info['id'], $_REQUEST['cable']);
                sendAPIResponse( array(), array( 'message' => 'ports linked successfully',
                                                 'local_object' => $port_info['object_id'], 'remote_object' => $remote_port_info['object_id'],
                                                 'local_port'   => $_REQUEST['port'],       'remote_port'   => $_REQUEST['remote_port'], ));
                break;
        // unlink a port
        //    UI equivalent: /index.php?module=redirect&op=unlinkPort&port_id=<<ID>>&object_id=<<ID>>&page=object&tab=ports
        //    UI handler: unlinkPort()
        case 'unlink_port':
	        require_once 'inc/init.php';
                assertUIntArg ('port_id');
                commitUnlinkPort ($_REQUEST['port_id']);
                sendAPIResponse( array(), array( 'message' => 'port unlinked successfully', 'port_id' => $_REQUEST['port_id'] ) );
                break;
        // get data on a given port
        //    UI equivalent: none
        //    UI handler: none
        case 'get_port':
	        require_once 'inc/init.php';
                assertUIntArg ('port_id');
                $port_info = getPortInfo ($_REQUEST['port_id']);
                sendAPIResponse($port_info);
                break;
        // delete an object
        //    UI equivalent: /index.php?module=redirect&op=deleteObject&page=depot&tab=addmore&object_id=993
        //                   (typically a link from edit object page)
        //    UI handler: deleteObject()
        case 'delete_object':
		require_once 'inc/init.php';
                assertUIntArg ('object_id');
                // determine racks the object is in
                $racklist = getResidentRacksData ($_REQUEST['object_id'], FALSE);
                commitDeleteObject ($_REQUEST['object_id']);
                foreach ($racklist as $rack_id)
                        usePreparedDeleteBlade ('RackThumbnail', array ('rack_id' => $rack_id));
                // redirect to the depot method
                redirectUser( $_SERVER['SCRIPT_NAME'] . "?method=get_depot" );
                break;
        // get all objects
        //    UI equivalent: /index.php?page=depot&tab=default
        //    UI handler: renderDepot()
        case 'get_depot':
		require_once 'inc/init.php';
                $cellfilter = getCellFilter();
                $objects = filterCellList (listCells ('object'), $cellfilter['expression']);
                // get details if requested
                if (isset ($_REQUEST['include_attrs']))
                {
                        foreach ($objects as $object_id => $object)
                        {
                                amplifyCell($object);
                                // return the attributes in an array keyed on 'name', unless otherwise requested
                                $key_attrs_on = 'name';
                                if (isset ($_REQUEST['key_attrs_on']))
                                        $key_attrs_on = $_REQUEST['key_attrs_on'];
                                $attrs = array();
                                foreach (getAttrValues ( $object_id ) as $record)
                                {
                                        // check that the key exists for this record
                                        if (! isset ($record[ $key_attrs_on ]))
                                        {
                                                throw new InvalidRequestArgException ('key_attrs_on',
                                                                                      $_REQUEST['key_attrs_on'],
                                                                                      'requested keying value not set for all attributes');
                                        }
                                        if ( strlen ( $record['value'] ) )
                                                $attrs[ $record[ $key_attrs_on ] ] = $record;
                                }
                                $objects[$object_id] = $object;
                                $objects[$object_id]['attrs'] = $attrs;
                        }
                }
                sendAPIResponse($objects);
                break;
        // get all available object attributes
        //    UI equivalent: /index.php?page=attrs
        //    UI handler: renderAttributes()
        case 'get_attributes':
	        require_once 'inc/init.php';
                sendAPIResponse(getAttrMap());
                break;
        // get all chapters in the dictionary
        //    UI equivalent: /index.php?page=dict
        //    UI handler: renderDictionary()
        case 'get_dictionary':
	        require_once 'inc/init.php';
                sendAPIResponse(getChapterList());
                break;
        // get dictionary chapter
        //    UI equivalent: /index.php?page=chapter&chapter_no=1
        //    UI handler: renderChapter()
        case 'get_chapter':
                require_once 'inc/init.php';
                assertUIntArg ('chapter_no', TRUE);
                // make sure the chapter exists
                $chapters = getChapterList();
                if ( ! isset($chapters[$_REQUEST['chapter_no']]) )
                  throw new InvalidArgException ('chapter_no', $_REQUEST['chapter_no'],
                                                 "invalid argument: no such chapter");
                $style = 'a';
                if ( isset($_REQUEST['style']) and 'o' == $_REQUEST['style'])
                        $style = 'o';
                $words = readChapter ( $_REQUEST['chapter_no'], $style);
                // TODO: add refcount and attributes data to enable filtered lookups? getChapterRefc() and getChapterAttributes()
                
                sendAPIResponse($words);
                break;
        // add en entry to a chapter
        //    UI equivalent: /index.php?module=redirect&page=chapter&tab=edit&op=add&chapter_no=10007&dict_value=asdf
        //    UI handler: tableHandler()
        case 'add_chapter_entry':
	        require_once 'inc/init.php';
                assertUIntArg ('chapter_no', TRUE);
                assertStringArg ('dict_value', TRUE);
                // make sure the chapter exists
                $chapters = getChapterList();
                if ( ! isset($chapters[$_REQUEST['chapter_no']]) )
                  throw new InvalidArgException ('chapter_no', $_REQUEST['chapter_no'],
                                                 "invalid argument: no such chapter");
                usePreparedInsertBlade('Dictionary', array('chapter_id' => $_REQUEST['chapter_no'],
                                                           'dict_value' => $_REQUEST['dict_value']));
                sendAPIResponse(array(), array('message' => 'dictionary entry added successfully',
                                               'chapter_no' => $_REQUEST['chapter_no']));
                break;
        // delete an entry from a chapter
        //    UI equivalent: /index.php?page=chapter&module=redirect&op=del&dict_key=50228&tab=edit&chapter_no=10007
        //    UI handler: tableHandler()
        case 'delete_chapter_entry':
	        require_once 'inc/init.php';
                assertUIntArg ('chapter_no', TRUE);
                assertStringArg ('dict_value', TRUE);
                // make sure the chapter exists
                $chapters = getChapterList();
                if ( ! isset($chapters[$_REQUEST['chapter_no']]) )
                  throw new InvalidArgException ('chapter_no', $_REQUEST['chapter_no'],
                                                 "invalid argument: no such chapter");
                // make sure the entry exists in this chapter
                $words = readChapter ( $_REQUEST['chapter_no'], 'o');
                if ( ! in_array($_REQUEST['dict_value'], $words) )
                  throw new InvalidArgException ('dict_value', $_REQUEST['dict_value'],
                                                 "invalid argument: no such value in chapter ID " . $_REQUEST['chapter_no']);
                usePreparedDeleteBlade ('Dictionary', array ('chapter_id' => $_REQUEST['chapter_no'],
                                                             'dict_value' => $_REQUEST['dict_value']));
                sendAPIResponse( array(), array( 'message' => 'dictionary entry deleted successfully',
                                                 'chapter_no' => $_REQUEST['chapter_no'],
                                                 'dict_value' => $_REQUEST['dict_value']));
                break;
        // perform a generic search
        //    UI equivalent: /index.php?page=search
        //    UI handler: searchEntitiesByText()
        case 'search':
                require_once 'inc/init.php';
                assertStringArg ('term', TRUE);
                sendAPIResponse(searchEntitiesByText($_REQUEST['term']));
                break;
        // <<DESCRIPTION>>
        //    UI equivalent: /index.php?page=
        //    UI handler: ()
        //case '':
	//        require_once 'inc/init.php';
        //        ...do stuff...
        //        sendAPIResponse();
        //        break;
	default:
		throw new InvalidRequestArgException ('method', $_REQUEST['method']);
	}
	ob_end_flush();
}
catch (Exception $e)
{
        error_log('exception handled by API. message: "' . $e->getMessage()
                  . '" request URI: ' . $_SERVER['REQUEST_URI']);
	ob_end_clean();
        // TODO: add custom error display and possibly exceptions for API
	printAPIException ($e);
}
/**
 * Send a standardized HTTP response to the client.
 *
 * Sends a utf8 JSON response to the client, consisting of a top-level array
 * containing up to three elements:
 *
 * * *response*: the actual data being returned
 * * *metadata*: any additional information, such as number of objects found in a search (optional)
 * * *errors*: any errors that occured (optional)
 *
 * @param mixed[] $data the actual information to be returned
 * @param mixed[] $metadata metadata about any action taken or results returned
 * @param int $http_code HTTP code to return
 * @param mixed[] $errors errors that occured
 */
function sendAPIResponse ( $data, $metadata = NULL, $http_code = 200, $errors = NULL )
{
        $http_body = array( 'response' => $data );
        // add metadata if present
        if ( isset( $metadata ) )
        {
                $http_body[ 'metadata' ] = $metadata;
        }
        // add errors if present
        if ( isset( $errors ) )
        {
                $http_body[ 'errors' ] = $errors;
        }
        header ('Content-Type: application/json; charset=UTF-8', FALSE, $http_code);
        echo json_encode( recursive_utf8_encode($http_body), JSON_FORCE_OBJECT );
        exit;
}
/**
 * Recursively utf8 encode a data structure.
 *
 * Recursively encodes keys and values of a data strucure. Really only required if
 * you're running a PHP version that doesn't have json_encode's
 * JSON_UNESCAPED_UNICODE option.
 *
 * @param mixed[] $thing data structure to encode
 */
function recursive_utf8_encode ($thing) {
        if (!is_array($thing)){
                return utf8_encode($thing);
        }
        foreach ($thing as $k => $v) {
                $new_k = recursive_utf8_encode($k);
                $new_v = recursive_utf8_encode($v);
                unset($thing[$k]);
                $thing[$new_k] = $new_v;
        }
        return $thing;
}
/**
 * Return an exception to the client.
 *
 * Based on the class of exception passed, this method will return an appropriate
 * HTTP code and the description of the error.
 *
 * * RackTablesError (500)
 * * RTDatabaseError (500)
 * * RackCodeError (500)
 * * RTPermissionDenied (403)
 * * EntityNotFoundException (404)
 * * RTGatewayError (400)
 * * InvalidArgException (400)
 * * InvalidRequestArgException (400)
 *
 * @param Exception $e the exception that occurred.
 */
function printAPIException ($e)
{
        if ($e instanceof RackTablesError)
                switch ( get_class($e) )
                {
                case 'RackTablesError':
                        // TODO check RT error constant to see if i'ts an auth problem
                        sendAPIResponse(NULL,NULL,500,array($e->getMessage()));
                        break;
                case 'RTDatabaseError':
                        sendAPIResponse(NULL,NULL,500,array($e->getMessage()));
                        break;
                case 'RackCodeError':
                        sendAPIResponse(NULL,NULL,500,array($e->getMessage()));
                        break;
                case 'RTPermissionDenied':
                        sendAPIResponse(NULL,NULL,403,array($e->getMessage()));
                        break;
                case 'EntityNotFoundException':
                        sendAPIResponse(NULL,NULL,404,array($e->getMessage()));
                        break;
                case 'RTGatewayError':
                        sendAPIResponse(NULL,NULL,400,array($e->getMessage()));
                        break;
                case 'InvalidArgException':
                        sendAPIResponse(NULL,NULL,400,array($e->getMessage()));
                        break;
                case 'InvalidRequestArgException':
                        sendAPIResponse(NULL,NULL,400,array($e->getMessage()));
                        break;
                default:
                        sendAPIResponse(NULL,NULL,500,array('unhandled RackTablesError -based exception: '.$e->getMessage));
                        break;
                }
        elseif ($e instanceof PDOException)
                //printPDOException($e);
                sendAPIResponse(NULL,NULL,500,array('PDO exception: ' . $e->getMessage()));
        else
                //printGenericException($e);
                sendAPIResponse(NULL,NULL,500,array('unhandled exception: ' . $e->getMessage()));
}
?>

