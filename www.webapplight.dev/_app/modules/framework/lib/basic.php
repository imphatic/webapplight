<?php

/*
 * Framework
 * @author - Garrett R. Davis
 * @date - 4-26-14
 *
 * Framework (formally PHP Framework) is a set of classes that will handle communicating between a
 * web application and the database.
 *
 * This implementation is for legacy sites that used the old version of framework but want to upgrade
 * to this one.  Tip: To make the implementation smooth, I would recomend moving this file out to the
 * /framework root and rename /framework to phpframework (like the old sites) then you could really make
 * it fool proof by creating an admin.php inside /phpframework and just including basic.php.  All of this
 * is simply to allow you to avoid needing to repoint all the includes or requires in existing sites to
 * follow this new dir structure.  For old sites, every call was made through /phpframework/basic.php or
 * /phpframework/admin.php
 */

require(dirname(__FILE__) . '/../_settings.php');
require('frameworkError.php');
require('frameworkEngine.php');

require('backwardsCompatibility.php');

/*
 * select Class
 *  METHODS
  --------------------------------------------------------------------------------------------------
    select(sql, [limit])
    repeat()
    paging()
    toArray()

        NOTE: Limit must be defined if you want to use the paging() method.

  SAMPLE USAGE
  --------------------------------------------------------------------------------------------------
    $products = new select("products", 10);

    echo "Showing " . $products->recordCount . "<br />";
    do {
        echo $products->row['name'] . "<br />";
    } while($products->repeat());

    echo $products->paging();
 */

class select extends frameworkEngine
{
    /*
     * Useful Variables:
     * ----------------------------------------------------------------
     * $totalRows  - Total rows returned
     * $tableStructureAndInfo - an array of data about each column in the table.
     *
     * Paging Variables:
     * ----------------------------------------------------------------
     * $recordCount - Outputs something like "1 to 2 of 13"
     * $nextPageHREF
     * $prevPageHREF
     * $firstPageHREF
     * $lastPageHREF
     * $pageHREF
     * $curPage
     * $firstPage
     * $lastPage
     */

    public function __construct($string, $limit = NULL)
    {
        parent::__construct();
        parent::select($string, $limit);
    }

    public function __destruct() { parent::destruct(); }
    /*
     * Method - repeat
     * This method advances to row currently loaded and when there are no more rows,
     * returns false.
     *
     * NOTE:  If $while is set to anything, then the function assumes
     * you are doing a while loop instead of a do-while loop.
     */
    function repeat($while = NULL)
    {
        return parent::repeat($while);
    }

    /*
     * Method - paging
     * A method to output the links used for paging.
     * For example:
     * < Previous 1 2 3 Next >
     */
    function paging($page = NULL, $displayFirstLast = FALSE, $displayPageNums = FALSE)
    {
        return parent::paging($page, $displayFirstLast, $displayPageNums);
    }
    /*
     * Method - toArray
     * Takes the result set and converts it into a multidimensional array.
     * It has the following structure:
     * [row primary key value] =>
     *          array(
     *              [column name 1] => column 1 data
     *              [column name 2] => column 2 data
     *          );
     */
    function toArray()
    {
        return parent::toArray();
    }
}


/* insert Class
 * METHODS
  --------------------------------------------------------------------------------------------------
    insert(A, [B])
        A is the full SQL or the table name that you are inserting data.
        B an optional value that will redirect the page to the specified value after inserting data.

  SAMPLE USAGE
  --------------------------------------------------------------------------------------------------
    Entering SQL Example:
    $sql = sprintf("INSERT INTO products (subCatID, typeID, name, `description`, price, promoDiscount, weight) VALUES (%s, %s, %s, %s, %s, %s, %s)",
                       prep($subCatID, "text"),
                       prep($typeID, "text"),
                       prep($_POST['name'], "text"),
                       prep($_POST['description'], "text"),
                       prep($_POST['price'], "text"),
                       prep($_POST['promoDiscount'], "text"),
                       prep($_POST['weight'], "text"));
    $insert = new insert($sql);

    Just specifying the table Example:
    $insert = new insert("products", "products.php");

        NOTES: For this method to work, you will need to name all $_POST values the same why as their corresponding columns in the database.

 */
class insert extends frameworkEngine
{
    public function __construct($sql, $goto_after_insert = NULL)
    {
        parent::__construct();
        parent::insert($sql, $goto_after_insert);
    }
}

/*
 * update Class
 * METHODS
   --------------------------------------------------------------------------------------------------
   update(A, [B])
       A is the full SQL or the table name that you are updateing.
       B an optional value that will redirect the page to the specified value after updating data.

   SAMPLE USAGE
   --------------------------------------------------------------------------------------------------
   Entering SQL Example:
    $updateSQL = sprintf("UPDATE products SET subCatID=%s, typeID=%s, name=%s, description=%s, price=%s, promoDiscount=%s, weight=%s WHERE productID=%s",
                  prep($subCatID, "text"),
                  prep($typeID, "text"),
                  prep($_POST['name'], "text"),
                  prep($_POST['description'], "text"),
                  prep($_POST['price'], "text"),
                  prep($_POST['promoDiscount'], "text"),
                  prep($_POST['weight'], "text"),
                  prep($_POST['productID'], "int"));
   $update = new update($updateSQL);

   Just specifying the table Example:
   $update = new update("products", "products.php");

       NOTES: For this method to work, you will need to name all $_POST values the same why as their corresponding columns in the database.
              Be sure that your primary key is submitted as a $_POST value too.

 */
class update extends frameworkEngine
{
    public function __construct($sql, $goto_after_update = NULL)
    {
        parent::__construct();
        parent::update($sql, $goto_after_update);
    }
}

/*
 * delete Class
 *  METHODS
  --------------------------------------------------------------------------------------------------
    delete(A, [B], [C])
        A is the full SQL or the table name that you are deleting data from.
        B an optional value that will redirect the page to the specified value after inserting data.
        C on optional value that will attempt to delete the file that you are specifyig.  Can be either from the root or relative.

  SAMPLE USAGE
  --------------------------------------------------------------------------------------------------
    Entering SQL Example:
    $delete = new delete("DELETE FROM products WHERE productID=" . prep($_POST['productID'], "int"));

    Just specifying the table Example:
    $delete = new delete("products", "products.php", "../uploads/img/cats.jpg");

        NOTES: For this method to work you will need to $_POST the primary key with its name set the same as your primary key column name.

 */

class delete extends frameworkEngine
{
    public function __construct($sql, $goto_after_delete = NULL, $delete_file = NULL)
    {
        parent::__construct();
        parent::delete($sql, $goto_after_delete, $delete_file);
    }
}

