SELECT "Adding new services" AS "";

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'identifier', 'DELETE', 1, 1 ),
( 'identifier', 'GET', 0, 1 ),
( 'identifier', 'GET', 1, 1 ),
( 'identifier', 'PATCH', 1, 1 ),
( 'identifier', 'POST', 0, 1 ),
( 'participant_identifier', 'DELETE', 1, 1 ),
( 'participant_identifier', 'GET', 0, 1 ),
( 'participant_identifier', 'GET', 1, 1 ),
( 'participant_identifier', 'PATCH', 1, 1 ),
( 'participant_identifier', 'POST', 0, 1 );
