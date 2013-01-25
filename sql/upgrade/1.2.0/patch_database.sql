-- Patch to upgrade database to version 1.2.0

SET AUTOCOMMIT=0;

SOURCE activity.sql

COMMIT;
