DROP PROCEDURE IF EXISTS patch_trainee_user;
DELIMITER //
CREATE PROCEDURE patch_trainee_user()
  BEGIN

    -- determine the cenozo database name
    SELECT unique_constraint_schema INTO @cenozo
    FROM information_schema.referential_constraints
    WHERE constraint_schema = DATABASE()
    AND constraint_name = "fk_access_site_id";

    SELECT "Creating new trainee_user table" AS "";

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS trainee_user ( ",
        "id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, ",
        "update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(), ",
        "create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(), ",
        "user_id INT(10) UNSIGNED NOT NULL, ",
        "PRIMARY KEY (id), ",
        "INDEX fk_user_id (user_id ASC), ",
        "UNIQUE INDEX uq_user_id (user_id ASC), ",
        "CONSTRAINT fk_trainee_user_user_id ",
          "FOREIGN KEY (user_id) ",
          "REFERENCES ", @cenozo, ".user (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION) ",
      "ENGINE = InnoDB"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;
  END //
DELIMITER ;

CALL patch_trainee_user();
DROP PROCEDURE IF EXISTS patch_trainee_user;
