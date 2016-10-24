-- Patch to upgrade database to version 2.0.0

SET AUTOCOMMIT=0;

SOURCE access.sql
SOURCE interview.sql
SOURCE participant_last_interview.sql
SOURCE update_participant_last_interview.sql
SOURCE interview_last_assignment.sql
SOURCE update_interview_last_assignment.sql
SOURCE assignment_last_phone_call.sql
SOURCE update_assignment_last_phone_call.sql
SOURCE participant_last_appointment.sql

SOURCE cedar_instance.sql
SOURCE opal_instance.sql
SOURCE queue.sql
SOURCE queue_state.sql
SOURCE qnaire_has_event_type.sql
SOURCE qnaire_has_quota.sql
SOURCE qnaire_has_site.sql
SOURCE event_type.sql
SOURCE qnaire.sql
SOURCE source_survey.sql
SOURCE phase.sql
SOURCE queue_has_participant.sql
SOURCE qnaire_has_interview_method.sql
SOURCE interview_method.sql
SOURCE assignment.sql
SOURCE phone_call.sql
SOURCE activity.sql
SOURCE opal_data.sql
SOURCE writelog.sql
SOURCE prerecruit.sql
SOURCE ivr_appointment.sql
SOURCE source_withdraw.sql
SOURCE interview_phone_call_status_count.sql
SOURCE appointment.sql
SOURCE callback.sql
SOURCE service.sql
SOURCE role_has_operation.sql
SOURCE role_has_service.sql
SOURCE role_has_state.sql
SOURCE operation.sql
SOURCE site.sql
SOURCE setting_value.sql
SOURCE setting.sql
SOURCE system_message.sql
SOURCE away_time.sql
SOURCE user_time.sql
SOURCE user.sql
SOURCE recording.sql
SOURCE recording_file.sql
SOURCE report_type.sql
SOURCE report_restriction.sql
SOURCE application_type_has_report_type.sql
SOURCE role_has_report_type.sql
SOURCE overview.sql
SOURCE application_type_has_overview.sql
SOURCE role_has_overview.sql

SOURCE table_character_sets.sql
SOURCE column_character_sets.sql

SOURCE update_version_number.sql

COMMIT;
