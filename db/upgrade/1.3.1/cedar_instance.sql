DROP PROCEDURE IF EXISTS patch_cedar_instance;
DELIMITER //
CREATE PROCEDURE patch_cedar_instance()
  BEGIN
    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Adding new cedar_instance table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "cedar_instance" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS cedar_instance ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "user_id INT UNSIGNED NOT NULL, ",
          "PRIMARY KEY (id), ",
          "INDEX fk_user_id (user_id ASC), ",
          "UNIQUE INDEX uq_user_id (user_id ASC), ",
          "CONSTRAINT fk_cedar_instance_user_id ",
            "FOREIGN KEY (user_id) ",
            "REFERENCES ", @cenozo, ".user (id) ",
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
CALL patch_cedar_instance();
DROP PROCEDURE IF EXISTS patch_cedar_instance;
