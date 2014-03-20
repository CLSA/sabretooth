-- Patch to upgrade database to version 1.3.2

SET AUTOCOMMIT=0;

SOURCE operation.sql
SOURCE role_has_operation.sql
SOURCE jurisdiction.sql
SOURCE qnaire.sql
SOURCE qnaire_has_interview_method.sql
SOURCE queue.sql
SOURCE ivr_appointment.sql

COMMIT;
