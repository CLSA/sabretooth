-- Patch to upgrade database to version 1.2.4

SET AUTOCOMMIT=0;

SOURCE state.sql
SOURCE role_has_state.sql
SOURCE participant.sql

COMMIT;
