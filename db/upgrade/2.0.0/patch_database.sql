-- Patch to upgrade database to version 2.0.0

SET AUTOCOMMIT=0;

SOURCE queue.sql
SOURCE queue_state.sql
SOURCE interview.sql
SOURCE qnaire.sql
SOURCE queue_has_participant.sql
SOURCE qnaire_has_interview_method.sql
SOURCE interview_method.sql
SOURCE assignment.sql
SOURCE phone_call.sql
SOURCE activity.sql
SOURCE writelog.sql
SOURCE callback.sql
SOURCE appointment.sql
SOURCE ivr_appointment.sql
SOURCE source_survey.sql
SOURCE source_withdraw.sql
SOURCE participant_last_appointment.sql
SOURCE participant_last_interview.sql
SOURCE interview_phone_call_status_count.sql
SOURCE interview_last_assignment.sql
SOURCE assignment_last_phone_call.sql
SOURCE service.sql
SOURCE role_has_operation.sql
SOURCE role_has_service.sql
SOURCE operation.sql
SOURCE setting_value.sql
SOURCE setting.sql
SOURCE system_message.sql
SOURCE away_time.sql
SOURCE user_time.sql

SOURCE update_participant_last_interview.sql
SOURCE update_interview_last_assignment.sql
SOURCE update_assignment_last_phone_call.sql

SOURCE table_character_sets.sql
SOURCE column_character_sets.sql

SOURCE update_version_number.sql

COMMIT;
