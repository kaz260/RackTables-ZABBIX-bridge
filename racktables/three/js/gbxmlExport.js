var id = 0;
var spaceId = 0;
var surfaceId = 0;
var openingId = 0;

function saveFileAsGBXML(size, scene) {
	var xml = createGBXML(size, scene);

	var blob = new Blob([xml], {type: 'text/plain'});
	saveAs(blob, row_name + '.xml');

}

function createGBXML(meshSize, scene) {
	var output = '';
	var surface = '';
	var zone = '';

	output += '<?xml version="1.0"?>';
	output += '<gbXML xmlns="http://www.gbxml.org/schema" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"\n';
	output += '       xsi:schemaLocation="http://www.gbxml.org/schema" temperatureUnit="F" lengthUnit="Inches" areaUnit="SquareFeet" volumeUnit="CubicFeet" useSIUnitsForResults="false">\n';
	output += '\t<Campus id="camp1">\n';
	output += '\t\t<Location>\n';
	output += '\t\t\t<ZipcodeOrPostalCode>03101</ZipcodeOrPostalCode>\n';
	output += '\t\t</Location>\n';
	output += '\t\t<Building id="bldg1" buildingType="Office">\n';

	output += '\t\t\t<Area>18000.00000</Area>\n';

	// model
	scene.traverse( function ( object ) {
		if ( !(object instanceof THREE.Mesh) ) return;
		// if the type of current is not rack, return.
		if ( !(object.userData.type == "rack") ) return;
			var geometry = object.geometry;
			var matrixWorld = object.matrixWorld;
		if ( !(object.geometry instanceof THREE.Geometry )) return;
			var vertices = geometry.vertices;

			var vectors = [];
			for(var i = 0; i < 8; i ++) {
				var vector = new THREE.Vector3();
				vector.copy( vertices[ i ] ).applyMatrix4( matrixWorld );
				vector.x = vector.x / 100 + meshSize[0] / 2;
				temp_y = meshSize[1] / 2 - vector.z / 100;
				temp_z = vector.y / 100
				vector.y = temp_y;
				vector.z = temp_z;

				vectors.push( vector );
			}

			// rack
			var currentSpaceId = object.name + '_rack_' + spaceId;
			var outputArray = setRackGBXML(object.name, currentSpaceId, vectors);
			output += outputArray[0];
			surface += outputArray[1];

			// rackFilters
			currentSpaceId = object.name + '_rackFilters_' + spaceId;
			var outputArray = setRackFiltersGBXML(currentSpaceId, object.name, vectors);
			output += outputArray[0];
			surface += outputArray[1];

			// opening
			currentSpaceId = object.name + '_Opening_' + spaceId;
			var outputArray = setOpeningGBXML( currentSpaceId, object.name, vectors );
			output += outputArray[0];
			surface += outputArray[1];

			// sensors
			currentSpaceId = object.name + '_Sensors_' + spaceId;
			var outputArray = setSensorsGBXML(currentSpaceId, object.name, vectors, object.userData.rackData, object.userData.objectPosition);
			output += outputArray[0];
			surface += outputArray[1];

			// servers
			currentSpaceId = object.name + '_Servers_' + spaceId;
			var outputArray = setServersGBXML(currentSpaceId, object.name, vectors, object.userData.rackData, object.userData.objectPosition);
			output += outputArray[0];
			surface += outputArray[1];

			spaceId ++;

			zone += createZoneGBXML(object.name);
	});

	output += '\t\t</Building>\n';

	output += surface;

	output += '\t</Campus>\n';

	output += zone;

	output += '</gbXML>\n';
	return output;
}



