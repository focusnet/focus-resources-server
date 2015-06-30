<?php 
/*
 * Configuration settings for the resources server application.
 * 
 * Apart from the database credentials, most settings are optional.
 *
 * NOTE:
 * - All paths must have a trailing slash but no leading slash.
 * 
 * --
 * 
 * This file is part of the focus-resources-server package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Database configuration
 */
$FOCUS_REST_CONFIGURATION['db_host'] = 'localhost';
$FOCUS_REST_CONFIGURATION['db_user'] = 'dbuser';
$FOCUS_REST_CONFIGURATION['db_pass'] = '*********';
$FOCUS_REST_CONFIGURATION['db_dbname'] = 'focus';

/**
 * Where to store cached schemas. This can be a path relative to the directory
 * where index.php is located or an absolute path.
 */
// $FOCUS_REST_CONFIGURATION['schemas_cache_dir'] = 'cache/';

/**
 * Enable debugging?
 */
// $FOCUS_REST_CONFIGURATION['DEBUG'] = FALSE;

/**
 * When testing, the root schemas (FOCUS Object, etc.) may be on a dev server.
 * You can specify a URI pointing to the root of these schemas here.
 */
// $FOCUS_REST_CONFIGURATION['DEBUG_root_schemas_url'] = FALSE;

/**
 * Set to TRUE if you don't want to cache schema files.
 */
// $FOCUS_REST_CONFIGURATION['DEBUG_bypass_cache'] = FALSE;

