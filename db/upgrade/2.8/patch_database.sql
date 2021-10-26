-- Patch to upgrade database to version 2.8

SET AUTOCOMMIT=0;

SOURCE update_version_number.sql

COMMIT;
