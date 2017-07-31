<?php

	/**
	 * File: Database.php
	 * Author: David@Refoua.me
	 * Version: 0.6.2
	 */
	 
	if ( basename($_SERVER['PHP_SELF']) == basename(__FILE__) ) {
		header('Content-Type: text/plain');
		error_reporting(E_ALL); ini_set('display_errors', 'On');
	}
	
	// Check if all the required extensions are present
	foreach ( ['PDO', 'pdo_mysql'] as $extension ) if( !extension_loaded($extension) ) {
		trigger_error ("The required '$extension' extension, is not enabled.");
		die ("\n");
	}
	
	// TODO: __commentme__
	// TODO: move $db_name after $db_password, also add $PDO_options
	function dbInit($dsn, $db_name = null, $db_username = null, $db_password = null) {
		
		// Remove all whitespace, tabs and newlines
		$dsn = preg_replace( '|\s+|', '', $dsn );
	
		try {
			$db = new PDO($dsn, $db_username, $db_password, [
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			]);
			
			// $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			if ( !empty($db_name) ) dbSelect($db, $db_name);
		}

		catch( PDOException $e ) {
			$db = null;
			
			$code = $e->getCode();
			echo 'Line ' . $e->getLine() . ': ' . $e->getMessage();
		}
	
		// Return Database handle
		return $db;
	}
	
	// TODO: add try {} catch and handle errors
	// TODO: ($db instanceof PDO) === true
	function dbSelect( $db, $db_name, $create = false ) {
		
		// First sanitize the db name, just in case
		$db_name = sanitizeName($db_name);
		
		// Check if the specified Database exist
		if ( !$create ) $db->exec("USE `$db_name`;");
		
		// Make sure that we have write access and database actually exists
		$db->exec("CREATE DATABASE IF NOT EXISTS `$db_name`; USE `$db_name`;");
		
		// Set the default encoding to UTF-8
		$db->exec('set names utf8');
		
		return $db;
		
	}
	
	/** Deprecated, all of these methods will be replaced by OOP class ASAP. */
	function useHandle( $dbHandle ) {
		
		global $db, $dbLast;
		
		if ( ($dbHandle instanceof PDO) === true ) {
			list($db, $dbLast) = array($dbHandle, $db);
			
			return true;
		}
		
		return false;
		
	}
	
	function getHandle() {
		
		global $db;
		
		if ( ($db instanceof PDO) === true ) {
			return $db;
		}
		
		return null;
		
	}
	
	function formatSQL( $sql ) {
		
		global $db;
		
		// First, trim any useless spaces
		$sql = trim( preg_replace( '|\s+|', ' ', $sql ) );
		
		// Replace any empty selection i.e. INSERT INTO `table_name` ()
		$sql = preg_replace( '|(`\w+`)\s*\(\s*\)|iU', '$1', $sql );
		
		// Remove any empty clause i.e. WHERE()
		$sql = preg_replace( '|\b\w+\b\s*\(\s*\)|iU', '', $sql );
		
		// If the LIMIT amount is set to INF, remove the clause
		$sql = str_replace( 'LIMIT INF', '', $sql);
		
		// Remove additional white-spaces and keep only one semicolon 
		$sql = trim( trim($sql), ';' ) . ';';
		
		// Check for server-specific corrections
		$dbDriver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
		
		// Microsoft SQL Server based queries
		if ( in_array($dbDriver, ['sqlsrv', 'mssql', 'dblib']) ) {
			
			// Change the "`..`" format to "[...]" format
			$sql = preg_replace( '|\`([^\`]+)\`|iU', '[\1]', $sql );
			
			// Change "LIMIT n" to "TOP n" format
			$sql = preg_replace_callback( '@(?:^|;)(?<clause>\w+)\s+(?<parameters>[^\;]+)\s+LIMIT (?<limit>\w+)\;@iU',
				function($section) { return "${section['clause']} TOP ${section['limit']} ${section['parameters']}"; }
			, $sql );
			
			// Remove additional white-spaces and keep only one semicolon 
			$sql = trim( trim($sql), ';' ) . ';';
			
		}
		
		//echo $sql; exit;
		
		return $sql;
		
	}
	
	function preparePost( &$post, $data, $prefix = '', $opr = '=' ) {
		
		$prefix  = sanitizeName  ( $prefix );
		$data    = sanitizeArray ( $data );
		$isAssoc = count(array_filter(array_keys($data), 'is_string')) > 0;
		$isSeq   = array_keys($data) === range(0, count($data) - 1);
		
		if ( !empty($prefix) ) $prefix .= '_';
		if ( empty($post) ) $post = [];
		
		if ( $isAssoc ) {
			$fields  = array_keys($data);
			$pattern = ( empty($opr) ? ":$prefix*" : "`*` $opr :$prefix*" );
			$values  = array_set( $pattern, array_keys($data) );
			foreach ( $data as $key=>$value ) $post[$prefix.$key] = $value;
		} else
		if ( $isSeq ) {
			$fields  = array();
			$values  = array_fill( 0, count($data), '?' );
			$post    = array_values($data);
		} else {
			die("Database.php: Not supported yet!"); // TODO: for any array like array( 3=>'third row', 5=>'fifth row' )
		}
		
		/*
		// TODO: instead of NULL, use DEFAULT for this
		for ( $i=0; $i<count($data); $i++ )
			if ( $data[$i] === NULL) {
				unset($post[$i]);
				$values[$i] = 'NULL';
			}
		*/
			
		return array($fields, $values);
		
	}
	
	function buildWhere( $filters ) {
		$where = [];
		
		foreach( $filters as $key=>$value ) {
			$opr = sanitizeOpr( preg_match( '@^.+\[([^\[\]]+)]$@iU', trim($key), $matches ) ? array_pop($matches) : '=' );
			$key = sanitizeName( preg_replace( '@\[([^\[\]]+)]@iU', '', $key) );
			$where []= str_replace( '*', $key, "`*` $opr ?" );
		}
		
		return implode(' AND ', $where);
	}
	
	//die( buildWhere( [ 'name[NOT]'=>NULL ] ) );
	
	function dbExec( $sql ) {
		GLOBAL $db;
		
		if ( ($db instanceof PDO) === true ) {
			$count   = $db->exec( formatSQL($sql) );
			return $count;
		}
	}
	
	function dbQuery( $sql ) {
		GLOBAL $db;
		
		if ( ($db instanceof PDO) === true ) {
			$result  = $db->query( formatSQL($sql) );
			return $result;
		}
	}
	
	function dbMake( $table, $columns ) {
		GLOBAL $db;
		
		$table   = sanitizeName  ( $table );
		$columns = sanitizeArray ( $columns );
		$columns = array_map('sanitizeType', $columns);
		
		if ( ($db instanceof PDO) === true ) {
			$columns = implode( ', ', array_use('`?` *', $columns) );
			$sql     = ("CREATE TABLE IF NOT EXISTS `$table` ($columns)");
			$count   = $db->exec( formatSQL($sql) );
			return ($count === 0);
		}
		
	}
	
	function dbDestroy( $table ) {
		GLOBAL $db;
		
		$table   = sanitizeName  ( $table );
		
		if ( ($db instanceof PDO) === true ) {
			$sql     = ("DROP TABLE IF EXISTS `$table`");
			$count   = $db->exec( formatSQL($sql) );
			return ($count === 0);
		}
		
	}
	
	// TODO: $limit = implode( ', ', [$offset, $limit] );
	// Examples: 
	// SELECT * FROM Orders LIMIT 5 # Retrieve first 5 rows
	// SELECT * FROM Orders LIMIT 10 OFFSET 15
	// SELECT * FROM Orders LIMIT 15, 10 # Retrieve rows 16-25
	// SELECT * FROM Orders LIMIT 5,10;  # Retrieve rows 6-15
	// LIMIT row_count is equivalent to LIMIT 0, row_count.
	
	// TODO: COUNT(*)
	// https://stackoverflow.com/a/1893431/1454514
	// function dbCount( $table, $filters = [] ) {
	// 		return $count;
	// }
	
	// TODO: ORDER BY
	// SELECT column_name FROM Orders WHERE condition ORDER BY col1 ASC, col2 DESC
	// SELECT * FROM CUSTOMERS ORDER BY NAME, SALARY;
	// SELECT * FROM CUSTOMERS ORDER BY NAME DESC;
	// SELECT * FROM Orders WHERE OrderDate >= '1980-01-01' ORDER BY OrderDate
	
	// For pagination, read example: http://www.xarg.org/2011/10/optimized-pagination-using-mysql/
	
	function dbRead( $table, $filters = [], $limit = INF ) {
		GLOBAL $db;
		
		$limit   = sanitizeInt   ( $limit );
		$table   = sanitizeName  ( $table );
		//$filters = sanitizeArray ( $filters );
		
		if ( ($db instanceof PDO) === true ) {
			$fields  = '*'; // TODO: For now, everything. To be changed later.
			//$where   = implode(' AND ', array_set("`*` = ?", array_keys($filters)));
			$where   = buildWhere( $filters );
			$sql     = ("SELECT $fields FROM `$table` WHERE ($where) LIMIT $limit");
			$stmt    = $db->prepare( formatSQL($sql) );
			$success = $stmt->execute( array_values($filters) );
			$result  = $stmt->fetchAll( PDO::FETCH_ASSOC );
			$count   = $stmt->rowCount();
			return $result;
		}
		
	}
	
	function dbPrepare( $table, $filters = [], $limit = INF ) {
		GLOBAL $db;
		
		$limit   = sanitizeInt   ( $limit );
		$table   = sanitizeName  ( $table );
		//$filters = sanitizeArray ( $filters );
		
		if ( ($db instanceof PDO) === true ) {
			$fields  = '*'; // TODO: For now, everything. To be changed later.
			//$where   = implode(' AND ', array_set("`*` = ?", array_keys($filters)));
			$where   = buildWhere( $filters );
			$sql     = ("SELECT $fields FROM `$table` WHERE ($where) LIMIT $limit");
			$stmt    = $db->prepare( formatSQL($sql) );
			$success = $stmt->execute( array_values($filters) );
			$count   = $stmt->rowCount();
			return $stmt;
		}
		
	}
	
	function dbFetch( $stmt ) {
		GLOBAL $db;
		
		if ( ($db instanceof PDO) === true ) {
			$result  = $stmt->fetch( PDO::FETCH_ASSOC );
			return $result;
		}
		
	}
	
	function dbGetRow( $table, $filters = [] ) {
		GLOBAL $db;
		
		$rows = dbRead( $table, $filters, 1 );
		return array_pop($rows);
		
	}
	
	function dbAdd( $table, $data ) {
		GLOBAL $db;
		
		$table   = sanitizeName  ( $table );
		$data    = sanitizeArray ( $data );
		
		if ( ($db instanceof PDO) === true ) {
			list($fields, $values) = preparePost( $post, $data, 'insert', false );
			$fields  = implode(', ', $fields );
			$values  = implode(', ', $values );
			$sql     = ("INSERT INTO `$table` ($fields) VALUES ($values)");
			$stmt    = $db->prepare( formatSQL($sql) );
			$success = $stmt->execute( $post );
			$count   = $stmt->rowCount();
			$id      = $db->lastInsertId();
			return $success && ($count === 1);
		}
	}
	
	// NOTE: $count returns how many updated with new data in MySQL, and how many totally affected in MSSQL
	
	function dbWrite( $table, $filters = [], $data ) {
		GLOBAL $db;
		
		$table   = sanitizeName  ( $table );
		$filters = sanitizeArray ( $filters );
		$data    = sanitizeArray ( $data );
		
		if ( ($db instanceof PDO) === true ) {
			$clause  = implode(', ', preparePost( $post, $data, 'set' )[1] );
			$where   = implode(' AND ', preparePost( $post, $filters, 'where' )[1] );
			$sql     = ("UPDATE `$table` SET $clause WHERE ($where)");
			$stmt    = $db->prepare( formatSQL($sql) );
			$success = $stmt->execute( $post );
			$count   = $stmt->rowCount();
	
			return ($success ? $count : FALSE);
		}
		
	}
	
	function dbRemove( $table, $filters = [] ) {
		GLOBAL $db;
		
		$table   = sanitizeName  ( $table );
		$filters = sanitizeArray ( $filters );
		
		if ( ($db instanceof PDO) === true ) {
			$where   = implode(' AND ', array_set("`*` = ?", array_keys($filters)));
			$sql     = ("DELETE FROM `$table` WHERE ($where)");
			$stmt    = $db->prepare( formatSQL($sql) );
			$success = $stmt->execute( array_values($filters) );
			$count   = $stmt->rowCount();
			
			return $success;
		}
		
	}
	
	function array_build( $glue, $array ) {
		$output = [];
		foreach ( $array as $key=>$value ) $output []= implode( $glue, array($key, $value) );
		return $output;
	}
	
	function array_use( $pattern, $array ) {
		$output = []; foreach ( $array as $key=>$value ) $output [] = 
		str_replace('?', $key, str_replace('*', $value, $pattern));
		return $output;
	}
	
	/*
	function array_set( $replacement, $array ) {
		$output = []; foreach ( $array as $key=>$value )
		$output [    str_replace('@', $key, $replacement) ]=
			         str_replace('*', $value, $replacement);
		return $output;
	}
	*/
	
	function array_set( $replacement, $array ) {
		return array_map(function($key) use(&$replacement) {
			return str_replace('*', $key, $replacement);
		}, $array);
	}
	
	/*
	function array_set( $replacement, $array ) {
		$output = ( preg_match('|[\@\*]|', $replacement) ) ?
		function( $output = [] ) use(&$replacement, &$array) {
			foreach ( $array as $key=>$value ) $output [str_replace('@', $key, $replacement)]= str_replace('*', $value, $replacement);
			return $output;
		} :
		array_map(function($key) use(&$replacement) {
			return str_replace('*', $key, $replacement);
		}, $array);
		return $output;
	}
	
	function array_remap( $replacement, $array ) {
		$output = [];
		foreach ( $array as $key=>$value ) $output [str_replace('*', $key, $replacement)]= ($value);
		return $output;
	}
	*/
	
	function sanitizeInt( $input, $intOnly = false ) {
		
		// Return negative and positive Infinity as-is
		if ( abs($input) === INF ) return $input;
		
		// Remove any kind of whitespace, and/or comma digit separators
		$input = preg_replace( '/[\s|\,]+/', '', (string) $input );
		
		// Remove any non-digit characters
		$input = (float) preg_replace( '|[^\d\-\.e]|iU', '', $input );
		
		// If float isn't accepted, round the number
		if ( $intOnly ) $input = (int) round($input); else
		if ( $input == (int)$input ) $input = intval($input);
		
		return $input;
	}
	
	function sanitizeOpr( $input ) {
		
		// Trim all whitespace characters
		$input = trim($input);
		
		// Remove all invalid characters
		// TODO: complete this
		//$input = preg_replace( '|[^\w\s\[\]]+|', '', $input );
		
		// Truncate to 64 characters
		$input = substr( $input, 0, 64 );
		
		return $input;
	}
	
	function sanitizeName( $input ) {
		
		$output = trim($input);
		
		// Changes any whitespace to underscore characters
		$output = preg_replace( '|\s+|', '_', $output );
		
		// Remove all non-alphanumeric and underscore characters
		$output = preg_replace( '|[^\w\-]+|', '', $output );
		
		// Check if the value should be an integer
		if ( !preg_match( '|^[\d\-\.\+\ \,]+$|', $output ) )

		// Remove numbers from the beginning of the variable
		$output = preg_replace( '|^\d+|', '', $output );
		
		// Otherwise, the value should be an integer
		else $output = sanitizeInt( $output );
		
		// Truncate to 64 characters
		$output = substr( $output, 0, 64 );
		
		return $output;
	}
	
	function sanitizeType( $input ) {
		
		// Remove all invalid characters
		$input = preg_replace( '|[^\w\s\(\)]+|', '', $input );
		
		// Remove excess white-spaces
		$input = preg_replace( '|[\s]+|', ' ', $input );
		
		return trim( $input );
	}
	
	function sanitizeArray( $input ) {
		$output = [];
		foreach ( (array) $input as $name=>$value ) $output [ is_int($name) ? sanitizeInt($name) : sanitizeName($name) ] = is_null($value) ? NULL : (string) $value; // TODO: if ( !( is_string($value) || is_int($value) || is_null($value) ) ) trigger_error("Invalid type");
		return $output;
	}
	
	function sanitizeOutput( $input ) {
		$input = htmlentities( $input );
		return $input;
	}
	
	/* TODO: remove this
	//$clause  = implode(', ', array_build(' = ', array_combine( array_set('`*`', $fields), array_set('ppp*', $values) )));
	function processFilters( $arr ) {
		if ( count(array_filter(array_keys($arr), function($val) {return is_int($val);})) == count($arr) ) $raw = $arr;
		else { $raw = []; foreach ($arr as $key=>$val) { $raw []= $key; $raw []= $val; } }
		return $raw;
	}
	*/
	
	//================================================
	// -------- Sandboxing New Functions Area --------
	//================================================
	
	if ( basename($_SERVER['PHP_SELF']) == basename(__FILE__) ) {
		
		if ( !realpath('./dADroid.php') )
			die("Error: Can not call this file directly.");
		
		else {

			$db_dsn = "mysql:host=localhost;port=3306;charset=utf8"; //dbname=
			$db_name = "workspace"; //"dADroid";
			$db_username = "main";
			$db_password = "letmein";
			
			$db = dbInit( $db_dsn, $db_name, $db_username, $db_password );
			// ($db instanceof PDO) === true
			
			if ($db) {
				// Connection is OK, do the query here.
				
				//$data = dbRead('users', ['uid'=>1]);
				//var_dump($data); exit;				
				
				/*
				
				// INSERT...ON DUPLICATE KEY UPDATE (only supported on MySQL)
				// INSERT IGNORE, INSERT OR REPLACE, INSERT OR IGNORE
				// $query = "INSERT INTO myTable SET fname='Fname', lname='Lname', website='Website', demo=DEFAULT";
				
				try {
					$db = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
					$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$sql = "INSERT INTO MyGuests (firstname, lastname, email) VALUES ('John', 'Doe', 'john@example.com')";
					$db->exec($sql); // use exec() because no results are returned
					echo "New record created successfully";
				}
				catch (PDOException $e) {
					echo $sql . "<br>" . $e->getMessage();
				}
				
				$uid    = 10;
				$sql    = "SELECT * FROM users WHERE uid = :user_id";
				$stmt   = $db->prepare($sql);
				$result = $stmt->execute( [":user_id" => intval($uid)] );
				$user   = $stmt->fetch(PDO::FETCH_ASSOC); // or fetchAll
				echo $stmt->rowCount() . " records SELECTED successfully: \n";
				
				echo json_encode($user, JSON_PRETTY_PRINT);

				$stmt = $dbh->prepare("SELECT * FROM REGISTRY where name = ?");
				if ( $stmt->execute( [ $_REQUEST['name'] ] ) ) {
					while ($row = $stmt->fetch()) {
						print_r($row);
					}
				}
				
				// sql to delete a record
				$sql = "DELETE FROM MyGuests WHERE id=3";
				
				// =, <>, >, >=, <, <=, IN, BETWEEN, LIKE, IS NULL, IS NOT NULL
				// WHERE  mycol > 100
				// WHERE  mycol IS NULL OR mycol = 100
				// WHERE  mycol > 100 AND item = 'Hammer'
				// WHERE ename IN ('value1', 'value2', ...)
				// WHERE ename='value1' OR ename='value2'
				// WHERE ename BETWEEN 'value1' AND 'value2'
				// WHERE salary BETWEEN 5000 AND 10000
				// WHERE ename LIKE 'S%'
				// WHERE ename LIKE '%A_E%'
				// WHERE ename LIKE '[a-zA-Z0-9_]%'
				
				$sql = '
					UPDATE `access_users`   
					   SET `contact_first_name` = :firstname,
						   `contact_surname` = :surname,
						   `contact_email` = :email,
						   `telephone` = :telephone 
					 WHERE `user_id` = :user_id -- you probably have some sort of id
					';
				
				//NOTE that bindValue differs from bindParam,
				// bindParam makes a reference but bindValue gives the evaluated variable to function
				
				$statement = $conn->prepare($sql);
				$statement->bindValue(":firstname", $firstname);
				$statement->bindValue(":surname",   $surname);
				$statement->bindValue(":email",     $email);
				$statement->bindValue(":telephone", $telephone);
				$count = $statement->execute();
				
				PDOStatement::rowCount()
				*/
			}
		}
	}

	
