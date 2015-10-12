
var container;
var info;
var camera, scene, renderer, labelRenderer;
var plane1;
var mouse, raycaster;

var rackMaterial, airMaterial;

var rollOverMesh, rollSize;

var newRackMesh;
var NEWRACK;

var SELECTED, LABEL;

var objects = [];

var offsetTop;

var line;
var pos_y = 0;

var meshWidth, meshDepth, meshHeight;

var row_id, row_name;

var orbit;
var controls;


function init( width, depth, height ) {
	container = document.createElement( "div" );
	document.body.appendChild( container );

	offsetTop = container.offsetTop;

	camera = new THREE.PerspectiveCamera( 45, window.innerWidth / window.innerHeight, 1, 10000 );
	camera.position.set( 200, 700, 1200 );
	camera.lookAt( new THREE.Vector3() );

	scene = new THREE.Scene();

	// cubes
	//rackMaterial = new THREE.MeshLambertMaterial( { color: 0x1e90ff, shading: THREE.FlatShading, map: THREE.ImageUtils.loadTexture( "img/square-outline-rack.png" ) } );
	rackMaterial = new THREE.MeshLambertMaterial( { shading: THREE.FlatShading, map: THREE.ImageUtils.loadTexture( "img/square-outline-rack.png" ) } );
	airMaterial = new THREE.MeshLambertMaterial( { shading: THREE.FlatShading, map: THREE.ImageUtils.loadTexture( "img/square-outline-air.png" ) } );

	//
	raycaster = new THREE.Raycaster();
	mouse = new THREE.Vector2();

	// Lights
	var ambientLight = new THREE.AmbientLight( 0x606060 );
	scene.add( ambientLight );
	var directionalLight = new THREE.DirectionalLight( 0xffffff );
	directionalLight.position.set( 1, 0.75, 0.5 ).normalize();
	scene.add( directionalLight );

	renderer = new THREE.WebGLRenderer( { antialias: true } );
	renderer.setClearColor( 0xf0f0f0 );
	renderer.setPixelRatio( window.devicePixelRatio );
	renderer.setSize( window.innerWidth, window.innerHeight );
	container.appendChild( renderer.domElement );

	// to display the name of rack, add CSS3DRenderer.
	labelRenderer = new THREE.CSS3DRenderer();
	labelRenderer.setSize( window.innerWidth, window.innerHeight );
	labelRenderer.domElement.style.position = "absolute";
	labelRenderer.domElement.style.top = offsetTop + "px";
	labelRenderer.domElement.style.pointerEvents = "none";
	container.appendChild( labelRenderer.domElement );

	// listener
	container.addEventListener( "mousemove", onDocumentMouseMove, false );
	container.addEventListener( "mousedown", onDocumentMouseDown, false );
	container.addEventListener( "mouseup", onDocumentMouseUp, false );
	container.addEventListener( "dblclick", onDocumentMouseDbClick, false ); 
	window.addEventListener( "resize", onWindowResize, false );

	showMesh( width, depth, height );
	addControls( meshWidth, meshDepth, meshHeight );

	// set contorls css 
	$(".dg").children(".main").css("margin-top", offsetTop + "px");

	//
	info = document.createElement( "div" );
	info.style.position = "absolute";
	info.style.top = (offsetTop + 10) + "px";
	info.style.width = "100%";
	info.align = "center";
	info.style.fontSize = "15px";
	container.appendChild( info );


	orbit = new THREE.OrbitControls( camera );
	orbit.enabled = false;
}

function render() {
	setTimeout( function() {
		requestAnimationFrame( render );
	}, 1000 / 10 );

	orbit.update();

	renderer.render( scene, camera );
	labelRenderer.render( scene, camera );
}

function onWindowResize() {
	camera.aspect = window.innerWidth / window.innerHeight;
	camera.updateProjectionMatrix();
	renderer.setSize( window.innerWidth, window.innerHeight );
	labelRenderer.setSize( window.innerWidth, window.innerHeight );
}

