-- Patch to upgrade database to version 2.0.0

SET AUTOCOMMIT=0;

SOURCE interview.sql
SOURCE assignment.sql
SOURCE phone_call.sql
SOURCE activity.sql
SOURCE writelog.sql
SOURCE appointment.sql
SOURCE ivr_appointment.sql
SOURCE participant_last_appointment.sql
SOURCE participant_last_interview.sql
SOURCE interview_last_assignment.sql
SOURCE assignment_last_phone_call.sql
SOURCE service.sql
SOURCE role_has_operation.sql
SOURCE role_has_service.sql
SOURCE operation.sql
SOURCE qnaire.sql
SOURCE setting_value.sql
SOURCE setting.sql
SOURCE system_message.sql

SOURCE update_participant_last_interview.sql
SOURCE update_interview_last_assignment.sql
SOURCE update_assignment_last_phone_call.sql

SOURCE table_character_sets.sql
SOURCE column_character_sets.sql

SOURCE update_version_number.sql

COMMIT;
