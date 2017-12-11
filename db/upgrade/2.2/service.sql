SELECT "Replacing old state services with hold_type services" AS "";

DELETE FROM service WHERE subject = "state";

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'hold', 'GET', 0, 0 ),
( 'hold', 'GET', 1, 0 ),
( 'hold', 'POST', 0, 1 ),
( 'hold_type', 'DELETE', 1, 1 ),
( 'hold_type', 'GET', 0, 0 ),
( 'hold_type', 'GET', 1, 0 ),
( 'hold_type', 'PATCH', 1, 1 ),
( 'hold_type', 'POST', 0, 1 ),
( 'proxy', 'GET', 0, 0 ),
( 'proxy', 'GET', 1, 0 ),
( 'proxy', 'POST', 0, 1 ),
( 'proxy_type', 'GET', 0, 0 ),
( 'proxy_type', 'GET', 1, 0 ),
( 'trace', 'GET', 0, 0 ),
( 'trace', 'GET', 1, 0 ),
( 'trace_type', 'GET', 0, 0 ),
( 'trace_type', 'GET', 1, 0 );
