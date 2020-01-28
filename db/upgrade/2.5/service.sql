SELECT "Replacing old state services with hold_type services" AS "";

DELETE FROM service WHERE subject = "state";

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'appointment_mail', 'DELETE', 1, 1 ),
( 'appointment_mail', 'GET', 0, 1 ),
( 'appointment_mail', 'GET', 1, 1 ),
( 'appointment_mail', 'PATCH', 1, 1 ),
( 'appointment_mail', 'POST', 0, 1 ),
( 'mail', 'DELETE', 1, 1 ),
( 'mail', 'GET', 0, 0 ),
( 'mail', 'GET', 1, 0 ),
( 'mail', 'PATCH', 1, 1 ),
( 'mail', 'POST', 0, 1 ),
( 'pine_qnaire', 'GET', 0, 0 ),
( 'pine_response', 'GET', 1, 0 );
