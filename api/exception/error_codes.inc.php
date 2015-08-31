<?php
/**
 * error_codes.inc.php
 * 
 * This file is where all error codes are defined.
 * All error code are named after the class and function they occur in.
 */

/**
 * Error number category defines.
 */
define( 'ARGUMENT_SABRETOOTH_BASE_ERRNO',   150000 );
define( 'DATABASE_SABRETOOTH_BASE_ERRNO',   250000 );
define( 'LDAP_SABRETOOTH_BASE_ERRNO',       350000 );
define( 'NOTICE_SABRETOOTH_BASE_ERRNO',     450000 );
define( 'PERMISSION_SABRETOOTH_BASE_ERRNO', 550000 );
define( 'RUNTIME_SABRETOOTH_BASE_ERRNO',    650000 );
define( 'SYSTEM_SABRETOOTH_BASE_ERRNO',     750000 );
define( 'TEMPLATE_SABRETOOTH_BASE_ERRNO',   850000 );
define( 'VOIP_SABRETOOTH_BASE_ERRNO',       950000 );

/**
 * "argument" error codes
 */
define( 'ARGUMENT__SABRETOOTH_BUSINESS_DATA_MANAGER__GET_PARTICIPANT_VALUE__ERRNO',
        ARGUMENT_SABRETOOTH_BASE_ERRNO + 1 );
define( 'ARGUMENT__SABRETOOTH_DATABASE_QUEUE__GET_QUERY_PARTS__ERRNO',
        ARGUMENT_SABRETOOTH_BASE_ERRNO + 2 );

/**
 * "database" error codes
 * 
 * Since database errors already have codes this list is likely to stay empty.
 */

/**
 * "ldap" error codes
 * 
 * Since ldap errors already have codes this list is likely to stay empty.
 */

/**
 * "notice" error codes
 */
define( 'NOTICE__SABRETOOTH_DATABASE_APPOINTMENT__SAVE__ERRNO',
        NOTICE_SABRETOOTH_BASE_ERRNO + 1 );
define( 'NOTICE__SABRETOOTH_DATABASE_CALLBACK__SAVE__ERRNO',
        NOTICE_SABRETOOTH_BASE_ERRNO + 2 );
define( 'NOTICE__SABRETOOTH_DATABASE_QNAIRE__SAVE__ERRNO',
        NOTICE_SABRETOOTH_BASE_ERRNO + 3 );
define( 'NOTICE__SABRETOOTH_DATABASE_SHIFT__SAVE__ERRNO',
        NOTICE_SABRETOOTH_BASE_ERRNO + 4 );

/**
 * "permission" error codes
 */

/**
 * "runtime" error codes
 */
define( 'RUNTIME__SABRETOOTH_BUSINESS_SESSION__GET_CURRENT_ASSIGNMENT__ERRNO',
        RUNTIME_SABRETOOTH_BASE_ERRNO + 1 );
define( 'RUNTIME__SABRETOOTH_BUSINESS_SESSION__GET_CURRENT_PHONE_CALL__ERRNO',
        RUNTIME_SABRETOOTH_BASE_ERRNO + 2 );
define( 'RUNTIME__SABRETOOTH_DATABASE_APPOINTMENT__VALIDATE_DATE__ERRNO',
        RUNTIME_SABRETOOTH_BASE_ERRNO + 3 );
define( 'RUNTIME__SABRETOOTH_DATABASE_ASSIGNMENT__SAVE__ERRNO',
        RUNTIME_SABRETOOTH_BASE_ERRNO + 4 );
define( 'RUNTIME__SABRETOOTH_DATABASE_PHONE_CALL__SAVE__ERRNO',
        RUNTIME_SABRETOOTH_BASE_ERRNO + 5 );
define( 'RUNTIME__SABRETOOTH_DATABASE_QUEUE__GENERATE_QUERY_LIST__ERRNO',
        RUNTIME_SABRETOOTH_BASE_ERRNO + 6 );
define( 'RUNTIME__SABRETOOTH_DATABASE_QUEUE__GET_QUERY_PARTS__ERRNO',
        RUNTIME_SABRETOOTH_BASE_ERRNO + 7 );
define( 'RUNTIME__SABRETOOTH_DATABASE_SHIFT__SAVE__ERRNO',
        RUNTIME_SABRETOOTH_BASE_ERRNO + 8 );

/**
 * "system" error codes
 * 
 * Since system errors already have codes this list is likely to stay empty.
 * Note the following PHP error codes:
 *      1: error,
 *      2: warning,
 *      4: parse,
 *      8: notice,
 *     16: core error,
 *     32: core warning,
 *     64: compile error,
 *    128: compile warning,
 *    256: user error,
 *    512: user warning,
 *   1024: user notice
 */

/**
 * "template" error codes
 * 
 * Since template errors already have codes this list is likely to stay empty.
 */

/**
 * "voip" error codes
 */

