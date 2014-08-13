-- Patch to upgrade database to version 1.3.4

SET AUTOCOMMIT=0;

SOURCE operation.sql
SOURCE role_has_operation.sql

SOURCE update_version_number.sql

COMMIT;
