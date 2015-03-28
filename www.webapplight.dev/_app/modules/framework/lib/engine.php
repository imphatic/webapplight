<?php

class frameworkEngine extends frameworkError implements Iterator
{
    protected $db_connection;
    protected $objUniqueKey;
    protected $result;
    protected $queryString;
    protected static $fm_object_registration;
    protected $selectArrayIterator;
    protected $preparedStatement = false;
    protected $prepareTypes;
    protected $prepareInputs;
    protected $statement;
    protected $queryError = false;

    public $sql;
    public $id;
    public $table = false;
    public $totalRows = 0;
    public $totalPages;
    public $recordCount;
    public $curPage;
    public $firstPage;
    public $lastPage;
    public $nextPageHREF;
    public $prevPageHREF;
    public $firstPageHREF;
    public $lastPageHREF;
    public $pageHREF;
    public $tableStructureAndInfo;
    public $row;
    public $curRowNumber = 1;

    public $pageVar = "_page";

    function __construct()
    {
        $this->setDatabaseConnection();
    }

    function destruct()
    {
        $this->db_connection->close();
    }

    public function rewind()
    {
        $this->selectArrayIterator = $this->toArray();
    }

    public function current()
    {
        $var = current($this->selectArrayIterator);
        $object = new stdClass();
        foreach ($var as $key => $value)
        {
            $object->$key = $value;
        }
        $object->id = key($this->selectArrayIterator);
        return $object;
    }

    public function key()
    {
        $var = key($this->selectArrayIterator);
        return $var;
    }

    public function next()
    {
        $var = next($this->selectArrayIterator);
        $object = new stdClass();
        if(is_array($var)) {
            foreach ($var as $key => $value)
            {
                $object->$key = $value;
            }
        }
        return $object;
    }

    public function valid()
    {
        $key = key($this->selectArrayIterator);
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }

    private function setDatabaseConnection ()
    {
        $this->db_connection = new mysqli(FRAME_DB_HOSTNAME, FRAME_USER, FRAME_PASSWORD, FRAME_DB_NAME);

        if($this->db_connection->connect_errno > 0)
        {
            $this->error("Unable to connect to database [" . $this->db_connection->connect_error . "]", 1, __LINE__, __FILE__, true);
        }
    }

    public function freeResult()
    {
        if(method_exists($this->result, "free")) {
            $this->result->free();
        } else {
            $this->statement->free_result();
        }
    }

    public function query($sql, $type=NULL, $forceNotPrepared=false)
    {
        $error = false;

        if($this->preparedStatement && !$forceNotPrepared)
        {
            $this->statement = $this->db_connection->prepare($sql);
            if($this->statement)
            {
                $data_array = array_merge(array($this->prepareTypes), $this->prepareInputs);

                if(class_exists("ReflectionClass"))
                {
                    $ref    = new ReflectionClass('mysqli_stmt');
                    $method = $ref->getMethod("bind_param");
                    $method->invokeArgs($this->statement,$this->refValues($data_array));
                } else {
                    call_user_func_array(array($this->statement, "bind_param"), $this->refValues($data_array));
                }
                $this->statement->execute();

                if(MYSQLND_INSTALLED)
                {
                    //This only works with PHP 5.4 and up that have mysqlnd installed.
                    $query = $this->statement->get_result();
                } else {
                    if(in_array($type, array("INSERT", "UPDATE", "DELETE", "ERROR"))) {
                        return true;
                    }
                    $this->statement->store_result();
                    $metaData = $this->statement->result_metadata();

                    $data = $metaData->fetch_field();
                    $this->table = $data->table;

                    $result = array();
                    if ($this->db_bind_array($this->statement, $result) !== FALSE) {
                        return $result;
                    }
                }

            } else {
                $error = true;
            }

        } else {
            $query = $this->db_connection->query($sql);
            $error = (!$query) ? true : false;
        }

        if($error)
        {
            $this->queryError = true;
            $report = $this->traceOutError();
            $this->error($this->db_connection->error . ". SQL: $sql" , 3, $report['line'], $report['file'], false);
            $errorForLog = $this->db_connection->error . " | line: {$report['line']} | file: {$report['file']}";
            $this->logSqlError($sql, $type, $errorForLog);
            return false;
        }

        return $query;
    }
    /*
     * This method is used in the query() method when mysqlnd in not installed.
    */
    function db_bind_array($statement, &$row)
    {
        $md = $statement->result_metadata();
        $params = array();
        while($field = $md->fetch_field()) {
            $params[] = &$row[$field->name];
        }
        return call_user_func_array(array($statement, 'bind_result'), $params);
    }

