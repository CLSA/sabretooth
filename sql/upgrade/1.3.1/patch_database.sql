-- Patch to upgrade database to version 1.3.1

SET AUTOCOMMIT=0;

SOURCE activity.sql
SOURCE role_has_operation.sql
SOURCE operation2.sql
SOURCE interview_method.sql
SOURCE qnaire.sql
SOURCE interview.sql

COMMIT;
