DROP PROCEDURE IF EXISTS patch_appointment;
  DELIMITER //
  CREATE PROCEDURE patch_appointment()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Replacing participant_id with interview_id column in appointment table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "appointment"
      AND COLUMN_NAME = "interview_id" );
    IF @test = 0 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      ALTER TABLE appointment 
      ADD COLUMN interview_id INT UNSIGNED NOT NULL
      AFTER participant_id;

      ALTER TABLE appointment 
      ADD INDEX fk_interview_id( interview_id ASC ), 
      ADD CONSTRAINT fk_appointment_interview_id 
      FOREIGN KEY( interview_id ) REFERENCES interview( id ) 
      ON DELETE NO ACTION ON UPDATE NO ACTION;

      -- fill in the new interview_id column using the existing participant_id column
      UPDATE appointment 
      JOIN interview ON appointment.participant_id = interview.participant_id 
      SET interview_id = interview.id;

      -- delete any remaining appointments which didn't have an interview
      DELETE FROM appointment WHERE interview_id = 0;

      -- delete any unassigned appointments which are for a completed interview which is for the last qnaire
      SET @last_qnaire_id = ( SELECT id FROM qnaire WHERE rank = ( SELECT MAX( rank ) FROM qnaire ) );
      DELETE FROM appointment
      WHERE assignment_id IS NULL
      AND interview_id IN ( SELECT id FROM interview WHERE qnaire_id = @last_qnaire_id );

      -- determine which appointments are for completed interviews whose qnaire is not the last
      -- and whose next interview doesn't yet exist
      INSERT INTO interview( qnaire_id, participant_id, start_datetime )
      SELECT next_qnaire.id, interview.participant_id, appointment.datetime
      FROM appointment
      JOIN interview ON appointment.interview_id = interview.id
      JOIN qnaire ON interview.qnaire_id = qnaire.id
      JOIN qnaire AS next_qnaire ON qnaire.rank + 1 = next_qnaire.rank
      LEFT JOIN interview AS next_interview ON next_qnaire.id = next_interview.qnaire_id
      AND interview.participant_id = next_interview.participant_id
      WHERE appointment.assignment_id IS NULL
      AND interview.end_datetime IS NOT NULL
      AND next_interview.id IS NULL;

      -- advance appointments which belong to completed interviews to the next interview
      UPDATE appointment
      JOIN interview ON appointment.interview_id = interview.id
      JOIN qnaire ON interview.qnaire_id = qnaire.id
      JOIN qnaire AS next_qnaire ON qnaire.rank + 1 = next_qnaire.rank
      JOIN interview AS next_interview ON next_qnaire.id = next_interview.qnaire_id
      AND interview.participant_id = next_interview.participant_id
      SET appointment.interview_id = next_interview.id
      WHERE appointment.assignment_id IS NULL
      AND interview.end_datetime IS NOT NULL;

      -- now get rid of the participant column, index and constraint
      ALTER TABLE appointment
      DROP FOREIGN KEY fk_appointment_participant_id;

      ALTER TABLE appointment
      DROP INDEX fk_participant_id;

      ALTER TABLE appointment
      DROP COLUMN participant_id;

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
    END IF;

    SELECT "Adding override column to appointment table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "appointment"
      AND COLUMN_NAME = "override" );
    IF @test = 0 THEN
      ALTER TABLE appointment
      ADD COLUMN override TINYINT(1) NOT NULL DEFAULT 0
      AFTER datetime;
    END IF;

    SELECT "Adding user_id column to appointment table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "appointment"
      AND COLUMN_NAME = "user_id" );
    IF @test = 0 THEN
      ALTER TABLE appointment
      ADD COLUMN user_id INT UNSIGNED NULL DEFAULT NULL
      AFTER interview_id;

      SET @sql = CONCAT(
        "ALTER TABLE appointment ",
        "ADD INDEX fk_user_id (user_id ASC), ",
        "ADD CONSTRAINT fk_appointment_user_id ",
        "FOREIGN KEY (user_id) ",
        "REFERENCES ", @cenozo, ".user (id) ",
        "ON DELETE NO ACTION ",
        "ON UPDATE NO ACTION" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

    SELECT "Changing appointment types to long/short" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "appointment"
      AND COLUMN_TYPE = "enum('full','half')" );
    IF @test = 1 THEN
      ALTER TABLE appointment
      MODIFY COLUMN type CHAR(5) NOT NULL DEFAULT 'long';
      UPDATE appointment SET type = IF( type = "full", "long", "short" );
      ALTER TABLE appointment
      MODIFY COLUMN type enum('long','short') NOT NULL DEFAULT 'long';
    END IF;

    SELECT "Replacing reached column with outcome in appointment table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "appointment"
      AND COLUMN_NAME = "reached" );
    IF @test = 1 THEN
      ALTER TABLE appointment
      ADD COLUMN outcome ENUM('reached', 'not reached', 'cancelled') NULL DEFAULT NULL,
      ADD INDEX dk_outcome (outcome ASC);

      UPDATE appointment SET outcome = IF( reached, 'reached', 'not reached' )
      WHERE reached IS NOT NULL;

      ALTER TABLE appointment
      DROP INDEX dk_reached,
      DROP COLUMN reached;
    END IF;

    SELECT "Modifiying constraint delete rules in appointment table" AS "";

    SET @test = (
      SELECT DELETE_RULE
      FROM information_schema.REFERENTIAL_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = "appointment"
      AND REFERENCED_TABLE_NAME = "interview" );
    IF @test = "NO ACTION" THEN
      ALTER TABLE appointment
      DROP FOREIGN KEY fk_appointment_interview_id;

      ALTER TABLE appointment
      ADD CONSTRAINT fk_appointment_interview_id
      FOREIGN KEY (interview_id)
      REFERENCES interview (id)
      ON DELETE CASCADE
      ON UPDATE NO ACTION;
    END IF;

    SET @test = (
      SELECT DELETE_RULE
      FROM information_schema.REFERENTIAL_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = "appointment"
      AND REFERENCED_TABLE_NAME = "assignment" );
    IF @test = "NO ACTION" THEN
      ALTER TABLE appointment
      DROP FOREIGN KEY fk_appointment_assignment_id;

      ALTER TABLE appointment
      ADD CONSTRAINT fk_appointment_assignment_id
      FOREIGN KEY (assignment_id)
      REFERENCES assignment (id)
      ON DELETE SET NULL
      ON UPDATE NO ACTION;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_appointment();
