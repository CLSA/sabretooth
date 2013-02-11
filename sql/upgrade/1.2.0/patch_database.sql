-- Patch to upgrade database to version 1.2.0

SET AUTOCOMMIT=0;

SOURCE activity.sql
SOURCE queue.sql

-- this must be last
SOURCE convert_database.sql

COMMIT;