function setRackGBXML(　name, currentSpaceId ,vectors ) {
	var group = '';
	var surface = '';

	group += createSpaceGBXML( name, 'rack', currentSpaceId, vectors );

	// rackFrame left
	var vector0 = new THREE.Vector3().copy( vectors[5] );
	vector0.x += 0.01;
	var vector1 = new THREE.Vector3().copy( vectors[4] );
	vector1.x += 0.01;
	var vector2 = new THREE.Vector3().copy( vectors[7] );
	vector2.x += 0.01;
	var vector3 = new THREE.Vector3().copy( vectors[6] );
	vector3.x += 0.01;
	var vector4 = new THREE.Vector3().copy( vectors[4] );
	var vector5 = new THREE.Vector3().copy( vectors[5] );
	var vector6 = new THREE.Vector3().copy( vectors[6] );
	var vector7 = new THREE.Vector3().copy( vectors[7] );
	var frameVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	var currentSurfaceId = 'rackFrame' + '_' + surfaceId;
	surface += createSurfaceGBXML('rackFrame', currentSurfaceId, currentSpaceId, frameVectors);
	surface += '\t\t</Surface>\n';
	surfaceId ++;

	// rackFrame right
	var vector0 = new THREE.Vector3().copy( vectors[0] );
	var vector1 = new THREE.Vector3().copy( vectors[1] );
	var vector2 = new THREE.Vector3().copy( vectors[2] );
	var vector3 = new THREE.Vector3().copy( vectors[3] );
	var vector4 = new THREE.Vector3().copy( vectors[1] );
	vector4.x -= 0.01;
	var vector5 = new THREE.Vector3().copy( vectors[0] );
	vector5.x -= 0.01;
	var vector6 = new THREE.Vector3().copy( vectors[3] );
	vector6.x -= 0.01;
	var vector7 = new THREE.Vector3().copy( vectors[2] );
	vector7.x -= 0.01;
	var frameVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	var currentSurfaceId = 'rackFrame' + '_' + surfaceId;
	surface += createSurfaceGBXML('rackFrame', currentSurfaceId, currentSpaceId, frameVectors);
	surface += '\t\t</Surface>\n';
	surfaceId ++;

	// rackFrame top
	var vector0 = new THREE.Vector3().copy( vectors[0] );
	var vector1 = new THREE.Vector3().copy( vectors[1] );
	var vector2 = new THREE.Vector3().copy( vectors[0] );
	vector2.z -= 0.01;
	var vector3 = new THREE.Vector3().copy( vectors[1] );
	vector3.z -= 0.01;
	var vector4 = new THREE.Vector3().copy( vectors[4] );
	var vector5 = new THREE.Vector3().copy( vectors[5] );
	var vector6 = new THREE.Vector3().copy( vectors[4] );
	vector6.z -= 0.01;
	var vector7 = new THREE.Vector3().copy( vectors[5] );
	vector7.z -= 0.01;
	var frameVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	var currentSurfaceId = 'rackFrame' + '_' + surfaceId;
	surface += createSurfaceGBXML('rackFrame', currentSurfaceId, currentSpaceId, frameVectors);
	surface += '\t\t</Surface>\n';
	surfaceId ++;

	// rackFrame bottom
	var vector0 = new THREE.Vector3().copy( vectors[2] );
	vector0.z += 0.01;
	var vector1 = new THREE.Vector3().copy( vectors[3] );
	vector1.z += 0.01;
	var vector2 = new THREE.Vector3().copy( vectors[2] );
	var vector3 = new THREE.Vector3().copy( vectors[3] );
	var vector4 = new THREE.Vector3().copy( vectors[6] );
	vector4.z += 0.01;
	var vector5 = new THREE.Vector3().copy( vectors[7] );
	vector5.z += 0.01;
	var vector6 = new THREE.Vector3().copy( vectors[6] );
	var vector7 = new THREE.Vector3().copy( vectors[7] );
	var frameVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	var currentSurfaceId = 'rackFrame' + '_' + surfaceId;
	surface += createSurfaceGBXML('rackFrame', currentSurfaceId, currentSpaceId, frameVectors);
	surface += '\t\t</Surface>\n';
	surfaceId ++;

	// rackFrame front left
	var vector5 = new THREE.Vector3().copy( vectors[5] );
	var vector7 = new THREE.Vector3().copy( vectors[7] );
	var vector0 = new THREE.Vector3().copy( vector5 );
	vector0.x += 0.1;
	var vector2 = new THREE.Vector3().copy( vector7 );
	vector2.x += 0.1;
	var vector1 = new THREE.Vector3().copy( vector0 );
	vector1.y += 0.01;
	var vector3 = new THREE.Vector3().copy( vector2 );
	vector3.y += 0.01;
	var vector4 = new THREE.Vector3().copy( vector5 );
	vector4.y += 0.01;
	var vector6 = new THREE.Vector3().copy( vector7 );
	vector6.y += 0.01;
	var frameVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	var currentSurfaceId = 'rackFrame' + '_' + surfaceId;
	surface += createSurfaceGBXML('rackFrame', currentSurfaceId, currentSpaceId, frameVectors);
	surface += '\t\t</Surface>\n';
	surfaceId ++;

	// rackFrame front right
	var vector0 = new THREE.Vector3().copy( vectors[0] );
	var vector2 = new THREE.Vector3().copy( vectors[2] );
	var vector5 = new THREE.Vector3().copy( vector0 );
	vector5.x -= 0.1;
	var vector7 = new THREE.Vector3().copy( vector2 );
	vector7.x -= 0.1;
	var vector1 = new THREE.Vector3().copy( vector0 );
	vector1.y += 0.01;
	var vector3 = new THREE.Vector3().copy( vector2 );
	vector3.y += 0.01;
	var vector4 = new THREE.Vector3().copy( vector5 );
	vector4.y += 0.01;
	var vector6 = new THREE.Vector3().copy( vector7 );
	vector6.y += 0.01;
	var frameVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	var currentSurfaceId = 'rackFrame' + '_' + surfaceId;
	surface += createSurfaceGBXML('rackFrame', currentSurfaceId, currentSpaceId, frameVectors);
	surface += '\t\t</Surface>\n';
	surfaceId ++;

	// rackFrame front top
	var vector0 = new THREE.Vector3().copy( vectors[0] );
	vector0.x -= 0.1;
	var vector5 = new THREE.Vector3().copy( vectors[5] );
	vector5.x += 0.1;
	var vector2 = new THREE.Vector3().copy( vector0 );
	vector2.z -= 0.1;
	var vector7 = new THREE.Vector3().copy( vector5 );
	vector7.z -= 0.1;
	var vector1 = new THREE.Vector3().copy( vector0 );
	vector1.y += 0.01;
	var vector3 = new THREE.Vector3().copy( vector2 );
	vector3.y += 0.01;
	var vector4 = new THREE.Vector3().copy( vector5 );
	vector4.y += 0.01;
	var vector6 = new THREE.Vector3().copy( vector7 );
	vector6.y += 0.01;
	var frameVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	var currentSurfaceId = 'rackFrame' + '_' + surfaceId;
	surface += createSurfaceGBXML('rackFrame', currentSurfaceId, currentSpaceId, frameVectors);
	surface += '\t\t</Surface>\n';
	surfaceId ++;

	// rackFrame front bottom
	var vector2 = new THREE.Vector3().copy( vectors[2] );
	vector2.x -= 0.1;
	var vector7 = new THREE.Vector3().copy( vectors[7] );
	vector7.x += 0.1;
	var vector0 = new THREE.Vector3().copy( vector2 );
	vector0.z += 0.1;
	var vector5 = new THREE.Vector3().copy( vector7 );
	vector5.z += 0.1;
	var vector1 = new THREE.Vector3().copy( vector0 );
	vector1.y += 0.01;
	var vector3 = new THREE.Vector3().copy( vector2 );
	vector3.y += 0.01;
	var vector4 = new THREE.Vector3().copy( vector5 );
	vector4.y += 0.01;
	var vector6 = new THREE.Vector3().copy( vector7 );
	vector6.y += 0.01;
	var frameVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	var currentSurfaceId = 'rackFrame' + '_' + surfaceId;
	surface += createSurfaceGBXML('rackFrame', currentSurfaceId, currentSpaceId, frameVectors);
	surface += '\t\t</Surface>\n';
	surfaceId ++;

	// rackFrame back left
	var vector4 = new THREE.Vector3().copy( vectors[4] );
	var vector6 = new THREE.Vector3().copy( vectors[6] );
	var vector1 = new THREE.Vector3().copy( vector4 );
	vector1.x += 0.1;
	var vector3 = new THREE.Vector3().copy( vector6 );
	vector3.x += 0.1;
	var vector0 = new THREE.Vector3().copy( vector1 );
	vector0.y -= 0.01;
	var vector2 = new THREE.Vector3().copy( vector3 );
	vector2.y -= 0.01;
	var vector5 = new THREE.Vector3().copy( vector4 );
	vector5.y -= 0.01;
	var vector7 = new THREE.Vector3().copy( vector6 );
	vector7.y -= 0.01;
	var frameVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	var currentSurfaceId = 'rackFrame' + '_' + surfaceId;
	surface += createSurfaceGBXML('rackFrame', currentSurfaceId, currentSpaceId, frameVectors);
	surface += '\t\t</Surface>\n';
	surfaceId ++;

	// rackFrame back right
	var vector1 = new THREE.Vector3().copy( vectors[1] );
	var vector3 = new THREE.Vector3().copy( vectors[3] );
	var vector4 = new THREE.Vector3().copy( vector1 );
	vector4.x -= 0.1;
	var vector6 = new THREE.Vector3().copy( vector3 );
	vector6.x -= 0.1;
	var vector0 = new THREE.Vector3().copy( vector1 );
	vector0.y -= 0.01;
	var vector2 = new THREE.Vector3().copy( vector3 );
	vector2.y -= 0.01;
	var vector5 = new THREE.Vector3().copy( vector4 );
	vector5.y -= 0.01;
	var vector7 = new THREE.Vector3().copy( vector6 );
	vector7.y -= 0.01;
	var frameVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	var currentSurfaceId = 'rackFrame' + '_' + surfaceId;
	surface += createSurfaceGBXML('rackFrame', currentSurfaceId, currentSpaceId, frameVectors);
	surface += '\t\t</Surface>\n';
	surfaceId ++;

	// rackFrame back top
	var vector1 = new THREE.Vector3().copy( vectors[1] );
	vector1.x -= 0.1;
	var vector4 = new THREE.Vector3().copy( vectors[4] );
	vector4.x += 0.1;
	var vector3 = new THREE.Vector3().copy( vector1 );
	vector3.z -= 0.1;
	var vector6 = new THREE.Vector3().copy( vector4 );
	vector6.z -= 0.1;
	var vector0 = new THREE.Vector3().copy( vector1 );
	vector0.y -= 0.01;
	var vector2 = new THREE.Vector3().copy( vector3 );
	vector2.y -= 0.01;
	var vector5 = new THREE.Vector3().copy( vector4 );
	vector5.y -= 0.01;
	var vector7 = new THREE.Vector3().copy( vector6 );
	vector7.y -= 0.01;
	var frameVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	var currentSurfaceId = 'rackFrame' + '_' + surfaceId;
	surface += createSurfaceGBXML('rackFrame', currentSurfaceId, currentSpaceId, frameVectors);
	surface += '\t\t</Surface>\n';
	surfaceId ++;

	// rackFrame back bottom
	var vector3 = new THREE.Vector3().copy( vectors[3] );
	vector3.x -= 0.1;
	var vector6 = new THREE.Vector3().copy( vectors[6] );
	vector6.x += 0.1;
	var vector1 = new THREE.Vector3().copy( vector3 );
	vector1.z += 0.1;
	var vector4 = new THREE.Vector3().copy( vector6 );
	vector4.z += 0.1;
	var vector0 = new THREE.Vector3().copy( vector1 );
	vector0.y -= 0.01;
	var vector2 = new THREE.Vector3().copy( vector3 );
	vector2.y -= 0.01;
	var vector5 = new THREE.Vector3().copy( vector4 );
	vector5.y -= 0.01;
	var vector7 = new THREE.Vector3().copy( vector6 );
	vector7.y -= 0.01;
	var frameVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	var currentSurfaceId = 'rackFrame' + '_' + surfaceId;
	surface += createSurfaceGBXML('rackFrame', currentSurfaceId, currentSpaceId, frameVectors);
	surface += '\t\t</Surface>\n';
	surfaceId ++;

	// rackInner left
	var vector5 = new THREE.Vector3().copy( vectors[5] );
	vector5.x += 0.01; vector5.y += 0.06; vector5.z -= 0.01;
	var vector7 = new THREE.Vector3().copy( vectors[7] );
	vector7.x += 0.01; vector7.y += 0.06; vector7.z += 0.01;
	var vector0 = new THREE.Vector3().copy( vector5 );
	vector0.x += 0.09;
	var vector2 = new THREE.Vector3().copy( vector7 );
	vector2.x += 0.09;
	var vector1 = new THREE.Vector3().copy( vector0 );
	vector1.y += 0.7;
	var vector3 = new THREE.Vector3().copy( vector2 );
	vector3.y += 0.7;
	var vector4 = new THREE.Vector3().copy( vector5 );
	vector4.y += 0.7;
	var vector6 = new THREE.Vector3().copy( vector7 );
	vector6.y += 0.7;
	var innerVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	var currentSurfaceId = 'rackInner' + '_' + surfaceId;
	surface += createSurfaceGBXML('rackInner', currentSurfaceId, currentSpaceId, innerVectors);
	surface += '\t\t</Surface>\n';
	surfaceId ++;

	// rackInner right
	var vector0 = new THREE.Vector3().copy( vectors[0] );
	vector0.x -= 0.01; vector0.y += 0.06; vector0.z -= 0.01;
	var vector2 = new THREE.Vector3().copy( vectors[2] );
	vector2.x -= 0.01; vector2.y += 0.06; vector2.z += 0.01;
	var vector5 = new THREE.Vector3().copy( vector0 );
	vector5.x -= 0.09;
	var vector7 = new THREE.Vector3().copy( vector2 );
	vector7.x -= 0.09;
	var vector1 = new THREE.Vector3().copy( vector0 );
	vector1.y += 0.7;
	var vector3 = new THREE.Vector3().copy( vector2 );
	vector3.y += 0.7;
	var vector4 = new THREE.Vector3().copy( vector5 );
	vector4.y += 0.7;
	var vector6 = new THREE.Vector3().copy( vector7 );
	vector6.y += 0.7;
	var innerVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	var currentSurfaceId = 'rackInner' + '_' + surfaceId;
	surface += createSurfaceGBXML('rackInner', currentSurfaceId, currentSpaceId, innerVectors);
	surface += '\t\t</Surface>\n';
	surfaceId ++;

	// rackInner top
	var vector0 = new THREE.Vector3().copy( vectors[0] );
	vector0.x -= 0.1; vector0.y += 0.06; vector0.z -= 0.01;
	var vector5 = new THREE.Vector3().copy( vectors[5] );
	vector5.x += 0.1; vector5.y += 0.06; vector5.z -= 0.01;
	var vector2 = new THREE.Vector3().copy( vector0 );
	vector2.z -= 0.09;
	var vector7 = new THREE.Vector3().copy( vector5 );
	vector7.z -= 0.09;
	var vector1 = new THREE.Vector3().copy( vector0 );
	vector1.y += 0.7;
	var vector3 = new THREE.Vector3().copy( vector2 );
	vector3.y += 0.7;
	var vector4 = new THREE.Vector3().copy( vector5 );
	vector4.y += 0.7;
	var vector6 = new THREE.Vector3().copy( vector7 );
	vector6.y += 0.7;
	var innerVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	var currentSurfaceId = 'rackInner' + '_' + surfaceId;
	surface += createSurfaceGBXML('rackInner', currentSurfaceId, currentSpaceId, innerVectors);
	surface += '\t\t</Surface>\n';
	surfaceId ++;

	// rackInner bottom
	var vector2 = new THREE.Vector3().copy( vectors[2] );
	vector2.x -= 0.1; vector2.y += 0.06; vector2.z += 0.01;
	var vector7 = new THREE.Vector3().copy( vectors[7] );
	vector7.x += 0.1; vector7.y += 0.06; vector7.z += 0.01;
	var vector0 = new THREE.Vector3().copy( vector2 );
	vector0.z += 0.09;
	var vector5 = new THREE.Vector3().copy( vector7 );
	vector5.z += 0.09;
	var vector1 = new THREE.Vector3().copy( vector0 );
	vector1.y += 0.7;
	var vector3 = new THREE.Vector3().copy( vector2 );
	vector3.y += 0.7;
	var vector4 = new THREE.Vector3().copy( vector5 );
	vector4.y += 0.7;
	var vector6 = new THREE.Vector3().copy( vector7 );
	vector6.y += 0.7;
	var innerVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	var currentSurfaceId = 'rackInner' + '_' + surfaceId;
	surface += createSurfaceGBXML('rackInner', currentSurfaceId, currentSpaceId, innerVectors);
	surface += '\t\t</Surface>\n';
	surfaceId ++;

	return [group, surface];
}

