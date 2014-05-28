<?php
/**
 Notificator - Send email notifications
 --------------------
 
  @author      Constantinos Evangelou <gieglas@gmail.com>
  @copyright   2014 Constantinos Evangelou
  @link        http://gieglas.com
  @license     The MIT License (MIT)
  @version     1.0.0
 
 */

/*must use some kind of scheduler e.g. windows schedule tasks 
C:\xampp\php\php.exe -f C:\code\notificator\notify.php 
*/
require_once 'config/config.php';
require_once 'lib/swift/swift_required.php';
require 'lib/Mustache/Autoloader.php';
Mustache_Autoloader::register();

if (!_is_cli()) {
	echo "This script can only be run from the command line";
} else {		
	echo date("Y-m-d H:i:s"). "| -----------------------  Notificator Started ----------------------- \r\n";
	$Data=array();		
	global $notify_options;	
	try {				
		// execute update status = 1
		$execCommand = _exeCommand($notify_options["updatequerystatus1"],$notify_options["connection"],array(),"Array");
		if ($execCommand["error"]) {
			echo "----- ERROR ------ _exeCommand updatequerystatus1\r\n";
			print_r($execCommand);
			exit;
		}		
		//get the data from the database
		$Data=_getData($notify_options["selectquery"],$notify_options["connection"],array(),"Array");				
		if ($Data["error"]) {
			echo "----- ERROR ------ _getData\r\n";
			print_r($Data);
			exit;
		} else {
			echo "----- Notifications to be sent ------ \r\n";
			print_r($Data);
		}
		// declare mustache engine with a loader in folder "templates"
		$m = new Mustache_Engine(array(
				'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '\templates'),
			));
		//for each entry in data
		foreach ($Data as $res) {
			echo "- Sending notification for record with id: ". $res["id"] ."\r\n";
			// set params for specific record
			$rec=new stdClass();
			$rec->name='id';
			$rec->value=$res["id"];
			$params = array();
			array_push($params,$rec);
			//send email using the data in that row and the specified template			
			$failedRecipients = _sendNotificationEmail($res,$m->render($res["template"],
			json_decode((($notify_options["convertencoding"])? iconv($notify_options["in_charset"], $notify_options["out_charset"], $res["data_json"]) : $res["data_json"])) ));
			if (count($failedRecipients) > 0) {
				echo "----- ERROR ------ _sendNotificationEmail \r\n";
				print_r($failedRecipients);
				exit;
			}			
			// execute update status = 2
			$execCommand = _exeCommand($notify_options["updatequerystatusid"],$notify_options["connection"],$params,"Array");
			if ($execCommand["error"]) {
				echo "----- ERROR ------ _exeCommand updatequerystatusid\r\n";
				print_r($execCommand);
				exit;
			}		
		}
		
	} catch(exception $e) { 
		echo "----- ERROR ------ \r\n";
		echo '{"error":"'. $e->getMessage() .'"}'; 
		//set the status = 3 for all notifications that were not processed
		$execCommand = _exeCommand($notify_options["updatequerystatus3"],$notify_options["connection"],$params,"Array");
		if ($execCommand["error"]) {
			echo "----- ERROR ------ _exeCommand updatequerystatus3\r\n";
			print_r($execCommand);
			exit;
		}		
	} finally {
		echo date("Y-m-d H:i:s"). "| -----------------------  Notificator Finished ----------------------- \r\n";
	}	
}

//-------------------------------------------------------------
//-------PRIVATE
/*Check if it is run on commandline*/
function _is_cli()
{	
	return php_sapi_name() === 'cli';
}

//-------------------------------------------------------------
//-------PRIVATE
/*Send email*/
function _sendNotificationEmail($res,$body){
	global $notify_options;	
	// Create the SMTP configuration
	$transport = Swift_SmtpTransport::newInstance($notify_options["mailserveraddress"], $notify_options["mailserverport"]);
	
	// Create the message
	$message = Swift_Message::newInstance();
	$message->setContentType("text/html");
	$message->setBcc($res["notif_to"]);
	$message->setSubject(_replaceSpecialTags(
		(($notify_options["convertencoding"])? iconv($notify_options["in_charset"], $notify_options["out_charset"], $res["subject"]) : $res["subject"])
		));
	$message->setBody($body);
	$message->setFrom($res["notif_from"]);	
	 
	// Send the email
	$mailer = Swift_Mailer::newInstance($transport);
	$mailer->send($message, $failedRecipients);
	 
	// return failed recipients	
	return $failedRecipients;
}

