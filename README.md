# RackTables & Zabbix
## Overview
This project is aimed to RackTables link with Zabbix. Data synchronization Tools is also included.

## Description
1. The data relationship between RackTables and Zabbix is as following:  

  RackTables  | Zabbix
  ------------- | -------------
  rack  | hostgroup
  object  | host
  item  | item
  
  Racktables's item is added in this project.

2. When change is occurred in RackTables or Zabbix, using the API it can change the other management software.
  For example when you update rack in RackTables, the hostgroup that has the same name with the RackTables rack is also updated.
3. The 3D display page (RackTables only)  
  In the TOP page It display the rack in RackTables on top of 3D scene. The following is what you can actually do:  
    * Add rack in RackTables.
    * Change the datacenter size.
    * Change the rack position by drag-and-drop.
    * Export the 3D scene as fdp file or gbxml file (fdp is project file of [FlowDesigner](http://www.akl.co.jp/en/) which is a Simulation software).
    * Add object in a rack by double click a rack.
    * Add item (The item information is the same with Zabbix's item).
4. The item information page  
  Adding item information page in RackTables to correspond with Zabbix. In this page you can add, update or delete item. Of course it also change the item in Zabbix. 
5. Data synchronization Tools  
  The tools can mass inserting the data that is already existed in the Racktables DB or Zabbix DB into the other software.  

## Requirement
* [RackTables 0.20.10](http://racktables.org/)
* [Zabbix 2.2](http://www.zabbix.com/)
* [three.js](https://github.com/mrdoob/three.js)
* [FileSaver.js](https://github.com/eligrey/FileSaver.js/)
* [jszip.js](https://github.com/Stuk/jszip)
* [dat-gui.js](https://code.google.com/p/dat-gui/)

## How to use
#### RackTables & Zabbix
1. Install RackTables  
  Reference site:  
  `https://wiki.racktables.org/index.php/RackTablesInstallHowto`  

2. Install Zabbix  
  Reference site:  
  `https://www.zabbix.com/documentation/2.4/manual/installation`  

3. Create tables in RackTables database  
  Connect to the RackTables's database, and then execute the sql in `racktables/racktables_create_tables.sql`.  

4. Overwrite Racktables source file  
  Create foloder in RackTables:  
  `racktables/three/js/`  
  `racktables/three/css/`   
  `racktables/img/`  
  
  Overwrite the following file:
  * PHP file
    * racktables/index.php
    * racktables/api.php
    * racktables/inc/interface.php
    * racktables/inc/interface-lib.php
    * racktables/inc/navigation.php
    * racktables/inc/ophanlders.php
    * racktables/inc/zabbixapi.php  
  * JS file
    * racktables/three/js/top.js
    * racktables/three/js/item.js
    * racktables/three/js/jquery-1.11.3.min.js
    * racktables/three/js/three.min.js
    * racktables/three/js/OrbitControls.js
    * racktables/three/js/CSS3DRenderer.js
    * racktables/three/js/dat.gui.min.js
    * racktables/three/js/FileSaver.min.js
    * racktables/three/js/jszip.min.js
    * racktables/three/js/FlowDesignerProjectExport.js
    * racktables/three/js/gbxmlExport.js
  * CSS file
    * racktables/three/css/top.css
    * racktables/three/css/item.css
  * Image file
    * racktables/img/square-outline-rack.png
    * racktables/img/square-outline-air.png

  Change the username, password and URL in `racktables/inc/zabbixapi.php` to your Zabbix server's username, password and URL.  
  
  Change the defaultRowId in file `racktables/api.php`(There is no Row in Zabbix, so when adding hostgroup in Zabbix, it is necessary to specify the rack should be added in which row).
  
5. Overwrite Zabbix source file  
  Overwrite the following file:
  * PHP file
    * zabbix/hostgroups.php
    * zabbix/hosts.php
    * zabbix/items.php
    * zabbix/racktablesapi.php

  Change the username, password and URL in `zabbix/racktablesapi.php` to your RackTables server's username, password and URL.   
  
  Create hostgroup which name is `Default` in Zabbix (When added Object in RackTables, the Object is not mounted into Rack. So we put the host into the 'Default' group).

6. If the 3D scene in TOP page of RackTables dosen't display properly, you should make sure that the WebGL is enabled of your browser.  

#### Data synchronization Tools  
1. Create folder in RackTables:  
  `racktables/init_data/`  

2. Put the following file into the folder `racktables/init_data/`.
  * init_data/init_data.css
  * init_data/init_data.html
  * init_data/init_data.php
  * init_data/init_racktables.php
  * init_data/init_zabbix.php  
  

3. Change the row_id in file `racktables/init_data/init_racktables.php`(There is no Row in Zabbix, so when adding hostgroup in Zabbix, it is necessary to specify the rack should be added in which row).

4. Access to ` http://address.to.your.server/racktables` and login to RackTables.  

5. Access to ` http://address.to.your.server/racktables/init_data/init_data.html`.  
  * To import the data of Zabbix into RackTables, firstly delete the exists data of RackTables (the exists data may cause problems), and secondly import the Zabbix's data into RackTables.
  * The same as importing Zabbix's data into RackTables, to import RackTables's data into Zabbix, you should delete the exists data of Zabbix firstly. But there is no item in RackTables originally, thus after importing the item of Zabbix is empty.
  * If you are using both of RackTables and Zabbix, you can choose either one of the above.