function setRackFiltersGBXML(currentSpaceId, name, vectors) {
	var group = '';
	var surface = '';

	// rackFilters group
	var vector0 = new THREE.Vector3().copy( vectors[0] );
	vector0.x -= 0.1; vector0.z -= 0.1;
	var vector1 = new THREE.Vector3().copy( vectors[1] );
	vector1.x -= 0.1; vector1.z -= 0.1;
	var vector2 = new THREE.Vector3().copy( vectors[2] );
	vector2.x -= 0.1; vector2.z += 0.1;
	var vector3 = new THREE.Vector3().copy( vectors[3] );
	vector3.x -= 0.1; vector3.z += 0.1;
	var vector4 = new THREE.Vector3().copy( vectors[4] );
	vector4.x += 0.1; vector4.z -= 0.1;
	var vector5 = new THREE.Vector3().copy( vectors[5] );
	vector5.x += 0.1; vector5.z -= 0.1;
	var vector6 = new THREE.Vector3().copy( vectors[6] );
	vector6.x += 0.1; vector6.z += 0.1;
	var vector7 = new THREE.Vector3().copy( vectors[7] );
	vector7.x += 0.1; vector7.z += 0.1;
	var filersVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	group += createSpaceGBXML(name, 'rackFilters', currentSpaceId, filersVectors );

	// rackFront
	var vector0 = new THREE.Vector3().copy( filersVectors[5] );
	var vector1 = new THREE.Vector3().copy( filersVectors[0] );
	var vector2 = new THREE.Vector3().copy( filersVectors[2] );
	var vector3 = new THREE.Vector3().copy( filersVectors[7] );
	var openingVectors = [ vector0, vector1, vector2, vector3 ];
	surface += createSurfaceGBXML('rackFront', 'rackFront' + '_' + surfaceId, currentSpaceId, openingVectors);
	surface += createOpeningGBXML( 'rackFront', 'rackFront' + '_' + openingId, openingVectors);
	surface += '\t\t</Surface>\n';
	surfaceId ++;
	openingId++;

	// rackBack
	var vector0 = new THREE.Vector3().copy( filersVectors[4] );
	var vector1 = new THREE.Vector3().copy( filersVectors[1] );
	var vector2 = new THREE.Vector3().copy( filersVectors[3] );
	var vector3 = new THREE.Vector3().copy( filersVectors[6] );
	var openingVectors = [ vector0, vector1, vector2, vector3 ];
	surface += createSurfaceGBXML('rackBack', 'rackBack' + '_' + surfaceId, currentSpaceId, openingVectors);
	surface += createOpeningGBXML( 'rackBack','rackback' + '_' + openingId, openingVectors);
	surface += '\t\t</Surface>\n';
	surfaceId ++;
	openingId++;

	return [group, surface];
}

