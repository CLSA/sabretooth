SELECT "Replacing old state services with hold_type services" AS "";

DELETE FROM service WHERE subject = "state";

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'pine_qnaire', 'GET', 0, 0 ),
( 'pine_response', 'GET', 1, 0 );
