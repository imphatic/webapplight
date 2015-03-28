<?php

class frameworkError
{
    private $error_types =
        array(
            1 => array('name' => 'Database Connection Error', 'report' => true),
            2 => array('name' => 'Framework Usage Error', 'report' => false),
            3 => array('name' => 'MySQL Error', 'report' => true)
        );
    protected $error_msg_snippets =
        array(
           1 => "The first argument should be your SQL statement with each input value as a '?' and then each
                 subsequent argument as the type:value.
                 EXAMPLE: fw::prep('SELECT * FROM products WHERE cat_name = ? AND online = ?', 'text:house wares', 'int:1')->select();"
        );


    public function error($message, $type, $line, $file, $shutdown=false)
    {
        $output  = "<strong>ERROR: ";
        $output .= $this->error_types[$type]['name'] .  "</strong> - $message (Line: <strong>$line</strong> in File: <strong>$file</strong>)";

        echo $output;

        //Report database connection errors to idesign central.
        if(!App::get('debug.set') && $type == 1) firebridge::reportError(2, $output, 100, 'N/A', $line, $file);

        if($shutdown) exit();
    }

    public function traceOutError()
    {
        $back_report = debug_backtrace();
        $framework_root = stristr(__FILE__, "/framework/", true) . "/framework/";
        $found = false;
        $return['line'] = __LINE__;
        $return['file'] = __FILE__;
        foreach($back_report as $report) {
            $check_file = substr($report['file'], 0, strlen($framework_root));
            if($check_file != $framework_root && !$found) {
                $found = true;
                $return['line'] = $report['line'];
                $return['file'] = $report['file'];

            }
        }
        return $return;

    }

    public function logSqlError($sqlStatement, $type, $error_msg)
    {
        //Check if error_sql tabel exists and if not then create table
        $this->checkErrorTableExists();

        $uri_parts = explode('?',$_SERVER['REQUEST_URI']);
        $uri = $uri_parts[0];

        $this->prep(array("INSERT INTO " . FRAME_SQL_ERROR_TABLE . "(`sql`, `type`, uri, userIP, errorMsg, getVars, postVars, `date`)
                     VALUES (?,?,?,?,?,?,?,CURRENT_TIMESTAMP)",
            "text:" . $sqlStatement,
            "text:" . $type,
            "text:" . $uri,
            "text:" . $_SERVER['REMOTE_ADDR'],
            "text:" . $error_msg,
            "text:" . serialize($_GET),
            "text:" . serialize($_POST)))->query($this->sql, "ERROR");


        if(!App::get('debug.set'))
        {
            $level = ($type = 'SELECT') ? 25 : 75 ;
            $error = $this->traceOutError();
            firebridge::reportError(2, $error_msg, $level, $uri, $error['line'], $error['file']);
        }
    }

    public function checkErrorTableExists()
    {
        $this->preparedStatement = false;
        $this->result = $this->query("SELECT COUNT(*) AS count FROM information_schema.tables  WHERE table_schema = '" .FRAME_DB_NAME . "' AND table_name = '" . FRAME_SQL_ERROR_TABLE . "'", null, true);
        $this->advanceRow();

        if (!$this->row['count']){
            //table_name doesn't exist so create table
            $this->query("CREATE TABLE ".FRAME_SQL_ERROR_TABLE." (
								  `errorID` int(11) unsigned NOT NULL auto_increment,
								  `sql` text,
								  `type` varchar(50) default NULL,
								  `uri` text default NULL,
								  `userIP` varchar(50) default NULL,
								  `errorMsg` text,
								  `getVars` text default NULL,
								  `postVars` text default NULL,
								  `date` timestamp NULL default NULL,
								  PRIMARY KEY  (`errorID`)
								) ENGINE=InnoDB DEFAULT CHARSET=latin1;", null, true);
        }
    }
}