function setOpeningGBXML( currentSpaceId, name, vectors ) {
	var group = '';
	var surface = '';

	// opeing    group
	var vector0 = new THREE.Vector3().copy( vectors[0] );
	vector0.x -= 0.1; vector0.z -= 0.1; vector0.y += 0.05;
	var vector2 = new THREE.Vector3().copy( vectors[2] );
	vector2.x -= 0.1; vector2.z += 0.1; vector2.y += 0.05;
	var vector5 = new THREE.Vector3().copy( vectors[5] );
	vector5.x += 0.1; vector5.z -= 0.1; vector5.y += 0.05;
	var vector7 = new THREE.Vector3().copy( vectors[7] );
	vector7.x += 0.1; vector7.z += 0.1; vector7.y += 0.05;
	var vector1 = new THREE.Vector3().copy( vector0 );
	vector1.y += 0.7;
	var vector3 = new THREE.Vector3().copy( vector2 );
	vector3.y += 0.7;
	var vector4 = new THREE.Vector3().copy( vector5 );
	vector4.y += 0.7;
	var vector6 = new THREE.Vector3().copy( vector7 );
	vector6.y += 0.7;
	var openingVectorsAll = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	group += createSpaceGBXML(name, '開口・圧損', currentSpaceId, openingVectorsAll );


	// Front
	var openingVectors = [ openingVectorsAll[5], openingVectorsAll[0], openingVectorsAll[2], openingVectorsAll[7] ];
	surface += createSurfaceGBXML('opening', 'opening' + '_' + surfaceId, currentSpaceId, openingVectors);
	surfaceId ++;

	// Front 1
	var vector0 = new THREE.Vector3().copy( openingVectorsAll[5] );
	var vector1 = new THREE.Vector3().copy( openingVectorsAll[0] );
	var vector2 = new THREE.Vector3().copy( vector1 );
	vector2.z -= 0.4;
	var vector3 = new THREE.Vector3().copy( vector0 );
	vector3.z -= 0.4;
	var openingVectors = [ vector0, vector1, vector2, vector3 ];
	surface += createOpeningGBXML( name, 'opening' + '_' + openingId, openingVectors);
	openingId++;

	// Front 2 ~ Front 5
	for ( var i = 0; i < 4; i++ ) {
		var vector0 = new THREE.Vector3().copy( vector3 );
		var vector1 = new THREE.Vector3().copy( vector2 );
		var vector2 = new THREE.Vector3().copy( vector1 );
		vector2.z -= 0.4;
		var vector3 = new THREE.Vector3().copy( vector0 );
		vector3.z -= 0.4;
		var openingVectors = [ vector0, vector1, vector2, vector3 ];
		surface += createOpeningGBXML( name, 'opening' + '_' + openingId, openingVectors);
		openingId++;
	}
	surface += '\t\t</Surface>\n';


	// Back
	var openingVectors = [ openingVectorsAll[4], openingVectorsAll[1], openingVectorsAll[3], openingVectorsAll[6] ];
	surface += createSurfaceGBXML('opening', 'opening' + '_' + surfaceId, currentSpaceId, openingVectors);
	surfaceId ++;

	// Back 1
	var vector0 = new THREE.Vector3().copy( openingVectorsAll[4] );
	var vector1 = new THREE.Vector3().copy( openingVectorsAll[1] );
	var vector2 = new THREE.Vector3().copy( vector1 );
	vector2.z -= 0.4;
	var vector3 = new THREE.Vector3().copy( vector0 );
	vector3.z -= 0.4;
	var openingVectors = [ vector0, vector1, vector2, vector3 ];
	surface += createOpeningGBXML( name, 'opening' + '_' + openingId, openingVectors);
	openingId++;

	// Back 2 ~ Back 5
	for ( var i = 0; i < 4; i++ ) {
		var vector0 = new THREE.Vector3().copy( vector3 );
		var vector1 = new THREE.Vector3().copy( vector2 );
		var vector2 = new THREE.Vector3().copy( vector1 );
		vector2.z -= 0.4;
		var vector3 = new THREE.Vector3().copy( vector0 );
		vector3.z -= 0.4;
		var openingVectors = [ vector0, vector1, vector2, vector3 ];
		surface += createOpeningGBXML( name, 'opening' + '_' + openingId, openingVectors);
		openingId++;
	}
	surface += '\t\t</Surface>\n';

	return [group, surface];
}