return;

if ($_POST['go'] == "add") {
	mysql_query("SET NAMES 'utf8'");
	$add = mysql_query("INSERT INTO `bans` VALUES ('', '" . sanitize_text($_POST['ip']) . "' , '" . time() . "' ,'" . sanitize_text($_POST['reason']) . "','" . sanitize_text($_POST['redirect']) . "','" . sanitize_text($_POST['url']) . "' , '" . sanitize_text($_SESSION['name']) . "')");
	$status = ($add ? '<div class="panel panel-success"><div class="panel-heading">محدوديت با موفقيت ثبت شد.</div></div>' : '<div class="panel panel-danger"><div class="panel-heading">مشکلي در ثبت محدوديت به وجود آمده است.</div></div>');
}
elseif ($_POST['go'] == "edit") {
	mysql_query("SET NAMES 'utf8'");
	$query = "UPDATE `bans` SET ";
	$fields = array( 'ip', 'date', 'reason', 'redirect', 'url', 'bannedby' );
	foreach($fields as $item) $query.= (isset($_POST[$item]) ? ("`$item` = '" . sanitize_text($_POST[$item]) . "', ") : "");
	$query = rtrim($query, ' ,') . ' ';
	$query.= "WHERE `id` = '" . sanitize($_POST['id']) . "' LIMIT 1";
	$edit = mysql_query($query);
	$status = ($edit ? '<div class="panel panel-warning"><div class="panel-heading">محدوديت با موفقيت ويرايش شد.</div></div>' : '<div class="panel panel-danger"><div class="panel-heading">مشکلي در ويرايش محدوديت به وجود آمده است.</div></div>');

}

