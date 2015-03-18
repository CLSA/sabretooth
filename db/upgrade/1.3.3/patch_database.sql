-- Patch to upgrade database to version 1.3.3

SET AUTOCOMMIT=0;

SOURCE operation.sql
SOURCE role_has_operation.sql
SOURCE recording.sql

SOURCE update_version_number.sql

COMMIT;