function setSensorsGBXML( currentSpaceId, name, vectors, rackData, objectPosition ) {
	var group = '';
	var surface = '';

	var objectids = Object.keys(rackData.mountedObjects);
	if(objectids.length <= 0) {
		return ['', ''];
	}

	var size = 0.05;

	var vector1 = new THREE.Vector3();
	vector1.x = ( vectors[1].x + vectors[4].x + size ) / 2; vector1.y = vectors[1].y;
	var vector4 = new THREE.Vector3();
	vector4.x = ( vectors[1].x + vectors[4].x -size ) / 2; vector4.y = vectors[1].y;
	var vector0 = new THREE.Vector3().copy( vector1 );
	vector0.y -= size;
	var vector5 = new THREE.Vector3().copy( vector4 );
	vector5.y -= size;
	var vector3 = new THREE.Vector3().copy( vector1 );
	var vector6 = new THREE.Vector3().copy( vector4 );
	var vector2 = new THREE.Vector3().copy( vector0 );
	var vector7 = new THREE.Vector3().copy( vector5 );

	// sensors
	var seneors = '';
	var start = null;
	var end = null;
	var addFlag = false;
	for(var i = 0; i < objectids.length; i ++) {
		var objectData = rackData.mountedObjects[objectids[i]];

		// display item 
		var item = objectData.item;
		if(item) {
			var pos = objectPosition[objectids[i]];

			vector3.z = (pos.start + pos.end - size) / 2;
			vector2.z = (pos.start + pos.end - size) / 2;
			vector6.z = (pos.start + pos.end - size) / 2;
			vector7.z = (pos.start + pos.end - size) / 2;

			vector1.z = vector3.z + size;
			vector4.z = vector6.z + size;
			vector0.z = vector2.z + size;
			vector5.z = vector7.z + size;


			var tempVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7, ];
			surface += createSurfaceGBXML(item, 'sensors' + '_' + surfaceId, currentSpaceId, tempVectors);
			surface += '\t\t</Surface>\n';
			surfaceId ++;

			addFlag = true;

			if((!start) || start > vector3.z) {
				start = vector3.z;
			}
			if((!end) || end < vector1.z) {
				end = vector1.z;
			}
		}
	}

	if(!addFlag) {
		return ['', ''];
	}

	// group
	vector3.z = start;
	vector2.z = start;
	vector6.z = start;
	vector7.z = start;

	vector1.z = end;
	vector4.z = end;
	vector0.z = end;
	vector5.z = end;
	var sensorVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7, ];
	group += createSpaceGBXML( name, 'Sensors', currentSpaceId, sensorVectors );


	return [group, surface];
}

