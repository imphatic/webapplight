<?php

/*
 * Framework
 * @author - Garrett R. Davis
 * @date - 4-26-14
 *
 * Framework (formally PHP Framework) is a set of classes that will handle communicating between a
 * web application and the database.
 */

require('_settings.php');
require('lib/error.php');
require('lib/engine.php');

class fw
{

    /*
     * select Class
     * USEFUL VARIABLES
     * ----------------------------------------------------------------
     * $totalRows  - Total rows returned
     * $tableStructureAndInfo - an array of data about each column in the table.
     *
     * PAGING VARIABLES
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

      SAMPLE USAGE
      ----------------------------------------------------------------
        $products = fw::select('products', 10);

        if($products)
        {
            echo "Showing " . $products->recordCount . "<br />";
            foreach ($products as $product) {
                echo $product->name . "<br />";
            }

            echo $products->paging();

        } else {
            echo "No Products Found";
        }
     *  METHODS
      ----------------------------------------------------------------
        select(sql, [limit])
        repeat()
        paging()
        toArray()

            NOTE: Limit must be defined if you want to use the paging() method.

     ********
     * repeat($while = NULL)
     * This method advances to row currently loaded and when there are no more rows,
     * returns false.
     *
     * NOTE:  If $while is set to anything, then the function assumes
     * you are doing a while loop instead of a do-while loop.

     *******
     * paging($page = NULL, $displayFirstLast = FALSE, $displayPageNums = FALSE)
     * A method to output the links used for paging.
     * For example:
     * < Previous 1 2 3 Next >

     *******
     * toArray()
     * Takes the result set and converts it into a multidimensional array.
     * It has the following structure:
     * [row primary key value] =>
     *          array(
     *              [column name 1] => column 1 data
     *              [column name 2] => column 2 data
     *          );
     */
    static function select($sql, $limit=NULL)
    {
        $obj = new frameworkEngine();
        $obj->select($sql, $limit);
        return ($obj->totalRows) ? $obj : false;
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
        $insert = fw::insert($sql);

        Just specifying the table Example:
        $insert = fw::insert("products", "success.php");

            NOTES: For this method to work, you will need to name all $_POST values the same why as their corresponding columns in the database.

     */

    static function insert($sql, $goto_after_insert=NULL)
    {
        $obj = new frameworkEngine();
        $obj->insert($sql, $goto_after_insert);
        return $obj;
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
       $update = fw::update($updateSQL);

       Just specifying the table Example:
       $update = fw::update("products", "success.php");

           NOTES: For this method to work, you will need to name all $_POST values the same why as their corresponding columns in the database.
                  Be sure that your primary key is submitted as a $_POST value too.

     */
    static function update($sql, $goto_after_update = NULL)
    {
        $obj = new frameworkEngine();
        $obj->update($sql, $goto_after_update);
        return $obj;
    }

    /*
     * delete Class
     *  METHODS
      --------------------------------------------------------------------------------------------------
        delete(A, [B], [C])
            A is the full SQL or the table name that you are deleting data from.
            B an optional value that will redirect the page to the specified value after inserting data.
            C on optional value that will attempt to delete the file that you are specifying.  Can be either from the root or relative.

      SAMPLE USAGE
      --------------------------------------------------------------------------------------------------
        Entering SQL Example:
        $delete = fw::delete("DELETE FROM products WHERE productID=" . prep($_POST['productID'], "int"));

        Just specifying the table Example:
        $delete = fw::delete("products", "success.php", "../uploads/img/cats.jpg");

            NOTES: For this method to work you will need to $_POST the primary key with its name set the same as your primary key column name.

     */
    static function delete($sql, $goto_after_delete = NULL, $delete_file = NULL)
    {
        $obj = new frameworkEngine();
        $obj->delete($sql, $goto_after_delete, $delete_file);
        return $obj;
    }


    static function prep()
    {
        $obj = new frameworkEngine();
        $obj->prep(func_get_args());
        return $obj;
    }

  static function query($sql)
  {
    $obj = new frameworkEngine();
    return $obj->rawQuery($sql);
  }


}