function onDocumentMouseDown( event ) {
	event.preventDefault();

	// get window scrollTop
	var scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop;

	mouse.x = ( event.clientX / window.innerWidth ) * 2 - 1;
	mouse.y = - ( ( event.clientY - offsetTop + scrollTop ) / window.innerHeight ) * 2 + 1;
	raycaster.setFromCamera( mouse, camera );

	orbit.enabled = true;

	var intersects = raycaster.intersectObjects( objects );
	if ( intersects.length > 0 ) {
		var intersect = intersects[ 0 ];
		
		var object = intersect.object;
		if ( object == plane1 ) {
			// if plane, do nothing.
			return;
		} else if ( object == newRackMesh ) {
			// if newRackMesh, change NEWRACK into True.
			NEWRACK = true;
		} else {
			// other conditionm, Rack is selected.
			SELECTED = object;
			SELECTED.visible = false;
	
			LABEL = scene.getObjectByName( "rackname_" + SELECTED.name );
			LABEL.element.style.display = "none";
			LABEL.visible = false;

			// roll-over helpers
			rollSize = SELECTED.userData.size;
			var rollOverGeometry = new THREE.BoxGeometry( rollSize[0], rollSize[1], rollSize[2] );
			var rollOverMaterial = new THREE.MeshBasicMaterial( { color: 0xff0000, opacity: 0.5, transparent: true } );
			rollOverMesh = new THREE.Mesh( rollOverGeometry, rollOverMaterial );

			rollOverMesh.position.copy( SELECTED.position );
			scene.add( rollOverMesh );
		}

		// delete object from object array.
		var index = objects.indexOf( object );
		objects.splice( index, 1 );

		container.style.cursor = "move";

		orbit.enabled = false;
	}
}

function onDocumentMouseMove( event ) {
	event.preventDefault();

	// get window scrollTop
	// chrome : document.body.scrollTop.
	// firefox : document.documentElement.scrollTop.
	// safiri : window.pageYOffset.
	// ie : if there is <!DOCTYPE html>, then document.documentElement.scrollTop. if no <!DOCTYPE html>, then document.body.scrollTop.
	// ※don't put window.pageYOffset at last.
	var scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop;

	mouse.x = ( event.clientX / window.innerWidth ) * 2 - 1;
	mouse.y = - ( ( event.clientY - offsetTop + scrollTop ) / window.innerHeight ) * 2 + 1;
	raycaster.setFromCamera( mouse, camera );
	
	var intersects = raycaster.intersectObjects( objects );

	if ( intersects.length > 0 ) {
		var intersect = intersects[ 0 ];

		if ( SELECTED ) {
			// if rack, setting rollOverMesh position.
			rollOverMesh.position.copy( intersect.point ).add( intersect.face.normal );
			//rollOverMesh.position.divideScalar( 50 ).floor().multiplyScalar( 50 ).addScalar( 25 );

			resetPosition( rollOverMesh, rollSize[0]/100, rollSize[2]/100, rollSize[1]/100 );

			container.style.cursor = "move";
			return;
		}
 		
		if ( NEWRACK ) {
			// if add new Rack, setting newRackMesh position.
			newRackMesh.position.copy( intersect.point ).add( intersect.face.normal );
			//newRackMesh.position.divideScalar( 50 ).floor().multiplyScalar( 50 ).addScalar( 25 );
	
			resetPosition( newRackMesh, controls.rackwidth, controls.rackdepth, controls.rackheight );

			container.style.cursor = "move";
			return;
		}
		
		if ( intersect.object != plane1　) {
			container.style.cursor = "pointer";

			if (intersect.object != newRackMesh) {
				var objectHTML = "<div style='width:40%;text-align:left'>";
				objectHTML += "Rack : " + intersect.object.name + "</br>";

				var itemHTML = "Items : ";
				var addItem = false;

				var mountedObjects = intersect.object.userData.rackData.mountedObjects;
				var objectIds = Object.keys(mountedObjects);

				var length = objectIds.length;

				if(length > 0) {
					objectHTML += "Objects : ";
				} else {
					objectHTML += "No Object.  ";
					itemHTML = "No Item.  ";
				}
				for(var i = 0; i < length; i ++) {

					var objectData = mountedObjects[objectIds[i]];

					// display object
					if(objectData["has_problems"] == "yes"){
						objectHTML += "<span style='color:red'>" + objectData["name"] + "</span>, ";
					} else {
						objectHTML += objectData["name"] + ", "; 
					}

					// display item
					var item = objectData["item"];
					if(item) {
						addItem = true;
						itemHTML += item + ", ";
					}
				}

				if(addItem) {
					itemHTML = itemHTML.substr(0, itemHTML.length - 2)　+ "</br>";
				} else {
					itemHTML = "No Item.";
				}

				objectHTML = objectHTML.substr(0, objectHTML.length - 2)　+ "</br>";

				var html = objectHTML + itemHTML + "</div>";
				info.innerHTML = html;
			}
		} else {
			container.style.cursor = "auto";

			info.innerHTML = "";
		}
	} else {
		container.style.cursor = "auto";

		info.innerHTML = "";
	}
}

