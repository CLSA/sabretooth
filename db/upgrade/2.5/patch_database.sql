-- Patch to upgrade database to version 2.5

SET AUTOCOMMIT=0;

SOURCE service.sql
SOURCE role_has_service.sql
SOURCE appointment_mail.sql

SOURCE update_version_number.sql

COMMIT;