DROP PROCEDURE IF EXISTS patch_appointment;

SELECT "Delete appointments for participants who have completed all interviews" AS "";

DELETE FROM appointment
WHERE id IN (
  SELECT id FROM (
    SELECT appointment.id
    FROM appointment
    JOIN interview ON appointment.interview_id = interview.id
    JOIN participant_last_interview on interview.participant_id = participant_last_interview.participant_id
    JOIN interview last_interview on participant_last_interview.interview_id = last_interview.id
    JOIN qnaire ON last_interview.qnaire_id = qnaire.id
    WHERE qnaire.rank = ( SELECT MAX( rank ) FROM qnaire )
    AND last_interview.end_datetime IS NOT NULL
    AND appointment.assignment_id IS NULL
  ) AS temp
);

SELECT "Create new interviews for participants with orphaned appointments which have no open interviews" AS "";

INSERT INTO interview( qnaire_id, participant_id, start_datetime )
SELECT qnaire_id+1, interview.participant_id, end_datetime
FROM interview
WHERE qnaire_id = (
  SELECT MAX( qnaire_id )
  FROM interview AS interview2
  WHERE interview.participant_id = interview2.participant_id
  GROUP BY interview2.participant_id
  LIMIT 1
)
AND interview.participant_id IN (
  SELECT DISTINCT participant_id
  FROM interview
  WHERE end_datetime IS NOT NULL
  AND participant_id IN (
    SELECT participant_id FROM appointment
    JOIN interview ON appointment.interview_id = interview.id
    WHERE appointment.assignment_id IS NULL
    AND interview.end_datetime IS NOT NULL
  )
  AND participant_id NOT IN (
    SELECT DISTINCT participant_id
    FROM interview
    WHERE end_datetime IS NULL
    AND participant_id IN (
      SELECT participant_id FROM appointment
      JOIN interview ON appointment.interview_id = interview.id
      WHERE appointment.assignment_id IS NULL
      AND interview.end_datetime IS NOT NULL
    )
  )
);

SELECT "Re-associate orphaned appointments with unfinished interviews" AS "";

UPDATE appointment
JOIN interview ON appointment.interview_id = interview.id
JOIN participant_last_interview ON interview.participant_id = participant_last_interview.participant_id
SET appointment.interview_id = participant_last_interview.interview_id
WHERE appointment.assignment_id IS NULL
AND interview.end_datetime IS NOT NULL
AND participant_last_interview.interview_id != interview.id;
