<?php


/*  ----------------------------------------------------------------------------
    APPLICATION ROUTES
    In this document you can specify how you want http requests handled and which file
    the request ultimately reaches.

    Methods to use:
    Route::any - match any request type.
    Route::get - only run if the request was of the type 'get'.
    Route::post - only run if the request was of the type 'post'
    Route::put - etc.
    Route::delete - etc.

    ----------------------------------------------------------------------------
    USAGE EXAMPLES
    ----------------------------------------------------------------------------

    Basic Usage:
    Route::any('/products/view/', '/cart/prods.php');

    [WIN]  myapp.com/products/view/
    [FAIL] myapp.com/products/

    ----------------------------------------------------------------------------

    Passing Parameters:
    Route::any('/products/{product_id}/view', '/cart/prodsview.php');

    [WIN]  myapp.com/products/54/view/
    [WIN]  myapp.com/products/zx233ffjk/view/
    [FAIL] myapp.com/products/54/view/parts

    Note: The value of "{product_id}" will be set as $_GET['product_id'] before loading the file "prodsview.php"

    ----------------------------------------------------------------------------

    Match All:
    Route::any('/category/{category_id}/*', '/cart/categories.php');

    [WIN]  myapp.com/category/12/
    [WIN]  myapp.com/category/12/what-it-takes-to-win
    [WIN]  myapp.com/category/12/literally/will/match/anything/with/star

    Note: This example is bad SEO, only use this in special circumstances.

    ----------------------------------------------------------------------------

    Group Routing:
    Route::group('/products/', function ()
    {
        Route::any('/view/','/cart/viewprods.php');
        Route::any('/categories/search/', '/cart/searchcats.php');
    });

    [WIN]  myapp.com/products/view/
    [WIN]  myapp.com/products/categories/search/
    [FAIL] myapp.com/categories/search/

 */


