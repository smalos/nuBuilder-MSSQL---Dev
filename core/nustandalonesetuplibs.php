<?php

require_once('nusystemupdatelibs.php'); 

function nuImportNewDB($nuConfigDBDriver) {

	global $nuDB;

	$t = nuRunQuery("SELECT table_name as TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'zzzzsys_object' AND ". nuSchemaWhereCurrentDBSQL() );
	if (db_num_rows($t) == 1) {
		return;
	}

	$sqlFile = $nuConfigDBDriver == "sqlsrv" ? "nubuilder4_mssql.sql" : "nubuilder4.sql"; 
	
	$file = __DIR__."/../".$sqlFile;

		@$handle					= fopen($file, "r");
		$temp						= "";
		if($handle){
			while(($line = fgets($handle)) !== false){
				if($line[0] != "-" AND $line[0] != "/" AND $line[0] != "\n"){
					$line 			= trim($line);
					$temp 			.= $line;
					
					$process = ($nuConfigDBDriver == "sqlsrv" && substr($line, 0, 2) == 'GO') || ($nuConfigDBDriver != "sqlsrv" && substr($line, -1) == ";");
					
					if($process){
						
						
						if ($nuConfigDBDriver == "sqlsrv") $temp = rtrim($temp,'GO');
						
						if ($nuConfigDBDriver != "sqlsrv") { 
							$temp = rtrim($temp,';');
							$temp	= str_replace('ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER','', $temp);
							
							$objList1 = '`information_schema`.`tables`.`TABLE_NAME` AS `zzzzsys_object_list_id` from `information_schema`.`tables` where `information_schema`.`tables`.`TABLE_SCHEMA`';
							$objList2 = '`TABLE_NAME` AS `zzzzsys_object_list_id` from `information_schema`.`tables` where `TABLE_SCHEMA`';
							$temp	= str_replace($objList1, $objList2, $temp);
						}
						
						// echo $temp;
						// echo "<br>________________________________________________________________________________<br>";
						
						nuRunQueryNoDebug($temp);
						
						$temp	= "";
					}
				}
			}
		}
	
	// nuImportLanguageFiles();
}

?>
