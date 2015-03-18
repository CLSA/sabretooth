DROP PROCEDURE IF EXISTS patch_prerecruit;
DELIMITER //
CREATE PROCEDURE patch_prerecruit()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Adding new prerecruit table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = @cenozo
      AND TABLE_NAME = "prerecruit" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS prerecruit ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT , ",
          "update_timestamp TIMESTAMP NOT NULL , ",
          "create_timestamp TIMESTAMP NOT NULL , ",
          "participant_id INT UNSIGNED NOT NULL , ",
          "quota_id INT UNSIGNED NOT NULL , ",
          "total INT UNSIGNED NOT NULL DEFAULT 0 , ",
          "selected INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'If selected this will be a number between 1 and total.' , ",
          "PRIMARY KEY (id) , ",
          "INDEX fk_participant_id (participant_id ASC) , ",
          "INDEX fk_quota_id (quota_id ASC) , ",
          "UNIQUE INDEX uq_participant_id_quota_id (participant_id ASC, quota_id ASC) , ",
          "CONSTRAINT fk_prerecruit_participant_id ",
            "FOREIGN KEY (participant_id ) ",
            "REFERENCES ", @cenozo, ".participant (id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_prerecruit_quota_id ",
            "FOREIGN KEY (quota_id ) ",
            "REFERENCES ", @cenozo, ".quota (id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;
  END //
DELIMITER ;

CALL patch_prerecruit();
DROP PROCEDURE IF EXISTS patch_prerecruit;
