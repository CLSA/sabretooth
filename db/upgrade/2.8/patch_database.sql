-- Patch to upgrade database to version 2.8

SET AUTOCOMMIT=0;

SOURCE table_character_sets.sql

SOURCE service.sql
SOURCE role_has_service.sql
SOURCE qnaire_has_alternate_type.sql

SOURCE update_version_number.sql

COMMIT;
