<?php
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


?>