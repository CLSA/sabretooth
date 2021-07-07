SELECT "Adding new services" AS "";

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'debug', 'POST', 0, 0 ),
( 'proxy_type', 'PATCH', 1, 1 );
