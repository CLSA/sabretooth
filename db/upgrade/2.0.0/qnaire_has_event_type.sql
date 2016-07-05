DROP PROCEDURE IF EXISTS patch_qnaire_has_event_type;
  DELIMITER //
  CREATE PROCEDURE patch_qnaire_has_event_type()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Modifiying constraint delete rules in qnaire_has_event_type table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.REFERENTIAL_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire_has_event_type"
      AND REFERENCED_TABLE_NAME = "qnaire"
      AND ( UPDATE_RULE = "NO ACTION" OR DELETE_RULE = "NO ACTION" ) );
    IF @test > 0 THEN
      ALTER TABLE qnaire_has_event_type
      DROP FOREIGN KEY fk_qnaire_has_event_type_qnaire_id;

      ALTER TABLE qnaire_has_event_type
      ADD CONSTRAINT fk_qnaire_has_event_type_qnaire_id
      FOREIGN KEY (qnaire_id)
      REFERENCES qnaire (id)
      ON DELETE CASCADE
      ON UPDATE CASCADE;
    END IF;

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.REFERENTIAL_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire_has_event_type"
      AND REFERENCED_TABLE_NAME = "event_type"
      AND ( UPDATE_RULE = "NO ACTION" OR DELETE_RULE = "NO ACTION" ) );
    IF @test > 0 THEN
      ALTER TABLE qnaire_has_event_type
      DROP FOREIGN KEY fk_qnaire_has_event_type_event_type_id;

      SET @sql = CONCAT(
        "ALTER TABLE qnaire_has_event_type ",
        "ADD CONSTRAINT fk_qnaire_has_event_type_event_type_id ",
        "FOREIGN KEY (event_type_id) ",
        "REFERENCES ", @cenozo, ".event_type (id) ",
        "ON DELETE CASCADE ",
        "ON UPDATE CASCADE" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_qnaire_has_event_type();
DROP PROCEDURE IF EXISTS patch_qnaire_has_event_type;
