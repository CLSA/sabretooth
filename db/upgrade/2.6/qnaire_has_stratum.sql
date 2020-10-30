DROP PROCEDURE IF EXISTS patch_qnaire_has_stratum;
DELIMITER //
CREATE PROCEDURE patch_qnaire_has_stratum()
  BEGIN

    -- determine the cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );

    SELECT "Creating new qnaire_has_stratum table" AS "";

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS qnaire_has_stratum ( ",
        "qnaire_id INT UNSIGNED NOT NULL, ",
        "stratum_id INT UNSIGNED NOT NULL, ",
        "update_timestamp TIMESTAMP NOT NULL, ",
        "create_timestamp TIMESTAMP NOT NULL, ",
        "PRIMARY KEY (qnaire_id, stratum_id), ",
        "INDEX fk_stratum_id (stratum_id ASC), ",
        "INDEX fk_qnaire_id (qnaire_id ASC), ",
        "CONSTRAINT fk_qnaure_has_stratum_qnaire_id ",
          "FOREIGN KEY (qnaire_id) ",
          "REFERENCES qnaire (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION, ",
        "CONSTRAINT fk_qnaure_has_stratum_stratum_id ",
          "FOREIGN KEY (stratum_id) ",
          "REFERENCES ", @cenozo, ".stratum (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION) ",
      "ENGINE = InnoDB"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_qnaire_has_stratum();
DROP PROCEDURE IF EXISTS patch_qnaire_has_stratum;
