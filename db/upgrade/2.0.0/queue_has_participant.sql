DROP PROCEDURE IF EXISTS patch_queue_has_participant;
  DELIMITER //
  CREATE PROCEDURE patch_queue_has_participant()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_queue_state_site_id" );

    SELECT "Dropping interview_method_id column from queue_has_participant table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "queue_has_participant"
      AND COLUMN_NAME = "interview_method_id" );
    IF @test = 1 THEN
      ALTER TABLE queue_has_participant
      DROP FOREIGN KEY fk_queue_has_participant_interview_method_id,
      DROP INDEX fk_interview_method_id;

      ALTER TABLE queue_has_participant DROP COLUMN interview_method_id;

      SELECT "Truncating the queue_has_participant table" AS "";
      TRUNCATE queue_has_participant;
    END IF;

    SELECT "Modifiying constraint delete rules in queue_has_participant table" AS "";

    SET @test = (
      SELECT DELETE_RULE
      FROM information_schema.REFERENTIAL_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = "queue_has_participant"
      AND REFERENCED_TABLE_NAME = "participant" );
    IF @test = "NO ACTION" THEN
      ALTER TABLE queue_has_participant
      DROP FOREIGN KEY fk_queue_has_participant_participant_id;

      SET @sql = CONCAT(
        "ALTER TABLE queue_has_participant ",
        "ADD CONSTRAINT fk_queue_has_participant_participant_id ",
        "FOREIGN KEY (participant_id) ",
        "REFERENCES ", @cenozo, ".participant (id) ",
        "ON DELETE CASCADE ",
        "ON UPDATE NO ACTION" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

    SET @test = (
      SELECT DELETE_RULE
      FROM information_schema.REFERENTIAL_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = "queue_has_participant"
      AND REFERENCED_TABLE_NAME = "queue" );
    IF @test = "NO ACTION" THEN
      ALTER TABLE queue_has_participant
      DROP FOREIGN KEY fk_queue_has_participant_queue_id;

      ALTER TABLE queue_has_participant
      ADD CONSTRAINT fk_queue_has_participant_queue_id
      FOREIGN KEY (queue_id)
      REFERENCES queue (id)
      ON DELETE CASCADE
      ON UPDATE NO ACTION;
    END IF;

    SET @test = (
      SELECT DELETE_RULE
      FROM information_schema.REFERENTIAL_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = "queue_has_participant"
      AND REFERENCED_TABLE_NAME = "qnaire" );
    IF @test = "NO ACTION" THEN
      ALTER TABLE queue_has_participant
      DROP FOREIGN KEY fk_queue_has_participant_qnaire_id;

      ALTER TABLE queue_has_participant
      ADD CONSTRAINT fk_queue_has_participant_qnaire_id
      FOREIGN KEY (qnaire_id)
      REFERENCES qnaire (id)
      ON DELETE CASCADE
      ON UPDATE NO ACTION;
    END IF;

    SET @test = (
      SELECT DELETE_RULE
      FROM information_schema.REFERENTIAL_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = "queue_has_participant"
      AND REFERENCED_TABLE_NAME = "site" );
    IF @test = "NO ACTION" THEN
      ALTER TABLE queue_has_participant
      DROP FOREIGN KEY fk_queue_has_participant_site_id;

      SET @sql = CONCAT(
        "ALTER TABLE queue_has_participant ",
        "ADD CONSTRAINT fk_queue_has_participant_site_id ",
        "FOREIGN KEY (site_id) ",
        "REFERENCES ", @cenozo, ".site (id) ",
        "ON DELETE CASCADE ",
        "ON UPDATE NO ACTION" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue_has_participant();
DROP PROCEDURE IF EXISTS patch_queue_has_participant;
