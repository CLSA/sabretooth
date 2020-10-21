SELECT "Adding new services" AS "";

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'identifier', 'GET', 0, 1 ),
( 'identifier', 'GET', 1, 1 ),
( 'participant_identifier', 'GET', 0, 1 ),
( 'participant_identifier', 'GET', 1, 1 ),
( 'study_phase', 'GET', 0, 1 ),
( 'study_phase', 'GET', 1, 1 );
