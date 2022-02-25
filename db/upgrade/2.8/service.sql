SELECT 'Adding new services' AS '';

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'alternate_type', 'GET', 0, 0 ),
( 'alternate_type', 'GET', 1, 0 ),
( 'alternate_type', 'PATCH', 1, 1 ),
( 'alternate_type', 'POST', 0, 1 ),
( 'country', 'GET', 0, 0 ),
( 'country', 'GET', 1, 0 ),
( 'pine_response', 'POST', 0, 1 );

UPDATE service SET restricted = 1 WHERE subject = 'pine_response' AND method = 'GET';

DELETE FROM service WHERE subject = 'alternate_type' AND method = 'DELETE';
