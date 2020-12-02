-- Patch to upgrade database to version 2.5

SET AUTOCOMMIT=0;

SOURCE interview.sql
SOURCE qnaire.sql
SOURCE queue.sql
SOURCE service.sql
SOURCE role_has_service.sql
SOURCE qnaire_has_quota.sql
SOURCE qnaire_has_stratum.sql
SOURCE setting.sql
SOURCE jurisdiction.sql
SOURCE region_site.sql

SOURCE update_version_number.sql

COMMIT;