function setServersGBXML( currentSpaceId, name, vectors, rackData, objectPosition ) {
	var group = '';
	var surface = '';

	var objectids = Object.keys(rackData.mountedObjects);
	if(objectids.length <= 0) {
		return ['', ''];
	}

	// object group
	var vector0 = new THREE.Vector3();
	vector0.x = vectors[0].x - 0.1; vector0.y = vectors[0].y + 0.05; 
	var vector5 = new THREE.Vector3().copy( vectors[5] );
	vector5.x = vectors[5].x + 0.1; vector5.y = vectors[5].y + 0.05;
	var vector2 = new THREE.Vector3().copy( vector0 );
	var vector7 = new THREE.Vector3().copy( vector5 );
	var vector1 = new THREE.Vector3().copy( vector0 );
	vector1.y += 0.7;
	var vector3 = new THREE.Vector3().copy( vector2 );
	vector3.y += 0.7;
	var vector4 = new THREE.Vector3().copy( vector5 );
	vector4.y += 0.7;
	var vector6 = new THREE.Vector3().copy( vector7 );
	vector6.y += 0.7;

	var servers = '';
	var start = null;
	var end = null;
	// object export
	for(var i = 0; i < objectids.length; i ++) {
		var objectData = rackData.mountedObjects[objectids[i]];
		var pos = objectPosition[objectids[i]];

		// set z of bottom vertex
		vector2.z = pos.start;
		vector7.z = pos.start;
		vector6.z = pos.start;
		vector3.z = pos.start;
		// set z of top vertex
		vector0.z = pos.end;
		vector5.z = pos.end;
		vector4.z = pos.end;
		vector1.z = pos.end;

		var serverVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
		surface += createSurfaceGBXML(objectData.name, 'Servers' + '_' + surfaceId, currentSpaceId, serverVectors);
		surface += '\t\t</Surface>\n';
		surfaceId ++;

		if((!start) || start > vector3.z) {
			start = vector3.z;
		}
		if((!end) || end < vector1.z) {
			end = vector1.z;
		}
	}

	// group
	vector2.z = start;
	vector7.z = start;
	vector6.z = start;
	vector3.z = start;

	vector0.z = end;
	vector5.z = end;
	vector4.z = end;
	vector1.z = end;
	var serverVectors = [ vector0, vector1, vector2, vector3, vector4, vector5, vector6, vector7 ];
	group += createSpaceGBXML(name, 'Servers', currentSpaceId, serverVectors );

	return [group, surface];
}