    /*
     * This method is used in the query() method because the mysqli::bind_param method requires a reference to a
     * variable instead of a value.  Why?  No idea.
    */
    function refValues($arr)
    {
        if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+
        {
            $refs = array();
            foreach($arr as $key => $value)
                $refs[$key] = &$arr[$key];
            return $refs;
        }
        return $arr;
    }

    public function rawQuery($sql)
    {
      $this->sql = $sql;
      $this->result = $this->query($this->sql, "RAW");
      return $this->result;
    }

    public function select($sql = NULL, $limit = NULL)
    {
        $limit = ($this->preparedStatement) ? $sql : $limit ;
        $this->sql = ($this->preparedStatement) ? $this->sql : $sql;

        if(substr(strtolower($this->sql), 0, 7) != "select ")
        {
            $this->table = $this->sql;

            if(strpos($this->sql, ":") > 0)
            {
                $parts  = explode(":", $this->sql);
                $this->table  = $parts[0];

                if($parts[1] == "") $parts[1] = -1;

                if(is_numeric($parts[1]))
                {  // get row by primary id
                    $this->tableStructureAndInfo = $this->getTableInfo($this->table);
                    if(!$this->tableStructureAndInfo) return false;

                    foreach($this->tableStructureAndInfo as $column)
                    {
                        if($column['primary_key']) {
                            $primary = $column['name'];
                        }
                    }

                    $this->sql = "SELECT * FROM `{$this->table}` WHERE `$primary` = ?";
                    $values[0] = $this->sql;
                    $values[1] = "int:" . $parts[1];
                    $this->prep($values);
                } else {
                    // sort results
                    $orderBy = $parts[1];
                    $direction = (isset($parts[2])) ? $parts[2] : "";
                    $this->sql =  "SELECT * FROM `".$this->table."` ORDER BY `$orderBy` $direction ";
                }

            } else {

                $this->sql = "SELECT * FROM `" . $this->sql . "`";
            }
        }

        //Get total rows of unlimited set
        if(!$this->totalRows)
        {
            $checkRows = $this->query($this->sql, "SELECT");
            if(!$checkRows) return false;

            if(!$this->preparedStatement || MYSQLND_INSTALLED)
            {
                $this->totalRows = $checkRows->num_rows;
            } else {
                $this->statement->fetch();
                $this->totalRows = $this->statement->num_rows;
                $this->freeResult();
            }

        }

        //Get the table name
        $this->table = (!$this->table) ? $checkRows->fetch_field()->table : ($this->table);

        //Used for paging urls
        if(!isset(self::$fm_object_registration[$this->table])) self::$fm_object_registration[$this->table] = 0;
        self::$fm_object_registration[$this->table]++;
        $this->objUniqueKey = $this->table . self::$fm_object_registration[$this->table];

        //Limit query and set up record set paging.
        if($limit > 0) $this->selectLimit($limit);

        //START setup recordCount variable
        $openHTML = "<strong>";
        $closeHTML = "</strong>";
        $startRow = ($this->curPage - 1) * $limit;
        if($limit > 0)
        {
            $this->recordCount = $openHTML . ($startRow + 1) . "$closeHTML to $openHTML" . min($startRow + $limit, $this->totalRows) . "$closeHTML of $openHTML" . $this->totalRows . $closeHTML;
        } else {
            $this->recordCount = 1 . " to " . $this->totalRows . " of " . $this->totalRows;
        }
        //END

        $this->result = $this->query($this->sql, "SELECT");
        $this->tableStructureAndInfo = $this->getTableInfo($this->table);
        $this->advanceRow();

        $this->setColumnNameVariables(TRUE);
        if($this->totalRows < 2)
        {
            $this->freeResult();
        }

        $preparedObject = ($this->totalRows) ? $this : false ;
        return ($this->preparedStatement) ? $preparedObject : $this->row;
    }

