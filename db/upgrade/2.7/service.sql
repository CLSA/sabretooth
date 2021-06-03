SELECT "Adding new services" AS "";

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'alternate_consent', 'DELETE', 1, 1 ),
( 'alternate_consent', 'GET', 0, 0 ),
( 'alternate_consent', 'GET', 1, 0 ),
( 'alternate_consent', 'PATCH', 1, 1 ),
( 'alternate_consent', 'POST', 0, 0 ),
( 'alternate_consent_type', 'DELETE', 1, 1 ),
( 'alternate_consent_type', 'GET', 0, 0 ),
( 'alternate_consent_type', 'GET', 1, 0 ),
( 'alternate_consent_type', 'PATCH', 1, 1 ),
( 'alternate_consent_type', 'POST', 0, 1 ),
( 'proxy_type', 'PATCH', 1, 1 );

-- restrictions on adding consent records is managed by restricting consent-types by role
UPDATE service SET restricted = 0 WHERE subject = 'consent' AND method = 'POST';
