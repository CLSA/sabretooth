
DROP PROCEDURE IF EXISTS patch_role_has_service;
DELIMITER //
CREATE PROCEDURE patch_role_has_service()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_system_message_site_id" );

    SELECT "Creating new role_has_service table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "role_has_service" );
    IF @test = 0 THEN
      -- add new role_has_service_table
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS role_has_service ( ",
          "role_id INT UNSIGNED NOT NULL, ",
          "service_id INT UNSIGNED NOT NULL, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "PRIMARY KEY (role_id, service_id), ",
          "INDEX fk_role_id (role_id ASC), ",
          "INDEX fk_service_id (service_id ASC), ",
          "CONSTRAINT fk_role_has_service_service_id ",
            "FOREIGN KEY (service_id) ",
            "REFERENCES service (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_role_has_service_role_id ",
            "FOREIGN KEY (role_id) ",
            "REFERENCES ", @cenozo, ".role (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- populate role_has_service column using the operation table
      INSERT INTO role_has_service ( role_id, service_id )
      SELECT role_id, service.id
      FROM operation
      JOIN role_has_operation ON operation.id = role_has_operation.operation_id
      JOIN service ON subject = SUBSTRING_INDEX( path, "/", 1 )
      AND method = "DELETE"
      WHERE name = "delete"
      GROUP BY subject;

      INSERT INTO role_has_service ( role_id, service_id )
      SELECT role_id, service.id
      FROM operation
      JOIN role_has_operation ON operation.id = role_has_operation.operation_id
      JOIN service ON subject = path
      AND method = "GET"
      WHERE name = "list"
      GROUP BY subject;

      INSERT INTO role_has_service ( role_id, service_id )
      SELECT role_id, service.id
      FROM operation
      JOIN role_has_operation ON operation.id = role_has_operation.operation_id
      JOIN service ON CONCAT( subject, "/<id>" ) = path
      AND method = "GET"
      WHERE name = "view"
      GROUP BY subject;

      INSERT INTO role_has_service ( role_id, service_id )
      SELECT role_id, service.id
      FROM operation
      JOIN role_has_operation ON operation.id = role_has_operation.operation_id
      JOIN service ON CONCAT( subject, "/<id>" ) = path
      AND method = "PATCH"
      WHERE name = "edit"
      GROUP BY subject;

      INSERT INTO role_has_service ( role_id, service_id )
      SELECT role_id, service.id
      FROM operation
      JOIN role_has_operation ON operation.id = role_has_operation.operation_id
      JOIN service ON subject = path
      AND method = "POST"
      WHERE name = "add" OR name = "new"
      GROUP BY subject;

      INSERT INTO role_has_service ( role_id, service_id )
      SELECT role_id, service.id
      FROM operation
      JOIN role_has_operation ON operation.id = role_has_operation.operation_id
      JOIN service ON CONCAT( subject, "/<id>/", SUBSTRING( name, 8 ), "/<id>" ) = path
      AND method = "DELETE"
      WHERE name LIKE "delete_%"
      GROUP BY subject;

      INSERT INTO role_has_service ( role_id, service_id )
      SELECT role_id, service.id
      FROM operation
      JOIN role_has_operation ON operation.id = role_has_operation.operation_id
      JOIN service ON CONCAT( subject, "/<id>/", SUBSTRING( name, 5 ) ) = path
      AND method = "GET"
      WHERE name LIKE "add_%" OR name LIKE "new_%"
      GROUP BY subject,
      SUBSTRING( name, 5 );

      INSERT INTO role_has_service ( role_id, service_id )
      SELECT role_id, service.id
      FROM operation
      JOIN role_has_operation ON operation.id = role_has_operation.operation_id
      JOIN service ON CONCAT( subject, "/<id>/", SUBSTRING( name, 5 ), "/<id>" ) = path
      AND method = "GET"
      WHERE name LIKE "add_%" OR name LIKE "new_%"
      GROUP BY subject,
      SUBSTRING( name, 5 );

      INSERT INTO role_has_service ( role_id, service_id )
      SELECT role_id, service.id
      FROM operation
      JOIN role_has_operation ON operation.id = role_has_operation.operation_id
      JOIN service ON CONCAT( subject, "/<id>/", SUBSTRING( name, 5 ) ) = path
      AND method = "POST"
      WHERE name LIKE "add_%" OR name LIKE "new_%"
      GROUP BY subject,
      SUBSTRING( name, 5 );

      INSERT INTO role_has_service ( role_id, service_id )
      SELECT role_id, service.id
      FROM operation
      JOIN role_has_operation ON operation.id = role_has_operation.operation_id
      JOIN service ON CONCAT( subject, "/<id>/", SUBSTRING( name, 5 ), "/<id>" ) = path
      AND method = "PATCH"
      WHERE name LIKE "add_%" OR name LIKE "new_%"
      GROUP BY subject,
      SUBSTRING( name, 5 );
    END IF;
  END //
DELIMITER ;

CALL patch_role_has_service();
DROP PROCEDURE IF EXISTS patch_role_has_service;
