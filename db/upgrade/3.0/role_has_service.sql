SELECT "Removing unneeded records from role_has_ervice table" AS "";

DELETE FROM role_has_service
WHERE service_id IN ( SELECT id FROM service WHERE restricted = false );
