
DROP PROCEDURE IF EXISTS patch_service;
DELIMITER //
CREATE PROCEDURE patch_service()
  BEGIN

    SELECT "Replacing operation_id with service_id in service table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "service" );
    IF @test = 0 THEN
      -- add new service_table
      CREATE TABLE IF NOT EXISTS service (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        update_timestamp TIMESTAMP NOT NULL,
        create_timestamp TIMESTAMP NOT NULL,
        method ENUM('DELETE','GET','PATCH','POST','PUT') NOT NULL,
        subject VARCHAR(45) NOT NULL,
        resource TINYINT(1) NOT NULL DEFAULT 1,
        restricted TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (id),
        UNIQUE INDEX uq_method_subject_resource (method ASC, subject ASC, resource ASC))
      ENGINE = InnoDB;

      -- populate service_id column using the operation table
      INSERT INTO service ( method, subject, resource, restricted )
      SELECT "DELETE", subject, 1, restricted
      FROM operation
      WHERE type = "push"
      AND name = "delete";

      INSERT INTO service ( method, subject, resource, restricted )
      SELECT "GET", subject, 0, restricted
      FROM operation
      WHERE type = "widget"
      AND name = "list";

      INSERT INTO service ( method, subject, resource, restricted )
      SELECT "GET", subject, 1, restricted
      FROM operation
      WHERE type = "widget"
      AND name = "list";

      INSERT INTO service ( method, subject, resource, restricted )
      SELECT "PATCH", subject, 1, restricted
      FROM operation
      WHERE type = "push"
      AND name = "edit";

      INSERT INTO service ( method, subject, resource, restricted )
      SELECT "POST", subject, 0, restricted
      FROM operation
      WHERE type = "push"
      AND name = "new";

      INSERT INTO service ( method, subject, resource, restricted )
      SELECT "PUT", subject, 1, restricted
      FROM operation
      WHERE type = "push"
      AND name = "edit";
    END IF;
  END //
DELIMITER ;

CALL patch_service();
DROP PROCEDURE IF EXISTS patch_service;
