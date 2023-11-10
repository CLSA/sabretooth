SELECT 'Adding new services' AS '';

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'custom_report', 'DELETE', 1, 1 ),
( 'custom_report', 'GET', 0, 0 ),
( 'custom_report', 'GET', 1, 0 ),
( 'custom_report', 'PATCH', 1, 1 ),
( 'custom_report', 'POST', 0, 1 ),
( 'log_entry', 'GET', 0, 1 ),
( 'log_entry', 'GET', 1, 1 ),
( 'relation', 'DELETE', 1, 1 ),
( 'relation', 'GET', 0, 0 ),
( 'relation', 'GET', 1, 1 ),
( 'relation', 'PATCH', 1, 1 ),
( 'relation', 'POST', 0, 1 ),
( 'relation_type', 'DELETE', 1, 1 ),
( 'relation_type', 'GET', 0, 1 ),
( 'relation_type', 'GET', 1, 1 ),
( 'relation_type', 'PATCH', 1, 1 ),
( 'relation_type', 'POST', 0, 1 );
