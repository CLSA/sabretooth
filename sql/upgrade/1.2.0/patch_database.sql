-- Patch to upgrade database to version 1.2.0

SET AUTOCOMMIT=0;

SOURCE queue.sql
SOURCE activity.sql
SOURCE role_has_operation.sql
SOURCE operation.sql

-- this must be last
SOURCE convert_database.sql

COMMIT;
