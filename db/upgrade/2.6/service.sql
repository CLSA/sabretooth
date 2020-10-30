SELECT "Adding new services" AS "";

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'identifier', 'GET', 0, 1 ),
( 'identifier', 'GET', 1, 1 ),
( 'participant_identifier', 'GET', 0, 1 ),
( 'participant_identifier', 'GET', 1, 1 ),
( 'stratum', 'GET', 0, 1 ),
( 'stratum', 'GET', 1, 1 ),
( 'study', 'GET', 0, 1 ),
( 'study', 'GET', 1, 1 ),
( 'study_phase', 'GET', 0, 1 ),
( 'study_phase', 'GET', 1, 1 );
