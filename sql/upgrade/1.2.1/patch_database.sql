-- Patch to upgrade database to version 1.2.1

SET AUTOCOMMIT=0;

SOURCE service_has_role.sql
SOURCE role_has_operation.sql

COMMIT;