    private function selectLimit($limit) {
        $this->curPage = (isset($_GET[$this->objUniqueKey . $this->pageVar])) ? $_GET[$this->objUniqueKey . $this->pageVar] : 1;
        $this->sql = sprintf("%s LIMIT %d, %d", $this->sql, (($this->curPage - 1) * $limit), $limit);

        $this->totalPages = ceil($this->totalRows/$limit);
        $this->firstPage = ($this->curPage == 1) ? true : false;
        $this->lastPage = ($this->curPage >= $this->totalPages) ? true : false;

        //Add query params that are not part of framework.
        if($_GET)
        {
            $addParamToQuery = array();
            $skipOver = array($this->objUniqueKey . $this->pageVar);

            foreach($_GET as $param => $value)
            {
                if(!in_array($param, $skipOver))
                {
                    $addParamToQuery[] = "$param=$value";
                }
            }

            $this->queryString = (count($addParamToQuery)) ? "&" . htmlentities(implode("&", $addParamToQuery)) : $this->queryString;
        }

        $stripOut = explode("?",$_SERVER["REQUEST_URI"]);
        $gotoPage = $stripOut[0];
        $this->nextPageHREF = sprintf("%s?" . $this->objUniqueKey . $this->pageVar . "=%d%s", $gotoPage, min($this->totalPages, $this->curPage + 1), $this->queryString);
        $this->prevPageHREF = sprintf("%s?" . $this->objUniqueKey . $this->pageVar . "=%d%s", $gotoPage, max(0, $this->curPage - 1), $this->queryString);
        $this->firstPageHREF = sprintf("%s?" . $this->objUniqueKey . $this->pageVar . "=%d%s", $gotoPage, 1, $this->queryString);
        $this->lastPageHREF = sprintf("%s?" . $this->objUniqueKey . $this->pageVar . "=%d%s", $gotoPage, $this->totalPages, $this->queryString);

        //Set Up pageHREF array
        for ($i = 1; $i <= $this->totalPages; $i++) {
            $this->pageHREF[$i] = sprintf("%s?" . $this->objUniqueKey . $this->pageVar . "=%d%s", $gotoPage,$i, $this->queryString);
        }
    }

    function repeat($while = false) //If $while is set to anything, then the function assumes you are doing a while loop instead of a do-while loop.
    {
        if($this->totalRows == 1) return false;

        if(($this->curRowNumber > 1) && ($while))
        {
            $this->advanceRow();
        } else if (!$while) {
            $this->advanceRow();
        }
        $this->setColumnNameVariables();
        $this->curRowNumber++;
        if($this->row) {
            return true;
        } else {
            $this->freeResult();
            return false;
        }
    }

    function advanceRow()
    {
        if(!$this->preparedStatement || MYSQLND_INSTALLED)
        {
            $this->row = $this->result->fetch_assoc();
        } else {
            if($this->statement->fetch())
            {
                $this->row = $this->result;
            } else {
                $this->row = false;
            }
        }
    }

