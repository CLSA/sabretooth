
DROP PROCEDURE IF EXISTS patch_role_has_service;
DELIMITER //
CREATE PROCEDURE patch_role_has_service()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Replacing operation_id with role_has_service_id in role_has_service table" AS "";

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

      -- populate role_has_service_id column using the operation table
      INSERT INTO role_has_service ( role_id, service_id )
      SELECT role_id, service.id
      FROM operation
      JOIN role_has_operation ON operation.id = role_has_operation.operation_id
      JOIN service ON operation.subject = service.subject
      AND service.method = "DELETE"
      AND service.resource = 1
      WHERE operation.type = "push"
      AND name = "delete";

      INSERT INTO role_has_service ( role_id, service_id )
      SELECT role_id, service.id
      FROM operation
      JOIN role_has_operation ON operation.id = role_has_operation.operation_id
      JOIN service ON operation.subject = service.subject
      AND service.method = "GET"
      AND service.resource = 0
      WHERE operation.type = "widget"
      AND name = "list";

      INSERT INTO role_has_service ( role_id, service_id )
      SELECT role_id, service.id
      FROM operation
      JOIN role_has_operation ON operation.id = role_has_operation.operation_id
      JOIN service ON operation.subject = service.subject
      AND service.method = "GET"
      AND service.resource = 1
      WHERE operation.type = "widget"
      AND name = "list";

      INSERT INTO role_has_service ( role_id, service_id )
      SELECT role_id, service.id
      FROM operation
      JOIN role_has_operation ON operation.id = role_has_operation.operation_id
      JOIN service ON operation.subject = service.subject
      AND service.method = "PATCH"
      AND service.resource = 1
      WHERE operation.type = "push"
      AND name = "edit";

      INSERT INTO role_has_service ( role_id, service_id )
      SELECT role_id, service.id
      FROM operation
      JOIN role_has_operation ON operation.id = role_has_operation.operation_id
      JOIN service ON operation.subject = service.subject
      AND service.method = "POST"
      AND service.resource = 0
      WHERE operation.type = "push"
      AND name = "new";

      INSERT INTO role_has_service ( role_id, service_id )
      SELECT role_id, service.id
      FROM operation
      JOIN role_has_operation ON operation.id = role_has_operation.operation_id
      JOIN service ON operation.subject = service.subject
      AND service.method = "PUT"
      AND service.resource = 1
      WHERE operation.type = "push"
      AND name = "edit";

    END IF;
  END //
DELIMITER ;

CALL patch_role_has_service();
DROP PROCEDURE IF EXISTS patch_role_has_service;
