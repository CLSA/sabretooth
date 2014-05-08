DROP PROCEDURE IF EXISTS patch_qnaire_has_quota;
DELIMITER //
CREATE PROCEDURE patch_qnaire_has_quota()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Adding new qnaire_has_quota table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire_has_quota" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS qnaire_has_quota ( ",
          "qnaire_id INT UNSIGNED NOT NULL, ",
          "quota_id INT UNSIGNED NOT NULL, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "PRIMARY KEY (qnaire_id, quota_id), ",
          "INDEX fk_quota_id (quota_id ASC), ",
          "INDEX fk_qnaire_id (qnaire_id ASC), ",
          "CONSTRAINT fk_qnaire_has_quota_qnaire_id ",
            "FOREIGN KEY (qnaire_id) ",
            "REFERENCES qnaire (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_qnaire_has_quota_quota_id ",
            "FOREIGN KEY (quota_id) ",
            "REFERENCES ", @cenozo, ".quota (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION) ",
        "ENGINE = InnoDB ",
        "COMMENT = 'Record means that quota is disabled for qnaire'" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      INSERT IGNORE INTO qnaire_has_quota( qnaire_id, quota_id )
      SELECT qnaire.id, quota_id
      FROM quota_state, qnaire
      WHERE quota_state.disabled = true;
    END IF;

  END //
DELIMITER ;

CALL patch_qnaire_has_quota();
DROP PROCEDURE IF EXISTS patch_qnaire_has_quota;