function onDocumentMouseUp( event ) {
	event.preventDefault();

	if ( NEWRACK ) {
		objects.push( newRackMesh );
		NEWRACK = false;
	}

	if ( SELECTED ) {

		SELECTED.position.copy( rollOverMesh.position );
		SELECTED.visible = true;

		LABEL.position.copy( SELECTED.position );
		LABEL.position.y += rollSize[2] + 20;
		LABEL.element.style.display = "inherit";
		LABEL.visible = true;
		
		objects.push( SELECTED );

		// send position information to the server using ajax.
		var pos = {
			row_id : row_id,
			rack_id : SELECTED.uuid,
			pos_x : SELECTED.position.x,
			pos_y : SELECTED.position.y,
			pos_z : SELECTED.position.z
		};
		$.ajax({
			type     : "post",
			url      : "api.php?method=update_rack_position",
			data     : pos,    
	   		cache    : false,    
	 		success  : function(data) {}
		});

		scene.remove( rollOverMesh );
		SELECTED = null;
	}

	orbit.enabled = false;
	container.style.cursor = "auto";
}

function onDocumentMouseDbClick( event) {
	event.preventDefault();

	// get window scrollTop
	var scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop;

	mouse.x = ( event.clientX / window.innerWidth ) * 2 - 1;
	mouse.y = - ( ( event.clientY - offsetTop + scrollTop )  / window.innerHeight ) * 2 + 1;
	raycaster.setFromCamera( mouse, camera );

	var intersects = raycaster.intersectObjects( objects );

	if ( intersects.length > 0 ) {
		var intersect = intersects[ 0 ];
		if ( !(intersect.object == plane1  || intersect.object == newRackMesh)) {
			var rack = intersect.object;
	
			var link = rack.name;
			var href = "index.php?page=top&tab=edit&op=updateObjectAllocation&rackmulti[]=";

			if( rack.uuid >= 0 ) {
				href = href + rack.uuid;
			} else {
				return;
			}
			
			var ret = confirm("Redirect to:" + link + "?");
			if( ret == true ) {
				location.href = href;
			}
		}
	}
}

