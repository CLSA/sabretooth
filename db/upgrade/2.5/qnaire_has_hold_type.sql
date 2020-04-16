DROP PROCEDURE IF EXISTS patch_qnaire_has_hold_type;
DELIMITER //
CREATE PROCEDURE patch_qnaire_has_hold_type()
  BEGIN

    -- determine the cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );

    SELECT "Adding new qnaire_has_hold_type table" AS "";

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS qnaire_has_hold_type ( ",
        "qnaire_id INT UNSIGNED NOT NULL, ",
        "hold_type_id INT UNSIGNED NOT NULL, ",
        "update_timestamp TIMESTAMP NOT NULL, ",
        "create_timestamp TIMESTAMP NOT NULL, ",
        "PRIMARY KEY (qnaire_id, hold_type_id), ",
        "INDEX fk_hold_type_id (hold_type_id ASC), ",
        "INDEX fk_qnaire_id (qnaire_id ASC), ",
        "CONSTRAINT fk_qnaire_has_hold_type_qnaire_id ",
          "FOREIGN KEY (qnaire_id) ",
          "REFERENCES qnaire (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION, ",
        "CONSTRAINT fk_qnaire_has_hold_type_hold_type_id ",
          "FOREIGN KEY (hold_type_id) ",
          "REFERENCES ", @cenozo, ".hold_type (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION) ",
      "ENGINE = InnoDB"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_qnaire_has_hold_type();
DROP PROCEDURE IF EXISTS patch_qnaire_has_hold_type;