    function paging($page = NULL, $displayFirstLast = FALSE, $displayPageNums = FALSE, $maxPageNumbers=NULL)
    {
        //Specify a the $page variable if you want to override the default behavior to just page on the current page.
        if(isset($page))
        {
            $gotoPage = $page;
        } else {
            $gotoPage = $_SERVER["REQUEST_URI"];
            $stripOut = explode("?",$gotoPage);
            $gotoPage = $stripOut[0];
        }

        $return = "";
        if($displayFirstLast)
        {
            $return .= "<a href='" . $this->firstPageHREF . "' class='first_page'><span>First</span></a>";
        }

        if (!$this->firstPage)
        {
            $return .= '<a href="' .  sprintf("%s?" . $this->objUniqueKey . $this->pageVar . "=%d%s", $gotoPage, max(0, $this->curPage - 1), $this->queryString) . '" class="prev"><span>&lt; Previous</span></a>';
        }

        if($displayPageNums)
        {
            if(count($this->pageHREF) > 0)
            {

                $maxPageNumbers = ( $maxPageNumbers & 1 ) ? $maxPageNumbers : $maxPageNumbers - 1 ; //makes sure the number is odd.
                $mid = floor($maxPageNumbers/2);

                if($maxPageNumbers > 0 && $this->totalPages > $maxPageNumbers)
                {
                    $startAt = $this->curPage - $mid;
                    $startAt = ($startAt < 1) ? 1 : $startAt;
                    $endAt = $this->curPage + $mid;
                    if($endAt > $this->totalPages)
                    {
                        $amountOver = $endAt - $this->totalPages;
                        $startAt = $startAt - $amountOver;
                        $endAt = $this->totalPages;
                    }
                    $spread = ($endAt - $startAt) + 1;
                    while($spread < $maxPageNumbers)
                    {
                        $spread++;
                        $endAt++;
                    }
                }

                foreach($this->pageHREF as $pageNum => $href)
                {
                    $active = ($pageNum == $this->curPage) ? ' active' : "";

                    if($maxPageNumbers > 0 && $this->totalPages > $maxPageNumbers)
                    {
                        if($pageNum >= $startAt && $pageNum <= $endAt)
                        {
                            $return .= '<a href="' . $href . '" class="page_num'.$active.'"><span>' . ($pageNum) . '</span></a>';
                        } else {
                            unset($this->pageHREF[$pageNum]);
                        }

                    } else {
                        $return .= '<a href="' . $href . '" class="page_num'.$active.'"><span>' . ($pageNum) . '</span></a>';
                    }
                }
            }
        }

        if (!$this->lastPage)
        {
            $return .= '<a href="' . sprintf("%s?" . $this->objUniqueKey . $this->pageVar . "=%d%s", $gotoPage, min($this->totalPages, $this->curPage + 1), $this->queryString) . '" class="next"><span>Next &gt;</span></a>';
        }

        if($displayFirstLast)
        {
            $return .= "<a href='" . $this->lastPageHREF . "' class='last_page'><span>Last</span></a>";
        }
        return $return;
    }

    function toArray()
    {
        $table = array();
        $fallBackRowNum = 0;
        do
        {

            foreach ($this->tableStructureAndInfo as $column)
            {
                $name = $column['name'];
                $index = $this->id;
                if($index > 0) {
                    $table[$index][$name] = $this->$name;
                } else {
                    $table[$fallBackRowNum][$name] = $this->$name;
                }
            }
            $fallBackRowNum++;
        } while($this->repeat());

        return $table;
    }

    function setColumnNameVariables($firstpass = FALSE)
    {

        foreach ($this->tableStructureAndInfo as $column)
        {
            $col = $column['name'];

            /*
            if(isset($this->$col) && ($firstpass) && $col != 'id')
            {
                $this->error("Column name <strong>$col</strong> is a reserved variable by PHP framework and its usage may cause problems with your application.  Either change the column name in your database or refraine from using <strong>\$yourobject->$col</strong><br /> ", 2, __LINE__, __FILE__, false);
            }
            */

            $this->$col = isset($this->row["$col"]) ? $this->row["$col"] : null;
            if($column['primary_key'] == 1)
            {
                $this->id = $this->row["$col"];
            }

        }
    }