function createPolyLoopGBXML(vector0, vector1, vector2, vector3) {
	var output = '';

	output += '\t\t\t\t\t\t<PolyLoop>\n';
	output += '\t\t\t\t\t\t\t<CartesianPoint>\n';
	output += '\t\t\t\t\t\t\t\t<Coordinate>' + (vector0.x * 100) + '</Coordinate>\n';
	output += '\t\t\t\t\t\t\t\t<Coordinate>' + (vector0.y * 100) + '</Coordinate>\n';
	output += '\t\t\t\t\t\t\t\t<Coordinate>' + (vector0.z * 100) + '</Coordinate>\n';
	output += '\t\t\t\t\t\t\t</CartesianPoint>\n';
	output += '\t\t\t\t\t\t\t<CartesianPoint>\n';
	output += '\t\t\t\t\t\t\t\t<Coordinate>' + (vector1.x * 100) + '</Coordinate>\n';
	output += '\t\t\t\t\t\t\t\t<Coordinate>' + (vector1.y * 100) + '</Coordinate>\n';
	output += '\t\t\t\t\t\t\t\t<Coordinate>' + (vector1.z * 100) + '</Coordinate>\n';
	output += '\t\t\t\t\t\t\t</CartesianPoint>\n';
	output += '\t\t\t\t\t\t\t<CartesianPoint>\n';
	output += '\t\t\t\t\t\t\t\t<Coordinate>' + (vector2.x * 100) + '</Coordinate>\n';
	output += '\t\t\t\t\t\t\t\t<Coordinate>' + (vector2.y * 100) + '</Coordinate>\n';
	output += '\t\t\t\t\t\t\t\t<Coordinate>' + (vector2.z * 100) + '</Coordinate>\n';
	output += '\t\t\t\t\t\t\t</CartesianPoint>\n';
	output += '\t\t\t\t\t\t\t<CartesianPoint>\n';
	output += '\t\t\t\t\t\t\t\t<Coordinate>' + (vector3.x * 100) + '</Coordinate>\n';
	output += '\t\t\t\t\t\t\t\t<Coordinate>' + (vector3.y * 100) + '</Coordinate>\n';
	output += '\t\t\t\t\t\t\t\t<Coordinate>' + (vector3.z * 100) + '</Coordinate>\n';
	output += '\t\t\t\t\t\t\t</CartesianPoint>\n';
	output += '\t\t\t\t\t\t</PolyLoop>\n';

	return output;
}

