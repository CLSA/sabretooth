DROP PROCEDURE IF EXISTS patch_queue_has_participant;
DELIMITER //
CREATE PROCEDURE patch_queue_has_participant()
  BEGIN
    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Adding new queue_has_participant table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.tables
      WHERE table_schema = @cenozo
      AND table_name = "queue_has_participant" );

    IF @test = 0 THEN
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS queue_has_participant ( ",
          "queue_id INT UNSIGNED NOT NULL, ",
          "participant_id INT UNSIGNED NOT NULL, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "site_id INT UNSIGNED NULL, ",
          "qnaire_id INT UNSIGNED NULL, ",
          "start_qnaire_date DATE NULL, ",
          "PRIMARY KEY (queue_id, participant_id), ",
          "INDEX fk_participant_id (participant_id ASC), ",
          "INDEX fk_queue_id (queue_id ASC), ",
          "INDEX fk_qnaire_id (qnaire_id ASC), ",
          "INDEX fk_site_id (site_id ASC), ",
          "CONSTRAINT fk_queue_has_participant_queue_id ",
            "FOREIGN KEY (queue_id) ",
            "REFERENCES queue (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_queue_has_participant_participant_id ",
            "FOREIGN KEY (participant_id) ",
            "REFERENCES ", @cenozo, ".participant (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_queue_has_participant_qnaire_id ",
            "FOREIGN KEY (qnaire_id) ",
            "REFERENCES qnaire (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_queue_has_participant_site_id ",
            "FOREIGN KEY (site_id) ",
            "REFERENCES ", @cenozo, ".site (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION) ",
        "ENGINE = InnoDB" );
      PREPARE queue_has_participantment FROM @sql;
      EXECUTE queue_has_participantment;
      DEALLOCATE PREPARE queue_has_participantment;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue_has_participant();
DROP PROCEDURE IF EXISTS patch_queue_has_participant;
