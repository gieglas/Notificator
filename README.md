Notificator - Send email notifications
======
 
@author      Constantinos Evangelou 

@copyright   2014 Constantinos Evangelou

@link        http://gieglas.com

@license     The MIT License (MIT)

@version     1.0.1

## Description ##
 
Sends notifications depending on a database table. The input data must have the following format:
  
 	'id' => NUMBER,
 	'from' => 'EMAIL@ADDRESS.COM',
 	'to' => 'EMAIL@ADDRESS.COM',
 	'subject' => 'STRING',
 	'notification_method' => 'email', (NOTE, for now only "email" notification method is supported)
 	'template' => 'NAME OF TEMPLARE FILE WITHOUT THE .mustache EXTENSION', (NOTE: ALL TEMPLATES MUST EXIST UNDER "templates" FOLDER)
 	'data_json'=> 'JSON STING WITH DATA SPECIFIC TO THE TEMPLATE'
 	
This program will send all the emails that are derived from the "selectquery" in the config.php which checks records with status = 0. It then runs the "updatequerystatus1" which updates the status to 1. Then it sends the notification using a mustache template. It then updates the status of the send email to 2 (which means notification was send). If an error occurs the status of the record is set to 3

The program **will not** populate the database table, it will only read, send notification and mark as send. 

It can connect to either Oracle, MySQL, Microsoft SQL Server, Sybase using PDO. 

    Template engine is mustache ( http://mustache.github.io/ ).
    Email engine is swift ( http://swiftmailer.org/ )

**Note**
Example of data_json:
```JSON
'[{"word":"watachikita"},{"word":"triskitsikitsikita"},{"word":"anginara"}]'
```

**Note**
Example template:
```XML
<!DOCTYPE html>
	<head>
	</head>	
	<body>
		<h4> Data </h4>
		{{#.}}
		<p> {{word}} </p>
		{{/.}}
		<hr>
		<span>Powered by Notificator.<br></span>
	</body>		
</html>
```

##Prerequisites##
Requires at least PHP 5.2.x to run, but works great in 5.3, 5.4 and 5.5, too.
 
##Installation##
 - Install PHP 5.2.x or later
     - Make sure the desired PDO extensions are enabled on php.ini, for example for mysql: `extension=php_pdo_mysql.dll`
 - Copy the notificator folder on your machine

##PDO on Windows notes(xampp)##
**MySQL** using **PDO_MYSQL** extension seemed to be installed on xampp by default didn't have to do much work. Here is the code I used for the connection:
```PHP
$connStr = "mysql:host=".$myServer.";dbname=".$myDB; 
$conn = new PDO($connStr,$myUser,$myPass);  
```

**Microsoft SQL Server** using **PDO_SQLSRV** followed the instructions on http://craigballinger.com/blog/2011/08/usin-php-5-3-with-mssql-pdo-on-windows/. Here is the code I used:
```PHP
$connStr = "sqlsrv:Server=".$myServer.";Database=".$myDB; 
$conn = new PDO($connStr,$myUser,$myPass);
```

**Oracle** with **PDO_OCI**. Download and install the proper Oracle Instant Client on your windows machine for example instantclient_12_1 and add its path to PATH in SYSTEM Environmental Variables. Note Oracle supports only 2 versions down so select your client version properly. Do that and then restart your Apache. Here is the code I used:
```PHP
$tns = "(DESCRIPTION=(ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = ".$myServer.")(PORT = 1521)))(CONNECT_DATA=(SID=".$myDB.")))"; 
$connStr = "oci:dbname=".$tns;      
$conn = new PDO($connStr,$myUser,$myPass);  
```

**Sybase** with **PDO_ODBC** Must have Sybase ASE ODBC Driver which comes with the SDK. Here is the code I used:
```PHP
$connStr = "odbc:Driver={Adaptive Server Enterprise};server=".$myServer.";port=".$myPort.";db=".$myDB;
$conn = new PDO($connStr,$myUser,$myPass);  
```

##Basic Usage##
1. The notificator table on the desired database can be populated by a 3rd party application or service. In order to send the notfication the new records must be marked with `status=0`. 
2. Run the php from the command line. For example:
```DOS
 C:\php\php.exe  -f C:\code\notificator\notify.php C:\code\notificator\config\config.php
```

##Config File##
The config file defines the email server and the database server. The program can connect to any type of database as descibed above. 

```PHP
 $notify_options = array(
		"mailserveraddress" => "smtpmail.example.com.cy",   // smtp server location
		"mailserverport" => "25",                           //  smtp server port
		"connection" => array (			
			"server" => "10.10.10.10",  // Location
			"port" => "5000",           // Port
			"user" => "user",           // Database user
			"pass" => "password",       // Password
			"name" => "dbname",         // Database name
			"provider" => "SYBASE"      // available providers are mysql,sqlsrv,SYBASE,oci
		),
		"convertencoding" => true,      // Convert encoding for subject and json_data?
		"in_charset" => "ISO-8859-7",   // Input character set (same as database)
		"out_charset" => "UTF-8",       // Output character set 
		"table" => "notificator",
		"selectquery" => "select id, notif_from, notif_to, subject, notification_method, template, data_json from notificator where status = 1", // Do not change unless you know what you are doing
		"updatequerystatus1" => "update notificator set status=1, send_date=getDate() where status=0", // Do not change unless you know what you are doing
		"updatequerystatus2" => "update notificator set status=2, send_date=getDate() where status=1", // Do not change unless you know what you are doing
		"updatequerystatus3" => "update notificator set status=3, send_date=getDate() where status=1", // Do not change unless you know what you are doing
		"updatequerystatusid" => "update notificator set status=2, send_date=getDate() where id=:id" // Do not change unless you know what you are doing
	);
```
##Database Table used##
```SQL
  create table notificator (
 		id                              int                              identity  ,
 		notif_from                      varchar(1000)                    not null  ,
 		notif_to                        varchar(1000)                    not null  ,
 		subject                         varchar(1000)                    not null  ,
 		notification_method             varchar(100)                     not null  ,
 		template                        varchar(100)                     not null  ,
 		data_json                       varchar(3000)                        null  ,
 		create_date                     datetime                         not null  ,
 		send_date                       datetime                             null  ,
 		status                          tinyint                         DEFAULT  0 not null   
 	)
```
  