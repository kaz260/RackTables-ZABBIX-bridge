
drop table if exists rack_airconditioner;
create table rack_airconditioner (
	row_id int(10) unsigned,
	rack_id int(10) unsigned primary key
);


drop table if exists rack_position;
create table rack_position (
	row_id int(10) unsigned,
	rack_id int(10) unsigned primary key,
	pos_x int(10),
	pos_y int(10),
	pos_z int(10)
);


drop table if exists room_size;
create table room_size (
	row_id int(10) unsigned primary key,
	width float unsigned,
	depth float unsigned,
	height float unsigned
);


drop table if exists item_information;
create table item_information (
	itemid bigint(20) unsigned primary key,
	objectid int(10) unsigned,
	type int(11) unsigned,
	hostid bigint(20) unsigned,
	name varchar(255),
	key_ varchar(255),
	interfaceid int(20) unsigned,
	delay int(11) default 0,
	history int(11) default 90,
	trends int(11) default 365,
	value_type int(11) default 0,
	trapper_hosts varchar(255),
	units varchar(255),
	multiplier int(11) default 0,
	delta int(11) default 0,
	snmp_community varchar(64),
	snmp_oid varchar(255),
	snmpv3_securityname varchar(64),
	snmpv3_securitylevel int(11) default 0,
	snmpv3_authpassphrase varchar(64),
	snmpv3_privpassphrase varchar(64),
	snmpv3_authprotocol int(11) default 0,
	snmpv3_privprotocol int(11) default 0,
	snmpv3_contextname varchar(255),
	formula varchar(255) default '1',
	error varchar(128),
	lastlogsize bigint(20) unsigned,
	logtimefmt varchar(64),
	templateid bigint(20) unsigned,
	valuemapid bigint(20) unsigned,
	delay_flex varchar(255),
	params text,
	ipmi_sensor varchar(128),
	data_type int(11) default 0,
	authtype int(11) default 0,
	username varchar(64),
	password varchar(64),
	publickey varchar(64),
	privatekey varchar(64),
	mtime int(11) default 0,
	flags int(11) default 0,
	filter varchar(255),
	port varchar(64),
	description text,
	inventory_link int(11) default 0,
	lifetime varchar(64) default '30',
	status int(11) default 0
);


