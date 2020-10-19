-- Patch to upgrade database to version 2.5

SET AUTOCOMMIT=0;

SOURCE interview.sql
SOURCE qnaire.sql
SOURCE queue.sql
SOURCE service.sql
SOURCE role_has_service.sql

SOURCE update_version_number.sql

COMMIT;
