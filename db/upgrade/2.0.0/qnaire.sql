DROP PROCEDURE IF EXISTS patch_qnaire;
  DELIMITER //
  CREATE PROCEDURE patch_qnaire()
  BEGIN

    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_queue_state_site_id" );

    SELECT "Dropping default_interview_method_id column from qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "default_interview_method_id" );
    IF @test = 1 THEN
      ALTER TABLE qnaire
      DROP FOREIGN KEY fk_qnaire_default_interview_method_id,
      DROP INDEX fk_default_interview_method_id;

      ALTER TABLE qnaire DROP COLUMN default_interview_method_id;
    END IF;

    SELECT "Adding new constraint to script table in qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "script_id" );
    IF @test = 0 THEN
      ALTER TABLE qnaire
      ADD COLUMN script_id INT UNSIGNED NOT NULL
      AFTER rank;

      ALTER TABLE qnaire
      ADD INDEX fk_script_id( script_id ASC );

      -- eliminate any qnaires without phases
      DELETE FROM qnaire
      WHERE id IN ( SELECT id FROM (
        SELECT qnaire.id
        FROM qnaire
        LEFT JOIN phase ON qnaire.id = phase.qnaire_id
        WHERE phase.id IS NULL ) AS t
      );

      -- create started event_type for script to be used by each qnaire
      SET @sql = CONCAT(
        "INSERT IGNORE INTO ", @cenozo, ".event_type( name, description ) ",
        "SELECT CONCAT( 'started (', qnaire.name, ')' ), ",
               "CONCAT( 'Started the "', qnaire.name, '" script.' ) ",
        "FROM qnaire" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- now create a script to represent all qnaires
      SET @sql = CONCAT(
        "INSERT INTO ", @cenozo, ".script( ",
          "name, started_event_type_id, completed_event_type_id, sid, repeated, reserved, description ) ",
        "SELECT name, event_type.id, completed_event_type_id, phase.sid, phase.repeated, 1, description ",
        "FROM qnaire ",
        "JOIN ", @cenozo, ".event_type ON event_type.name = CONCAT( 'started (', qnaire.name, ')' ) ",
        "JOIN phase ON qnaire.id = phase.qnaire_id ",
        "AND phase.repeated = 0 ",
        "GROUP BY qnaire.id ",
        "ORDER BY qnaire.rank" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO ", @cenozo, ".application_has_script( application_id, script_id ) ",
        "SELECT application.id, script.id ",
        "FROM ", @cenozo, ".application, ", @cenozo, ".script ",
        "JOIN qnaire ON script.id = qnaire.script_id ",
        "WHERE DATABASE LIKE CONCAT( '%_', application.name )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "UPDATE qnaire JOIN ", @cenozo, ".script USING( name ) ",
        "SET qnaire.script_id = script.id" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "ALTER TABLE qnaire ",
        "ADD CONSTRAINT fk_qnaire_script_id ",
        "FOREIGN KEY( script_id ) REFERENCES ", @cenozo, ".script( id ) ",
        "ON DELETE NO ACTION ON UPDATE NO ACTION" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

    SELECT "Dropping name column from qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "name" );
    IF @test = 1 THEN
      ALTER TABLE qnaire DROP INDEX uq_name;
      ALTER TABLE qnaire DROP COLUMN name;
    END IF;

    SELECT "Dropping completed_event_type_id column from qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "completed_event_type_id" );
    IF @test = 1 THEN
      ALTER TABLE qnaire
      DROP FOREIGN KEY fk_qnaire_completed_event_type_id,
      DROP INDEX fk_completed_event_type_id;

      ALTER TABLE qnaire DROP COLUMN completed_event_type_id;
    END IF;

    SELECT "Dropping withdraw_sid column from qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "withdraw_sid" );
    IF @test = 1 THEN
      ALTER TABLE qnaire DROP COLUMN withdraw_sid;
    END IF;

    SELECT "Dropping description column from qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "description" );
    IF @test = 1 THEN
      ALTER TABLE qnaire DROP COLUMN description;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_qnaire();
DROP PROCEDURE IF EXISTS patch_qnaire;