    function getTableInfo ($table)
    {
        if (!$this->result) $this->result = $this->query("SELECT * FROM `$table` LIMIT 1", "SELECT", true);
        if (!$this->result) return false;

        $resource = ($this->preparedStatement) ? $this->statement->result_metadata() : $this->result ;

        $tblData = array();
        while ($meta = $resource->fetch_field()) {
            $null = ($meta->flags & 1) ? true : false;
            $primary = ($meta->flags & 2) ? true : false;
            $numeric = ($meta->flags & 32768) ? true : false;


            $tblData[] = array(
                "name"		    => $meta->name,
                "not_null"	    => $null,
                "numeric"	    => $numeric,
                "primary_key"   => $primary,
                "type"		    => $meta->type);
        }

        return $tblData;

    }

    function matchInputDataWithTableData($table, $input)
    {
        $data = array();
        $decimal = array(4,5,246);
        $dates = array(7,10,11,12,13);

        //$tableInfo = ($this->tableStructureAndInfo) ? $this->tableStructureAndInfo : $this->getTableInfo($table);
        $tableInfo =$this->getTableInfo($table);
        foreach ($tableInfo as $column)
        {
            $col = $column['name'];

            $type = ($column['numeric']) ? "int" : "text" ;
            if(in_array($column['type'], $decimal)) { $type = "double"; }

            $value = (isset($input[$col])) ? $input[$col] : NULL ;
            $found = (isset($input[$col])) ? true : false ;

            if($found || $column['primary_key'])
            {
                if($column['primary_key'] && $value == "")
                {
                    $value = (isset($_POST[$col])) ? $_POST[$col] : null;
                }

                $data[$col] = array(
                    'primary'  => $column['primary_key'],
                    'value' => $value,
                    'type'  => $type,
                    'found' => $found
                );
            }

        }

        return $data;
    }

    function insert($sql=NULL, $goto_after_insert=NULL)
    {
        $this->sql = ($this->preparedStatement) ? $this->sql : $sql;
        $goto_after_insert = ($this->preparedStatement) ? $sql : $goto_after_insert ;

        if(substr(strtolower($this->sql), 0, 7) != "insert ")
        {
            $inputs = (isset($_POST)) ? $_POST : '';
            $this->table = $this->sql;

            if(strpos($this->sql, ":") > 0)
            {
                $parts  = explode(":", $this->sql);
                $this->table   = $parts[0];
                $params = $parts[1];
                $shortColumns = explode(",", $params);

                unset($inputs);
                foreach($shortColumns as $shortColumn)
                {
                    $inputs[$shortColumn] = (isset($_POST[$shortColumn])) ? $_POST[$shortColumn] : '' ;
                    $questionMarks[] = "?";
                    $shortColumnsPreped[] = "`$shortColumn`";
                }
            }

            if(!$this->preparedStatement)
            {
                $inputData = $this->matchInputDataWithTableData($this->table , $inputs);

                $values[0] = "";
                unset($questionMarks);
                foreach ($inputData as $name => $column)
                {
                    if(!$column['primary'])
                    {
                        $columns[] = "`" . $name . "`";
                        $values[] = $column['type'] . ":" . $column['value'];
                        $questionMarks[] = "?";
                    }
                }

                $this->sql = "INSERT INTO `" . $this->table  . "` (" . implode(", ",$columns) . ") VALUES (". implode(", ",$questionMarks) .")";

                $values[0] = $this->sql;
                $this->prep($values);
            } else {
                $this->sql = "INSERT INTO `" . $this->table  . "` (" . implode(", ",$shortColumnsPreped) . ") VALUES (". implode(", ",$questionMarks) .")";
            }
        }

        $this->result = $this->query($this->sql, "INSERT");
        $this->id = $this->db_connection->insert_id;

        if(isset($goto_after_insert) && !$this->queryError){
            header("Location: " . $goto_after_insert);
        }

        return $this;
    }