//-------------------------------------------------------------
//-------PRIVATE
function _replaceSpecialTags($strIn) {
	$now = new DateTime();
	$strOut = $strIn;
	$strOut = str_replace("#dd#", $now->format("d"),$strOut);
	$strOut = str_replace("#mm#", $now->format("m"),$strOut);
	$strOut = str_replace("#yyyy#", $now->format("Y"), $strOut);

	return $strOut;
}

//---------------------------------------------------------------
//-------PRIVATE
function _getData($query,$connection,$parameters = array(),$format="JSON") {
	//run code depending on provider ... e.g. handle differently mySQL with SQL Server and execute
	switch ($connection["provider"])
	{		
	case "mysql":
	case "sqlsrv":
	case "oci":
	case "SYBASE" :
	//----------------------- PDO SERVERS ------------------------
		try {
			//get connection
			$conn = _getOpenPDOConnection($connection);
			//execute the SQL statement and return records
			$st = $conn->prepare($query);

			//add parameters
			foreach($parameters as $parameterData) {							
				$st->bindParam(':'.$parameterData->name,$parameterData->value);						
			}
			
			$st->execute();
			$rs=$st->fetchAll(PDO::FETCH_ASSOC);
			
			//depending on the format return the appropriate type
			if ($format=="JSON") {
				return json_encode($rs);	
			}
			else {
				return $rs;
			}
			
			$rs = null;
			$conn = null;
		} catch(exception $e) { 
			if ($format=="JSON") {
				return '{"error":"'. $e->getMessage() .'"}'; 
			} else {
				return array(
					"error" => $e->getMessage()
				); 
			}			
		} 
		break;
	}
}

//---------------------------------------------------------------
//-------PRIVATE
function _exeCommand($query,$connection,$parameters = array(),$format="JSON") {
//run code depending on provider ... e.g. handle differently mySQL with SQL Server and execute
	switch ($connection["provider"])
	{		
	case "mysql":
	case "sqlsrv":
	case "oci":
	case "SYBASE" :
	//----------------------- PDO SERVERS ------------------------
		try {
			//get connection
			$conn = _getOpenPDOConnection($connection);
			//execute the SQL statement and return records
			$st = $conn->prepare($query);

			//add parameters
			foreach($parameters as $parameterData) {						
				$st->bindParam(':'.$parameterData->name,$parameterData->value);						
			}
			
			$st->execute();
			
			//if no error return success
			//depending on the format return the appropriate type
			if ($format=="JSON") {
				return '{"success":"success"}'; 
			}
			else {
				return array(
					"success" => "success"
				);
			}
			
			$rs = null;
			$conn = null;
		} catch(exception $e) { 
			if ($format=="JSON") {
				return '{"error":"'. $e->getMessage() .'"}'; 
			} else {
				return array(
					"error" => $e->getMessage()
				); 
			}			
		} 
		break;
	}
}

//--------------------------------------------------------------
//-------PRIVATE
/*
	extension=php_pdo_sqlsrv_53_ts.dll in php.ini and Microsoft SQL Server 2008 R2 Native Client http://craigballinger.com/blog/2011/08/usin-php-5-3-with-mssql-pdo-on-windows/
	FOR oci ORACLE, need instantclient_12_1 and add its path to PATH in SYSTEM Enviromental Variables. Note Oracle supports 2 versions down so select your client version properly
	FOR Sybase the PDO_ODBC is used. In order to work must have Sybase ASE ODBC Driver which comes with the SDK. 
*/
function _getOpenPDOConnection($connection) {
	
	$myProvider = $connection["provider"];
	$myServer = $connection["server"];
	$myUser = $connection["user"];
	$myPass = $connection["pass"];
	$myDB = $connection["name"]; 
	$myPort = $connection["port"]; 
	
	//define connection string, specify database driver
	switch ($myProvider) {
	case "mysql":
		$connStr = "mysql:host=".$myServer.";dbname=".$myDB; 
		$conn = new PDO($connStr,$myUser,$myPass);			
		break;
	case "sqlsrv":
		$connStr = "sqlsrv:Server=".$myServer.";Database=".$myDB; 
		$conn = new PDO($connStr,$myUser,$myPass);	
		break;
	case "oci":
		$tns = "(DESCRIPTION=(ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = ".$myServer.")(PORT = ".$myPort.")))(CONNECT_DATA=(SID=".$myDB.")))"; 
		$connStr = "oci:dbname=".$tns; 		
		$conn = new PDO($connStr,$myUser,$myPass);	
		$conn->setAttribute(PDO::ATTR_AUTOCOMMIT,TRUE);		
		break;
	case "SYBASE":
		$connStr = "odbc:Driver={Adaptive Server Enterprise};server=".$myServer.";port=".$myPort.";db=".$myDB;		
		$conn = new PDO($connStr,$myUser,$myPass);	
		break;
	}
	
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $conn;
}

?>