// controls area
function addControls( width, depth, height ) {
	var Controls =　function( width, depth, height ) {
		this.width = width;
		this.depth = depth;
		this.height = height;
		
		this.rackname = "";
		this.racktype = "rack";
		this.rackwidth = 0.7;
		this.rackdepth = 1.1;
		this.rackheight = 2.2;
		this.rackheightunit = 42;
		this.rackposition = false;
		this.newrack = function( event ) { 
			if ( this.rackname == "" ) {
				alert("please input the name.");
				return;
			}
			if ( this.rackwidth <= 0 ) {
				alert("invaild width value!");
				return;
			}
			if ( this.rackdepth <= 0 ) {
				alert("invaild depth value!");
				return;
			}
			if ( this.rackheight <= 0 ) {
				alert("invaild height value!");
				return;
			}
			if ( this.rackheightunit <= 0 ) {
				alert("invaild height unit value!");
				return;
			}
			
			if ( !newRackMesh ) {
				alert("please set the position!");
				return;
			}

			var href = "api.php?method=add_rack_with_position";
			href += "&name=" + this.rackname;
			href += "&row_id=" + row_id;
			href += "&height1=" + this.rackheightunit;
			href += "&asset_no=" + "";
			href += "&type=" + this.racktype;
		
			// position
			href += "&pos_x=" + newRackMesh.position.x;
			href += "&pos_y=" + newRackMesh.position.y;
			href += "&pos_z=" + newRackMesh.position.z;

			location.href = href;
		};

		// save as fdp
		this.save1 = function( event ) {
			saveFile( [ controls.width, controls.depth, controls.height ], scene );
		}
		this.save2 = function( event ) {
			saveFileAsGBXML( [ controls.width, controls.depth, controls.height ], scene );
		}
	};
	
	controls = new Controls( width, depth, height );
	var gui = new dat.GUI({ width: 230 });
	
	// Datacenter size
	var meshsize = gui.addFolder( "Datacenter size" );
	meshsize.open();
	var width = meshsize.add( controls, "width", 4, 10 ).step( 1 ).name( "width(m)" );
	width.onChange( function( value ) {
		controls.width = Math.floor( value / 1 );
	});
	width.onFinishChange( function( value ) {
		updateRoomSize( controls.width, controls.depth, controls.height );
		showMesh( controls.width, controls.depth, controls.height );
	});
	var depth = meshsize.add( controls, "depth", 4, 10 ).step( 1 ).name( "depth(m)" );
	depth.onChange( function( value ) {
		controls.depth = Math.floor( value / 1 );
	});
	depth.onFinishChange( function( value ) {
		updateRoomSize( controls.width, controls.depth, controls.height );
		showMesh( controls.width, controls.depth, controls.height );
	});
	var height = meshsize.add( controls, "height", 1, 5 ).step( 0.5 ).name( "height(m)" );
	height.onChange( function( value ) {
		controls.height = Math.floor( value / 0.5 ) / 2;
	});
	height.onFinishChange( function( value ) {
		updateRoomSize( controls.width, controls.depth, controls.height );
		showMesh( controls.width, controls.depth, controls.height );
	});

	// Add　new Rack
	var newrack = gui.addFolder( "Add new Rack" );
	newrack.open();
	var rackname = newrack.add( controls, "rackname" ).name( "name" );
	var racktype = newrack.add( controls, "racktype", [ "rack", "airconditioner" ] ).name( "type" );
	racktype.onFinishChange( function( value ) {
		updateNewRackMesh();
	});
	/*
	var rackwidth = newrack.add( controls, "rackwidth" ).name( "width(m)" );
	rackwidth.onFinishChange( function( value ) {
		updateNewRackMesh();
	});
	var rackdepth = newrack.add( controls, "rackdepth" ).name( "depth(m)" );
	rackdepth.onFinishChange( function( value ) {
		updateNewRackMesh();
	});
	var rackheight = newrack.add( controls, "rackheight" ).name( "height(m)" );
	rackheight.onFinishChange( function( value ) {
		updateNewRackMesh();
	});
	*/
	var rackheightunit = newrack.add( controls, "rackheightunit" ).name( "height unit" );
	var rackposition = newrack.add( controls, "rackposition" ).name( "position" );
	rackposition.onFinishChange( function( value) {
		if ( value ) {
			// if show position is checked, display newRackMesh.
			updateNewRackMesh();
		} else {
			// if show position　is checked, display newRackMesh.
			scene.remove( newRackMesh );

			// delete from objects array
			var index = objects.indexOf( newRackMesh );
			objects.splice( index, 1 );

			newRackMesh = null;
		}
	});
	var addrack = newrack.add( controls, "newrack" ).name( "add" );

	// save fdp&xml
	var save = gui.addFolder( "Save" );
	save.open();
	save.add( controls, "save1" ).name( "save as fdp" );
	save.add( controls, "save2" ).name( "save as gbxml" );
}

function updateNewRackMesh() {
	if ( !controls.rackposition ) {
		// if show position is not checked, return.
		return;
	}

	if ( newRackMesh ) {
		var position = newRackMesh.position;

		scene.remove( newRackMesh );
		var index = objects.indexOf( newRackMesh );
		if ( index >= 0) {
			objects.splice( index, 1 );
		}
	}
	
	var newRackGeometry = new THREE.BoxGeometry(  controls.rackwidth * 2 * 50, controls.rackheight * 2 * 50, controls.rackdepth * 2 *  50 );
	if ( controls.racktype == "rack" ) {
		var newRackMaterial = new THREE.MeshBasicMaterial( { color: 0x1e90ff, opacity: 0.5, transparent: true } );
	} else if ( controls.racktype == "airconditioner" ) {
		var newRackMaterial = new THREE.MeshBasicMaterial( { color: 0xfeb74c, opacity: 0.5, transparent: true } );
	}
	newRackMesh = new THREE.Mesh( newRackGeometry, newRackMaterial );

	// setting position
	if ( position ) {
		newRackMesh.position.copy( position );
	} else {
		newRackMesh.position.x = ( - controls.width + controls.rackwidth ) * 50;
		newRackMesh.position.z = ( controls.depth - controls.rackdepth )  * 50;
	}

	resetPosition( newRackMesh, controls.rackwidth, controls.rackdepth, controls.rackheight );

	scene.add( newRackMesh );
	objects.push( newRackMesh );
}

