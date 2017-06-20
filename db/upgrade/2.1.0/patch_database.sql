-- Patch to upgrade database to version 2.0.0

SET AUTOCOMMIT=0;

SOURCE report_type.sql
SOURCE report_restriction.sql
SOURCE application_type_has_report_type.sql
SOURCE role_has_report_type.sql

SOURCE service.sql
SOURCE role_has_service.sql

SOURCE column_character_sets.sql

SOURCE update_version_number.sql

COMMIT;
