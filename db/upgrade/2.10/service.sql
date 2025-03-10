SELECT 'Adding new services' AS '';

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'user_ip_address', 'GET', 0, 0 ),
( 'user_ip_address', 'GET', 1, 0 );