function resetPosition( rack, rackwidth, rackdepth, rackheight ) {
	if ( rack.position.x + rackwidth * 50 > meshWidth * 50 ) {
		rack.position.x = ( meshWidth - rackwidth ) * 50;
	} else if ( rack.position.x - rackwidth * 50 < - meshWidth * 50 ) {
		rack.position.x = ( - meshWidth + rackwidth ) * 50;
	}
	rack.position.y = pos_y + rackheight * 50;
	if ( rack.position.z + rackdepth * 50 > meshDepth * 50 ) {
		rack.position.z = ( meshDepth - rackdepth ) * 50;
	} else if ( rack.position.z - rackdepth * 50 < - meshDepth * 50 ) {
		rack.position.z = ( - meshDepth + rackdepth ) * 50;
	}
}

function updateRoomSize( width, depth, height ) {
	if ( width < 4 || depth < 4 || height < 1) {
		return;	
	}

	// send room size information to server using ajax.
	var size = {
		row_id : row_id,
		width : width,
		depth : depth,
		height : height
	};
	$.ajax({
		type     : "post",
		url      : "api.php?method=update_room_size",
		data     : size,    
	   	cache    : false,    
	 	success  : function(data) {}
	});	
}

function showMesh ( width, depth, height) {
	// set mesh size
	if ( width <= 0) {
		width = 8;
	}
	if ( depth <= 0 ) {
		depth = 6;
	}
	if ( height <= 0 ) {
		height = 2.5;
	}

	meshWidth = width;
	meshDepth = depth;
	meshHeight = height;

	width = width  * 50;
	depth = depth  * 50;
	height = height * 100;
	
	// delete old line
	if ( line ) {
		scene.remove( line );
	}

	// add new line
	var geometry = new THREE.Geometry();
	for ( var i = - depth; i <= depth; i += 50 ) {
		geometry.vertices.push( new THREE.Vector3( - width, pos_y, i ) );
		geometry.vertices.push( new THREE.Vector3(   width, pos_y, i ) );
	}
	for ( var i = - width; i <= width; i += 50 ) {
		geometry.vertices.push( new THREE.Vector3( i, pos_y, - depth ) );
		geometry.vertices.push( new THREE.Vector3( i, pos_y,   depth ) );
	}

	// row
	geometry.vertices.push( new THREE.Vector3( - width, pos_y + height,   depth ) );
	geometry.vertices.push( new THREE.Vector3( - width, pos_y + height, - depth ) );
	geometry.vertices.push( new THREE.Vector3( width, pos_y + height,   depth ) );
	geometry.vertices.push( new THREE.Vector3( width, pos_y + height, - depth ) );
	geometry.vertices.push( new THREE.Vector3( - width, pos_y + height,   depth ) );
	geometry.vertices.push( new THREE.Vector3( width, pos_y + height,   depth ) );
	geometry.vertices.push( new THREE.Vector3( - width, pos_y + height, - depth ) );
	geometry.vertices.push( new THREE.Vector3( width, pos_y + height, - depth ) );

	// col
	geometry.vertices.push( new THREE.Vector3( - width, pos_y + height, depth ) );
	geometry.vertices.push( new THREE.Vector3( - width, pos_y, depth ) );
	geometry.vertices.push( new THREE.Vector3( - width, pos_y + height, - depth ) );
	geometry.vertices.push( new THREE.Vector3( - width, pos_y, - depth ) );
	geometry.vertices.push( new THREE.Vector3( width, pos_y + height, depth ) );
	geometry.vertices.push( new THREE.Vector3( width, pos_y, depth ) );
	geometry.vertices.push( new THREE.Vector3( width, pos_y + height, - depth ) );
	geometry.vertices.push( new THREE.Vector3( width, pos_y, - depth ) );

	var material = new THREE.LineBasicMaterial( { color: 0x000000, opacity: 0.3, transparent: true } );
	line = new THREE.Line( geometry, material, THREE.LinePieces );
	scene.add( line );
	
	// delete plane1
	if ( plane1 ) {
		scene.remove( plane1 );

		var objectsIndex = objects.indexOf( plane1 );
		if ( objectsIndex >= 0 ) {
			objects.splice( objectsIndex, 1 );
		}
	}

	// add new plane1
	var geometry = new THREE.PlaneBufferGeometry( width * 2, depth * 2 );
	geometry.applyMatrix( new THREE.Matrix4().makeRotationX( - Math.PI / 2 ) );
	material = new THREE.MeshLambertMaterial( { color: 0xfaebd7 });
	plane1 = new THREE.Mesh( geometry, material );
	plane1.visible = true;
	plane1.position.y = pos_y;
	scene.add( plane1 );

	objects.push( plane1 );
}

