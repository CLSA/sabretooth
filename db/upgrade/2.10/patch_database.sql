-- Patch to upgrade database to version 2.10

SET AUTOCOMMIT=0;

SOURCE update_version_number.sql

COMMIT;
