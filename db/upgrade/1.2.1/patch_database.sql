-- Patch to upgrade database to version 1.2.1

SET AUTOCOMMIT=0;

SOURCE operation.sql
SOURCE service_has_role.sql
SOURCE role.sql
SOURCE role_has_operation.sql
SOURCE assignment_note.sql
SOURCE participant.sql
SOURCE queue.sql
SOURCE participant_last_interview.sql

SOURCE update_version_number.sql

COMMIT;