function showRack( rackData, type, size, position ) {
	size = [ 70, 220, 110 ];

	var cube;
	// height = height　* 2
	if ( type == "rack" ) {
		var rackGeometry = new THREE.BoxGeometry( size[0], size[1], size[2] );
		cube = new THREE.Mesh( rackGeometry, rackMaterial );
	} else if ( type = "airconditioner") {
		var airGeometry = new THREE.BoxGeometry( size[0], size[1], size[2] );
		cube = new THREE.Mesh( airGeometry, airMaterial );
	}
	cube.uuid = rackData["id"];
	cube.name = rackData["name"];

	var objectPosition = getObjectPosition(rackData);
	var userData = {
		size : size,
		type : type,
		rackData : rackData,
		objectPosition : objectPosition
	};
	cube.userData = userData;

	// there is no position information
	if( !position ) {
		position = [ - meshWidth * 50 + size[0] / 2, 0, meshDepth * 50 - size[2] / 2 ];
	}

	cube.position.x = position[0];
	// setting y
	cube.position.y = pos_y + size[1] / 2;
	cube.position.z = position[2];

	scene.add( cube );
	objects.push( cube );


	// display name
	var name = document.createElement( "div" );
	name.className = "labelgreen";
	var objectids = Object.keys(rackData.mountedObjects);

	for(var i = 0; i < objectids.length; i ++) {
		var objectData = rackData.mountedObjects[objectids[i]];
		if(objectData.has_problems == "yes") {
			name.className = "labelred";
		}

		var pos = objectPosition[objectids[i]];
		if(!pos) {
			// if there is no position information in objectPosition, the object is not mounted into front of rack.
			// delete the object from mountedObjects。
			delete rackData.mountedObjects[objectids[i]];
			continue;
		}
		if(!rackData.mountedObjects[objectids[i]].name) {
			// if there is no object name, delete from mountedObjects.
			// [spacer]'s name is null.
			delete rackData.mountedObjects[objectids[i]];
			continue;
		}

		// display item
		if(objectData.item) {
			var material = new THREE.MeshBasicMaterial( { color: 0x7fffff, opacity: 0.5, transparent: true } );
			var geometry = new THREE.BoxGeometry( 5, 5, 5 );
			var itemCube = new THREE.Mesh( geometry, material );

			itemCube.position.x = 0;
			itemCube.position.y = (pos.start * 100 + pos.end * 100- size[1]) / 2;
			itemCube.position.z = -53;

			itemCube.userData = {
				type : "sensor"
			}

			cube.add(itemCube);
		}

	}
	name.textContent = cube.name;

	var label = new THREE.CSS3DObject( name );
	label.name = "rackname_" + cube.name;
	label.position.copy( cube.position );
	label.position.y = size[1] + 20;
	scene.add( label );
}


function getObjectPosition( rackData ) {
	var count = rackData.height;

	var bottom = 0.10;
	var top = 2.10;
	var interval = 0.01;

	var totalH = ( top - bottom - interval ) / count ;
	var tempH = totalH - interval;
	var add = false;

	var mountedDatas = {};
	for ( var i = count; i > 0; i -- ) {
		var server = rackData[ i ][0].object_id;
		
		if ( server ) {
			mountedDatas[server] = {};

			// set the starting position of the the object
			var start = bottom + interval + (i - 1) * totalH;

			if ( i == 1 ) {
				// if this is the last object, change 'add' into true.
				add = true;
			} else if ( server == rackData[ i - 1 ][0].object_id ) {
				tempH += totalH;
			} else {
				// if the next object is another object, change 'add' into true.
				add = true;
			}

			if ( add ) {
				// set the ending positiong of the object
				var end = start + tempH;

				mountedDatas[server]["start"] = start;
				mountedDatas[server]["end"] = end;

				tempH = totalH - interval;
				add = false;
			}
		}
	}

	return mountedDatas;
}


