SELECT 'Adding new services' AS '';

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'relation', 'DELETE', 1, 1 ),
( 'relation', 'GET', 0, 1 ),
( 'relation', 'GET', 1, 1 ),
( 'relation', 'PATCH', 1, 1 ),
( 'relation', 'POST', 0, 1 ),
( 'relation_type', 'DELETE', 1, 1 ),
( 'relation_type', 'GET', 0, 1 ),
( 'relation_type', 'GET', 1, 1 ),
( 'relation_type', 'PATCH', 1, 1 ),
( 'relation_type', 'POST', 0, 1 );
