DROP PROCEDURE IF EXISTS patch_ivr_appointment;
DELIMITER //
CREATE PROCEDURE patch_ivr_appointment()
  BEGIN
    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Adding new ivr_appointment table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "ivr_appointment" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS ivr_appointment ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "participant_id INT UNSIGNED NOT NULL, ",
          "phone_id INT UNSIGNED NOT NULL, ",
          "datetime DATETIME NOT NULL, ",
          "completed TINYINT(1) NULL DEFAULT NULL COMMENT 'If the interview was completed by the appointment.', ",
          "PRIMARY KEY (id), ",
          "INDEX fk_participant_id (participant_id ASC), ",
          "INDEX dk_completed (completed ASC), ",
          "INDEX fk_phone_id (phone_id ASC), ",
          "INDEX dk_datetime (datetime ASC), ",
          "CONSTRAINT fk_ivr_appointment_participant_id ",
            "FOREIGN KEY (participant_id) ",
           "REFERENCES ", @cenozo, ".participant (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_ivr_appointment_phone_id ",
            "FOREIGN KEY (phone_id) ",
            "REFERENCES ", @cenozo, ".phone (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_ivr_appointment();
DROP PROCEDURE IF EXISTS patch_ivr_appointment;
