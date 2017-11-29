SELECT "Replacing old state services with hold_type services" AS "";

UPDATE service SET subject = "hold_type" WHERE subject = "state";

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'hold', 'GET', 0, 0 ),
( 'hold', 'GET', 1, 0 ),
( 'hold', 'POST', 0, 1 );
