-- Patch to upgrade database to version 1.3.4

SET AUTOCOMMIT=0;

SOURCE update_version_number.sql

COMMIT;
