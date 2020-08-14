-- Patch to upgrade database to version 2.5

SET AUTOCOMMIT=0;

SOURCE interview.sql

SOURCE update_version_number.sql
SOURCE queue.sql
SOURCE role_has_service.sql

COMMIT;
