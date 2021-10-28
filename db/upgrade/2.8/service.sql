SELECT "Adding new services" AS "";

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'country', 'GET', 0, 0 ),
( 'country', 'GET', 1, 0 );