    function update($sql=NULL, $goto_after_update=NULL)
    {
        $this->sql = ($this->preparedStatement) ? $this->sql : $sql;
        $goto_after_update = ($this->preparedStatement) ? $sql : $goto_after_update ;

        if(substr(strtolower($this->sql), 0, 7) != "update ")
        {
            $inputs = (isset($_POST)) ? $_POST : '';
            $this->table  = $this->sql;

            if(strpos($this->sql, ":") > 0)
            {
                $parts  = explode(":", $this->sql);
                $this->table   = $parts[0];
                $params = $parts[1];
                $shortColumns = explode(",", $params);

                unset($inputs);
                foreach($shortColumns as $shortColumn)
                {
                    if((end($shortColumns) == $shortColumn) && $this->preparedStatement)
                    {
                        $primaryPrepared = "`$shortColumn` = ?";
                        $primary = $shortColumn;
                        $inputs[$shortColumn] = $_POST[$shortColumn];
                    } else {
                        $inputs[$shortColumn] = (isset($_POST[$shortColumn])) ? $_POST[$shortColumn] : '';
                        $shotColumnsWithQues[] = "`$shortColumn` = ?";
                    }
                }
            }

            if(!$this->preparedStatement)
            {
                $inputData = $this->matchInputDataWithTableData($this->table , $inputs);

                $values[0] = "";
                foreach ($inputData as $name => $column)
                {
                    if(!$column['primary'])
                    {
                        $columns[] = "`" . $name . "`= ?";
                        $values[] = $column['type'] . ":" . $column['value'];
                    } else {
                        $primary = $name;
                        $inputs[$name] = $column['value'];
                        $primaryInput[] = $column['type'] . ":" . $column['value'];
                    }
                }

                $values = array_merge($values, $primaryInput); //This seems weird, but we need the primary input to be last.

                $this->sql = "UPDATE `" . $this->table  . "` SET " . implode(", ", $columns) . " WHERE `" . $primary . "` = ?";

                $values[0] = $this->sql;
                $this->prep($values);
            } else {
                $this->sql = "UPDATE `" . $this->table  . "` SET " . implode(", ",$shotColumnsWithQues) . " WHERE " . $primaryPrepared;
            }

            if(!isset($inputs[$primary]))
            {
                $msg = "Trying to update your table but it looks like you didn't POST the primary key $primary
                    with your form submission.  Create a hidden form element with the name set to $primary and set its value to the
                    ID of the row you are wanting to update. ";
                $this->error($msg, 2, __LINE__, __FILE__, true);
            }
        }
        $this->result = $this->query($this->sql, "UPDATE");
        $this->id = (isset($primary) && isset($inputs[$primary])) ? $inputs[$primary] : NULL;

        if(isset($goto_after_update) && !$this->queryError)
        {
            header("Location: " . $goto_after_update);
        }
    }

