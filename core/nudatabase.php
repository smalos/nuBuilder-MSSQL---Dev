<?php 

mb_internal_encoding('UTF-8');

$_POST['RunQuery']		= 0;


$DBDriver				= $_SESSION['nubuilder_session_data']['DB_DRIVER'];
$DBHost					= $_SESSION['nubuilder_session_data']['DB_HOST'];
$DBPort					= $_SESSION['nubuilder_session_data']['DB_PORT'];
$DBName					= $_SESSION['nubuilder_session_data']['DB_NAME'];
$DBUser					= $_SESSION['nubuilder_session_data']['DB_USER'];
$DBPassword				= $_SESSION['nubuilder_session_data']['DB_PASSWORD'];
$DBCharset				= $_SESSION['nubuilder_session_data']['DB_CHARSET'];

try {
	// MySQL + MSSQL DSN
	$dsn = $DBDriver != 'sqlsrv' ? "mysql:host=$DBHost;dbname=$DBName;charset=$DBCharset" : "sqlsrv:server=$DBHost,$DBPort;Database=$DBName;ConnectionPooling=0";
	$nuDB = new PDO($dsn, $DBUser, $DBPassword, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $DBCharset"));
	$nuDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	echo 'Connection failed: ' . $e->getMessage();
	die();
}

function nuRunQueryNoDebug($s, $a = array(), $isInsert = false){

	global $nuDB;

	$object = $nuDB->prepare($s);

	try {
		$object->execute($a);
	}catch(PDOException $ex){
		
	}

	if($isInsert){		
		return $nuDB->lastInsertId();
	}else{		
		return $object;	
	}

}

function nuRunQuery($s, $a = array(), $isInsert = false){

	global $DBHost;
	global $DBName;
	global $DBUser;
	global $DBPassword;
	global $nuDB;
	global $DBCharset;	

	if($s == ''){
		$a			= array();
		$a[0]		= $DBHost;
		$a[1]		= $DBName;
		$a[2]		= $DBUser;
		$a[3]		= $DBPassword;
		return $a;
	}

	$object = $nuDB->prepare($s);

	try {
		$object->execute($a);
	}catch(PDOException $ex){

		$user		= 'globeadmin';
		$message	= $ex->getMessage();
		$array		= debug_backtrace();
		$trace		= '';

		for($i = 0 ; $i < count($array) ; $i ++){
			$trace .= $array[$i]['file'] . ' - line ' . $array[$i]['line'] . ' (' . $array[$i]['function'] . ")\n\n";
		}

		$debug	= "
===USER==========

$user

===PDO MESSAGE=== 

$message

===SQL=========== 

$s

===BACK TRACE====

$trace

";

		$_POST['RunQuery']		= 1;
		nuDebug($debug);
		$_POST['RunQuery']		= 0;

		$id						= $nuDB->lastInsertId();
		$GLOBALS['ERRORS'][]	= $debug;

		return -1;

	}

	if($isInsert){

		return $nuDB->lastInsertId();

	}else{

		return $object;

	}

}

function db_is_auto_id($table, $pk){

	if (nuMSSQL()) {
		$s = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' AND COLUMN_NAME = '$pk'";
		return; // xx
	}
	$s		= "SHOW COLUMNS FROM `$table` WHERE `Field` = '$pk'";
	$t		= nuRunQuery($s);									//-- mysql's way of checking if its an auto-incrementing id primary key
	$r		= db_fetch_object($t);
	
	return $r->Extra == 'auto_increment';

}

function db_fetch_array($o){

	if (is_object($o)) {
		return $o->fetch(PDO::FETCH_ASSOC);
	} else {		
		return array();
	}

}

function db_fetch_object($o){

	if (is_object($o)) {
		return $o->fetch(PDO::FETCH_OBJ);
	} else {
		return false;
	}

}

function db_fetch_row($o){

	if (is_object($o)) {
		return $o->fetch(PDO::FETCH_NUM);
	} else {
		return false;
	}

}

function db_field_info($n){

	$fields		= array();
	$types		= array();
	$pk			= array();

	$s		= nuDescribeTableSQL($n);
	$t		= nuRunQuery($s);

	while($r = db_fetch_row($t)){

		$fields[] = $r[0];
		$types[] = $r[1];

		if($r[3] == 'PRI'){
			$pk[] = $r[0];
		}

	}

	return array($fields, $types, $pk);

}	

function db_field_names($n){

	$a	= array();
	$s		= nuTableInfoSQL()."$n";
	$t	= nuRunQuery($s);

	while($r = db_fetch_row($t)){
		$a[] = $r[0];
	}

	return $a;

}


function db_field_types($n){

	$a		= array();
	$s		= nuTableInfoSQL()."$n";
	$t		= nuRunQuery($s);

	while($r = db_fetch_row($t)){
		$a[] = $r[1];
	}

	return $a;

}


function db_primary_key($n){

	$a		= array();
	$s		= nuTableInfoSQL()."$n";
	$t		= nuRunQuery($s);

	while($r = db_fetch_row($t)){
		
		if($r[3] == 'PRI'){
			$a[] = $r[0];
		}
		
	}

	return $a;

}

function nuDBQuote($s) {

	global $nuDB;
	return $nuDB->quote($s);

}

function db_num_rows($o) {

	if(!is_object($o)){return 0;}
		
	return $o->rowCount();
	
}

function nuDebugResult($t){
	
	if(is_object($t)){
		$t	= print_r($t,1);
	}

	$i		= nuID();
	$s		= "INSERT INTO zzzzsys_debug (zzzzsys_debug_id, deb_message, deb_added) VALUES (? , ?, ?)";

	nuRunQuery($s, array($i, $t, time()));
	
	return $i;
}

function nuDebug($a){
	
	$date				= date("Y-m-d H:i:s");
	$b					= debug_backtrace();
	$f					= $b[0]['file'];
	$l					= $b[0]['line'];
	$m					= "$date - $f line $l\n\n<br>\n";

	$nuSystemEval				= '';
	if ( isset($_POST['nuSystemEval']) ) {
		$nuSystemEval			= $_POST['nuSystemEval'];
	}
	$nuProcedureEval			= '';
	if ( isset($_POST['nuProcedureEval']) ) { 
		$nuProcedureEval		= $_POST['nuProcedureEval'];
	}

	if($_POST['RunQuery'] == 1){
		$m				= "$date - SQL Error in <b>nuRunQuery</b>\n\n<br>\n" ;
	}else{
		$m				= "$date - $nuProcedureEval $nuSystemEval line $l\n\n<br>\n" ;
	}

	for($i = 0 ; $i < count(func_get_args()) ; $i++){

		$p				= func_get_arg($i);

		$m				.= "\n[$i] : ";

		if(gettype($p) == 'object' or gettype($p) == 'array'){
			$m			.= print_r($p,1);
		}else{
			$m			.= $p;
		}

		$m				.= "\n";

	}
	
	nuDebugResult($m);

}

function nuLog($s1, $s2 = '', $s3 = '') {

	$dataToLog = array(date("Y-m-d H:i:s"), $s1, $s2, $s3);
	
	$data = implode(" - ", $dataToLog);
	// $data = print_r($dataToLog, true); 

	file_put_contents('..\nulog.txt', $data.PHP_EOL , FILE_APPEND | LOCK_EX);
	
}	

function nuID(){
	
	global $DBUser;
	$i	= uniqid();
	$s	= md5($i);

	while($i == uniqid()){}

	$prefix = $DBUser == 'nudev' ? 'nu' : '';
	return $prefix.uniqid().$s[0].$s[1];

}

// This function will return the quote character required when quoting database table names and field names that have spaces in them.
// Return The Identifier quote character required.
function nuIdentCol($s) {

	global $DBDriver;
	return $DBDriver != 'sqlsrv' ? '`'.$s.'`' : '['.$s.']';

}

function nuMSSQL() {

	global $DBDriver;
	return $DBDriver == 'sqlsrv';

}

function nuTableInfoSQL() {

	return nuMSSQL() ? 'sp_columns ' : 'DESCRIBE ';

}

function nuDescribeTableSQL($table) {
	
	if (! nuMSSQL()) return "DESCRIBE ".$table;
	
	return "
		SELECT   
		   C.column_name AS [Field],
		   DATA_TYPE + CASE
						 WHEN CHARACTER_MAXIMUM_LENGTH IS NULL THEN ''
						 WHEN CHARACTER_MAXIMUM_LENGTH > 99999 THEN ''
						 ELSE '(' + Cast(CHARACTER_MAXIMUM_LENGTH AS VARCHAR(5)) + ')' 
					   END AS [Type],
		   IS_NULLABLE AS [Null],
		   Case When CONSTRAINT_TYPE = 'PRIMARY KEY' THEN 'PRI' ELSE '' END as [Key]
		FROM
		   INFORMATION_SCHEMA.Columns C 
		   JOIN
			  INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE U 
			  ON U.TABLE_NAME = C.table_name 
		   JOIN
			  INFORMATION_SCHEMA.TABLE_CONSTRAINTS T 
			  ON U.CONSTRAINT_NAME = T.CONSTRAINT_NAME 
		WHERE
		   C.table_name = '$table'
		   and C.TABLE_CATALOG = DB_NAME()
	";   

}



function nuSchemaWhereCurrentDBSQL() {

	return nuMSSQL() ? ' TABLE_CATALOG = db_name() ' : ' table_schema = DATABASE() ';

}

function nuCreateTableFromSelectSQL($table, $select) {
	
	if (! nuMSSQL()) return "CREATE TABLE $table $select";
	
	$pos = strrpos( $select, 'FROM' );
	$selectInto = substr( $select, 0, $pos ) .  ' INTO [' . $table . '] ' . substr( $select, $pos );	

	return $selectInto;
	
}

?>