if ( !empty($_GET['edit']) ) {
	$row_bans = @array_pop(dbRead('bans', ['id' => sanitizeInt($_GET['edit']) ], 1));
}


class Connection{

    protected $db;

    public function __construct(){

    $conn = NULL;

        try{
            $conn = new PDO("mysql:host=localhost;dbname=dbname", "dbuser", "dbpass");
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $e){
                echo 'ERROR: ' . $e->getMessage();
                }    
            $this->db = $conn;
    }
    
    public function getConnection(){
        return $this->db;
    }
}

 
/**
 * PHP MySQL BLOB Demo
 */
class DatabeOOP {
 
    const DB_HOST = 'localhost';
    const DB_NAME = 'classicmodels';
    const DB_USER = 'root';
    const DB_PASSWORD = '';
 
    /**
     * Open the database connection
     */
    public function __construct() {
        // open database connection
        $conStr = sprintf("mysql:host=%s;dbname=%s;charset=utf8", self::DB_HOST, self::DB_NAME);
 
        try {
            $this->pdo = new PDO($conStr, self::DB_USER, self::DB_PASSWORD);
            //$conn->exec("set names utf8");
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
 
    /**
     * close the database connection
     */
    public function __destruct() {
        // close the database connection
        $this->pdo = null;
    }
	
   /**
     * insert blob into the files table
     * @param string $filePath
     * @param string $mime mimetype
     * @return bool
     */
    public function insertBlob($filePath, $mime) {
        $blob = fopen($filePath, 'rb');
 
        $sql = "INSERT INTO files(mime,data) VALUES(:mime,:data)";
        $stmt = $this->pdo->prepare($sql);
 
        $stmt->bindParam(':mime', $mime);
        $stmt->bindParam(':data', $blob, PDO::PARAM_LOB);
 
        return $stmt->execute();
    }
	
   /**
     * update the files table with the new blob from the file specified
     * by the filepath
     * @param int $id
     * @param string $filePath
     * @param string $mime
     * @return bool
     */
    function updateBlob($id, $filePath, $mime) {
 
        $blob = fopen($filePath, 'rb');
 
        $sql = "UPDATE files
                SET mime = :mime,
                    data = :data
                WHERE id = :id;";
 
        $stmt = $this->pdo->prepare($sql);
 
        $stmt->bindParam(':mime', $mime);
        $stmt->bindParam(':data', $blob, PDO::PARAM_LOB);
        $stmt->bindParam(':id', $id);
 
        return $stmt->execute();
    }
	
    /**
     * select data from the the files
     * @param int $id
     * @return array contains mime type and BLOB data
     */
    public function selectBlob($id) {
 
        $sql = "SELECT mime,
                        data
                   FROM files
                  WHERE id = :id;";
 
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array(":id" => $id));
        $stmt->bindColumn(1, $mime);
        $stmt->bindColumn(2, $data, PDO::PARAM_LOB);
 
        $stmt->fetch(PDO::FETCH_BOUND);
 
        return array("mime" => $mime,
            "data" => $data);
    }
 
}

// blob example
$blobObj->insertBlob('images/php-mysql-blob.gif',"image/gif");
$blobObj->insertBlob('pdf/php-mysql-blob.pdf',"application/pdf");

$a = $blobObj->selectBlob(1);
header("Content-Type:" . $a['mime']);
echo $a['data']; exit;

$b = $blobObj->selectBlob(2);
header("Content-Type:" . $b['mime']);
echo $b['data']; exit;


class DB
{
    protected static $instance = null;

