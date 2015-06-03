DROP PROCEDURE IF EXISTS patch_participant_last_interview;
DELIMITER //
CREATE PROCEDURE patch_participant_last_interview()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_queue_state_site_id" );

    SELECT "Adding new participant_last_interview caching table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "participant_last_interview" );
    IF @test = 0 THEN

      DROP VIEW IF EXISTS participant_last_interview;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS participant_last_interview ( ",
          "participant_id INT UNSIGNED NOT NULL, ",
          "interview_id INT UNSIGNED NULL, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "PRIMARY KEY (participant_id), ",
          "INDEX fk_interview_id (interview_id ASC), ",
          "CONSTRAINT fk_participant_last_interview_participant_id ",
            "FOREIGN KEY (participant_id) ",
            "REFERENCES ", @cenozo, ".participant (id) ",
            "ON DELETE CASCADE ",
            "ON UPDATE CASCADE, ",
          "CONSTRAINT fk_participant_last_interview_interview_id ",
            "FOREIGN KEY (interview_id) ",
            "REFERENCES interview (id) ",
            "ON DELETE SET NULL ",
            "ON UPDATE NO ACTION) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SELECT "Populating participant_last_interview table" AS "";

      SET @sql = CONCAT(
        "REPLACE INTO participant_last_interview( participant_id, interview_id ) ",
        "SELECT participant.id, interview.id ",
        "FROM ", @cenozo, ".participant ",
        "JOIN interview ON participant.id = interview.participant_id ",
        "AND interview.start_datetime <=> ( ",
          "SELECT MAX( start_datetime ) ",
          "FROM interview ",
          "WHERE participant.id = interview.participant_id ",
          "GROUP BY interview.participant_id ",
          "LIMIT 1 ",
        ")" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

    END IF;

  END //
DELIMITER ;

CALL patch_participant_last_interview();
DROP PROCEDURE IF EXISTS patch_participant_last_interview;
