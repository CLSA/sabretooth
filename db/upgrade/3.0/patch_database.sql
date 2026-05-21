-- Patch to upgrade database to version 3.0

SET AUTOCOMMIT=0;

SOURCE service.sql
SOURCE role_has_service.sql

SOURCE update_version_number.sql

COMMIT;