    protected function __construct() {}
    protected function __clone() {}

    public static function instance()
    {
        if (self::$instance === null)
        {
            $opt  = array(
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => FALSE,
            );
            $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHAR;
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $opt);
        }
        return self::$instance;
    }

    public static function __callStatic($method, $args)
    {
        return call_user_func_array(array(self::instance(), $method), $args);
    }

    public static function run($sql, $args = [])
    {
        $stmt = self::instance()->prepare($sql);
        $stmt->execute($args);
        return $stmt;
    }
}

# Table creation
DB::query("CREATE temporary TABLE pdowrapper (id int auto_increment primary key, name varchar(255))");

# Prepared statement multiple execution
$stmt = DB::prepare("INSERT INTO pdowrapper VALUES (NULL, ?)");
foreach (['Sam','Bob','Joe'] as $name)
{
    $stmt->execute([$name]);
}
var_dump(DB::lastInsertId());
//string(1) "3"

# Getting rows in a loop
$stmt = DB::run("SELECT * FROM pdowrapper");
while ($row = $stmt->fetch(PDO::FETCH_LAZY))
{
    echo $row['name'],",";
    echo $row->name,",";
    echo $row[1], PHP_EOL;
}
/*
Sam,Sam,Sam
Bob,Bob,Bob
Joe,Joe,Joe
*/

# Getting one row
$id  = 1;
$row = DB::run("SELECT * FROM pdowrapper WHERE id=?", [$id])->fetch();
var_export($row);
/*
array (
  'id' => '1',
  'name' => 'Sam',
)
*/

# Getting single field value
$name = DB::run("SELECT name FROM pdowrapper WHERE id=?", [$id])->fetchColumn();
var_dump($name);
//string(3) "Sam"

# Getting array of rows
$all = DB::run("SELECT name, id FROM pdowrapper")->fetchAll(PDO::FETCH_KEY_PAIR);
var_export($all);
/*
array (
  'Sam' => '1',
  'Bob' => '2',
  'Joe' => '3',
)
*/

# Update
$new = 'Sue';
$stmt = DB::run("UPDATE pdowrapper SET name=? WHERE id=?", [$new, $id]);
var_dump($stmt->rowCount());
//int(1)
