-- Patch to upgrade database to version 2.0.0

SET AUTOCOMMIT=0;

SOURCE activity.sql
SOURCE writelog.sql
SOURCE appointment.sql
SOURCE ivr_appointment.sql
SOURCE participant_last_appointment.sql
SOURCE interview_last_appointment.sql
SOURCE service.sql
SOURCE role_has_operation.sql
SOURCE role_has_service.sql
SOURCE operation.sql
SOURCE setting_value.sql
SOURCE setting.sql

SOURCE update_version_number.sql

COMMIT;
