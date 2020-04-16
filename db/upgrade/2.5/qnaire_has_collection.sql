DROP PROCEDURE IF EXISTS patch_qnaire_has_collection;
DELIMITER //
CREATE PROCEDURE patch_qnaire_has_collection()
  BEGIN

    -- determine the cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );

    SELECT "Creating new qnaire_has_collection table" AS "";

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS qnaire_has_collection ( ",
        "qnaire_id INT UNSIGNED NOT NULL, ",
        "collection_id INT UNSIGNED NOT NULL, ",
        "update_timestamp TIMESTAMP NOT NULL, ",
        "create_timestamp TIMESTAMP NOT NULL, ",
        "PRIMARY KEY (qnaire_id, collection_id), ",
        "INDEX fk_collection_id (collection_id ASC), ",
        "INDEX fk_qnaire_id (qnaire_id ASC), ",
        "CONSTRAINT fk_qnaire_has_collection_qnaire_id ",
          "FOREIGN KEY (qnaire_id) ",
          "REFERENCES qnaire (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION, ",
        "CONSTRAINT fk_qnaire_has_collection_collection_id ",
          "FOREIGN KEY (collection_id) ",
          "REFERENCES ", @cenozo, ".collection (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION) ",
      "ENGINE = InnoDB"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_qnaire_has_collection();
DROP PROCEDURE IF EXISTS patch_qnaire_has_collection;
