-- Patch to upgrade database to version 1.3.1

SET AUTOCOMMIT=0;

SOURCE role.sql
SOURCE service_has_role.sql
SOURCE operation.sql
SOURCE activity.sql
SOURCE role_has_operation.sql
SOURCE operation2.sql
SOURCE interview_method.sql
SOURCE qnaire.sql
SOURCE interview.sql
SOURCE cedar_instance.sql
SOURCE source.sql

COMMIT;
