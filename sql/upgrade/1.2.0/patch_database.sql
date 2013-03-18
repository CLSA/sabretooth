-- Patch to upgrade database to version 1.2.0

SET AUTOCOMMIT=0;

SOURCE queue.sql
SOURCE operation.sql
SOURCE role_has_operation.sql
SOURCE activity.sql
SOURCE role_has_operation2.sql
SOURCE operation2.sql

-- these must come after the preceeding calls
SOURCE convert_database.sql

COMMIT;