function createSpaceGBXML(name, spaceName, spaceId,  vectors) {
	var output = '';

	output += '\t\t\t<Space id="' + spaceId + '" spaceType="OfficeOpenPlan" zoneIdRef="' + name + '">\n';
	output += '\t\t\t\t<Name>' + spaceName + '</Name>\n';
	output += '\t\t\t\t<Description></Description>\n';
	output += '\t\t\t\t<PeopleNumber unit="NumberOfPeople">8.00000</PeopleNumber>\n';
	output += '\t\t\t\t<LightPowerPerArea unit="WattPerSquareFoot">1.50000</LightPowerPerArea>\n';
	output += '\t\t\t\t<EquipPowerPerArea unit="WattPerSquareFoot">0.50000</EquipPowerPerArea>\n';
	output += '\t\t\t\t<Area>1200.00000</Area>\n';
	output += '\t\t\t\t<Volume>9600.00000</Volume>\n';
	output += '\t\t\t\t<ShellGeometry id="geo_sp11_Office_Office4">\n';
	output += '\t\t\t\t\t<ClosedShell>\n';

	output += createPolyLoopGBXML(vectors[4], vectors[5], vectors[7], vectors[6]);
	output += createPolyLoopGBXML(vectors[0], vectors[1], vectors[3], vectors[2]);
	output += createPolyLoopGBXML(vectors[0], vectors[2], vectors[7], vectors[5]);
	output += createPolyLoopGBXML(vectors[1], vectors[3], vectors[6], vectors[4]);
	output += createPolyLoopGBXML(vectors[0], vectors[1], vectors[4], vectors[5]);
	output += createPolyLoopGBXML(vectors[2], vectors[3], vectors[6], vectors[7]);

	output += '\t\t\t\t\t</ClosedShell>\n';
	output += '\t\t\t\t</ShellGeometry>\n';
	output += '\t\t\t\t<CADObjectId>' + id + '</CADObjectId>\n';
	id ++;
	output += '\t\t\t</Space>\n';

	return output;
}

function createSurfaceGBXML(name, surfaceId, spaceId, vectors) {
	var output = '';

	output += '\t\t<Surface id="' + surfaceId + '" surfaceType="ExteriorWall">\n';
	output += '\t\t\t<Name>' + name + '</Name>\n';
	output += '\t\t\t<AdjacentSpaceId spaceIdRef="' + spaceId + '"/>\n';

	output += '\t\t\t<PlanarGeometry>\n';

	if(vectors.length == 8) {
		output += createPolyLoopGBXML(vectors[4], vectors[5], vectors[7], vectors[6]);
		output += createPolyLoopGBXML(vectors[0], vectors[1], vectors[3], vectors[2]);
		output += createPolyLoopGBXML(vectors[0], vectors[2], vectors[7], vectors[5]);
		output += createPolyLoopGBXML(vectors[1], vectors[3], vectors[6], vectors[4]);
		output += createPolyLoopGBXML(vectors[0], vectors[1], vectors[4], vectors[5]);
		output += createPolyLoopGBXML(vectors[2], vectors[3], vectors[6], vectors[7]);
	} else if (vectors.length == 4) {
		output += createPolyLoopGBXML(vectors[0], vectors[1], vectors[2], vectors[3]);
	}

	output += '\t\t\t</PlanarGeometry>\n';


	return output;
}

function createOpeningGBXML(name, openingId, vectors) {
	var output = '';

	output += '\t\t\t<Opening id="' + openingId + '" openingType="OperableWindow">\n';
	output += '\t\t\t\t<Name>'+ name + '</Name>\n';
/*
	output += '\t\t\t\t<RectangularGeometry>\n';
	output += '\t\t\t\t\t<CartesianPoint>\n';
	output += '\t\t\t\t\t\t<Coordinate>96.00000</Coordinate>\n';
	output += '\t\t\t\t\t\t<Coordinate>24.00000</Coordinate>\n';
	output += '\t\t\t\t\t</CartesianPoint>\n';
	output += '\t\t\t\t\t<Height>72.00000</Height>\n';
	output += '\t\t\t\t\t<Width>48.00000</Width>\n';
	output += '\t\t\t\t</RectangularGeometry>\n';
*/
	output += '\t\t\t\t<PlanarGeometry>\n';

	output += createPolyLoopGBXML(vectors[0], vectors[1], vectors[2], vectors[3]);

	output += '\t\t\t\t</PlanarGeometry>\n';
	output += '\t\t\t</Opening>\n';

	return output;
}

function createZoneGBXML(name) {
	var output = '';

	output += '\t<Zone id="' + name + '">\n';
	output += '\t\t<Name>' + name + '</Name>\n';
	output += '\t\t<Description></Description>\n';
	output += '\t\t<AirChangesPerHour>1"</AirChangesPerHour>\n';
	output += '\t\t<FlowPerArea unit="CFMPerSquareFoot">0.13333</FlowPerArea>\n';
	output += '\t\t<FlowPerPerson unit="CFM">20.00000</FlowPerPerson>\n';
	output += '\t\t<OAFlowPerArea unit="CFMPerSquareFoot">0.05333</OAFlowPerArea>\n';
	output += '\t\t<OAFlowPerPerson unit="CFM">8.00000</OAFlowPerPerson>\n';
	output += '\t\t<DesignHeatT>72.00000</DesignHeatT>\n';
	output += '\t\t<DesignCoolT>75.00000</DesignCoolT>\n';
	output += '\t</Zone>\n';

	return output;
}
