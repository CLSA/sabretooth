-- Patch to upgrade database to version 1.3.2

SET AUTOCOMMIT=0;

SOURCE operation.sql
SOURCE role_has_operation.sql
SOURCE jurisdiction.sql

COMMIT;
