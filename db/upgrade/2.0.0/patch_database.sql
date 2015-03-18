-- Patch to upgrade database to version 2.0.0

SET AUTOCOMMIT=0;


SOURCE appointment.sql
SOURCE ivr_appointment.sql
SOURCE operation.sql
SOURCE role_has_operation.sql
SOURCE participant_last_appointment.sql
SOURCE interview_last_appointment.sql
SOURCE service.sql
SOURCE role_has_service.sql

SOURCE update_version_number.sql

COMMIT;
