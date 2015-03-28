<?php
/* -----------------------------------------------------------------------------------
 * DATABASE CREDENTIALS
 */

define("FRAME_DB_NAME",     App::get('database.db_name'));
define("FRAME_DB_HOSTNAME", App::get('database.hostname'));
define("FRAME_USER",        App::get('database.user'));
define("FRAME_PASSWORD",    App::get('database.password'));



/* -----------------------------------------------------------------------------------
 * OTHER SETTINGS
 */

define("FRAME_SQL_ERROR_TABLE", App::get('database.error_log_table'));

//Having mysqlnd installed results in a near 50% improvement in performance.
define("MYSQLND_INSTALLED", App::get('database.mysqlnd_installed'));