    function delete($sql=NULL, $goto_after_delete=NULL, $delete_file=NULL)
    {
        $this->sql = ($this->preparedStatement) ? $this->sql : $sql;
        $goto_after_delete = ($this->preparedStatement) ? $sql : $goto_after_delete;
        $delete_file = ($this->preparedStatement) ? $sql : $delete_file ;

        if(substr(strtolower($this->sql), 0, 7) != "delete ")
        {
            $inputs = (isset($_POST)) ? $_POST : '';
            $this->table  = $this->sql;

            if(strpos($this->sql, ":") > 0)
            {
                $parts  = explode(":", $this->sql);
                $this->table  = $parts[0];
                $this->tableStructureAndInfo = $this->getTableInfo($this->table);

                if(is_numeric($parts[1]))
                {  // delete row by primary id
                    foreach($this->tableStructureAndInfo as $column)
                    {
                        if($column['primary_key']) {
                            $primary = $column['name'];
                        }
                    }

                    $this->sql = "DELETE FROM `{$this->table}` WHERE `$primary` = ?";
                    $values[0] = $this->sql;
                    $values[1] = "int:" . $parts[1];
                    $this->prep($values);
                } else {
                    // delete row by other column and value
                    $delCol = $parts[1];
                    $delValue = $parts[2];
                    $input[$delCol] = $delValue;
                    $this->result = $this->query("SELECT * FROM `{$this->table}` LIMIT 1", "SELECT", true);
                    $inputInfo = $this->matchInputDataWithTableData($this->table, $input);

                    $delType = $inputInfo[$delCol]['type'];
                    $this->sql = "DELETE FROM `{$this->table}` WHERE `$delCol` = ?";
                    $values[0] = $this->sql;
                    $values[1] = $delType .":" . $parts[2];
                    $this->prep($values);
                }
            }

            if(!$this->preparedStatement)
            {
                $inputData = $this->matchInputDataWithTableData($this->table , $inputs);


                $values[0] = "";
                foreach ($inputData as $name => $column)
                {
                    if($column['primary'])
                    {
                        $primary =  $name;
                        $values[] = $column['type'] . ":" . $column['value'];
                    }
                }

                $this->sql = "DELETE FROM `" . $this->table  . "` WHERE `" . $primary . "` = ?";

                $values[0] = $this->sql;

                $this->prep($values);
            }
        }

        $this->result = $this->query($this->sql, "DELETE");

        // Delete the file if the flag exists
        if($delete_file != NULL)
        {
            if(file_exists($delete_file) && !is_dir($delete_file))
            {
                unlink($delete_file);
            }
        }

        $this->result = $this->query($this->sql, "DELETE");

        if(isset($goto_after_delete) && !$this->queryError){
            header("Location: " . $goto_after_delete);
        }
    }

    public function setInputArray($args)
    {
        $types = array(
            "int"    => "i",
            "text"   => "s",
            "double" => "d",
            "blob"   => "b"
        );

        $data = array(
            'sql'    => '',
            'types'  => '',
            'inputs' => array()
        );

        foreach($args as $count => $arg)
        {
            if($count == 0)
            {
                $data['sql'] = $arg;
            } else {
                $firstColan = strpos($arg, ":");

                if($firstColan < 1)
                {
                    $report = $this->traceOutError();
                    $this->error("Input type not defined, it should look like this type:input . {$this->error_msg_snippets[1]}", 2, $report['line'], $report['file'], true);
                }

                $type = substr($arg, 0, $firstColan);
                if(!array_key_exists($type, $types))
                {
                    $report = $this->traceOutError();
                    $this->error("Input type '$type' not valid. Must be int, text, double or blob. {$this->error_msg_snippets[1]}", 2, $report['line'], $report['file'], true);
                }

                $inputValue = substr($arg, $firstColan + 1);

                $data['types'] .= $types[$type];
                $data['inputs'][] = $inputValue;
            }
        }

        return $data;
    }

    public function prep($args)
    {
        $this->preparedStatement = true;

        //ERROR Handling.
        if(count($args) < 2) {
            $report = $this->traceOutError();
            $this->error("Less than 2 arguments sent to the prep method. {$this->error_msg_snippets[1]}", 2, $report['line'], $report['file'], true);
        }

        //Handle array passing as second argument
        if(is_array($args[1]))
        {
            $sql[] = $args[0];
            $data = $args[1];
            unset($args);
            $args = array_merge($sql, $data);
        }

        $data = $this->setInputArray($args);

        $this->sql = $data['sql'];
        $this->prepareTypes = $data['types'];
        $this->prepareInputs = $data['inputs'];

        return $this;
    }



}