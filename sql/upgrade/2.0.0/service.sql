
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
      CREATE TABLE IF NOT EXISTS service(
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        update_timestamp TIMESTAMP NOT NULL,
        create_timestamp TIMESTAMP NOT NULL,
        method ENUM('DELETE','GET','PATCH','POST','PUT') NOT NULL,
        path VARCHAR(511) NOT NULL,
        restricted TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (id),
        UNIQUE INDEX uq_method_path (method ASC, path ASC))
      ENGINE = InnoDB;

      -- populate service_id column using the operation table
      INSERT INTO service( method, path, restricted )
      SELECT "DELETE", CONCAT( subject, "/<id>" ), restricted
      FROM operation
      WHERE name = "delete"
      GROUP BY subject;

      INSERT INTO service( method, path, restricted )
      SELECT "GET", subject, restricted
      FROM operation
      WHERE name = "list"
      GROUP BY subject;

      INSERT INTO service( method, path, restricted )
      SELECT "GET", CONCAT( subject, "/<id>" ), restricted
      FROM operation
      WHERE name = "view"
      GROUP BY subject;

      INSERT INTO service( method, path, restricted )
      SELECT "PATCH", CONCAT( subject, "/<id>" ), restricted
      FROM operation
      WHERE name = "edit"
      GROUP BY subject;

      INSERT INTO service( method, path, restricted )
      SELECT "POST", subject, restricted
      FROM operation
      WHERE name = "add" OR name = "new"
      GROUP BY subject;

      INSERT INTO service( method, path, restricted )
      SELECT "DELETE", CONCAT( subject, "/<id>/", SUBSTRING( name, 8 ), "/<id>" ), restricted
      FROM operation
      WHERE name LIKE "delete_%"
      GROUP BY subject;

      INSERT INTO service( method, path, restricted )
      SELECT "GET", CONCAT( subject, "/<id>/", SUBSTRING( name, 5 ) ), restricted
      FROM operation
      WHERE name LIKE "add_%" OR name LIKE "new_%"
      GROUP BY subject, SUBSTRING( name, 5 );

      INSERT INTO service( method, path, restricted )
      SELECT "GET", CONCAT( subject, "/<id>/", SUBSTRING( name, 5 ), "/<id>" ), restricted
      FROM operation
      WHERE name LIKE "add_%" OR name LIKE "new_%"
      GROUP BY subject, SUBSTRING( name, 5 );

      INSERT INTO service( method, path, restricted )
      SELECT "PATCH", CONCAT( subject, "/<id>/", SUBSTRING( name, 5 ), "/<id>" ), restricted
      FROM operation
      WHERE name LIKE "add_%" OR name LIKE "new_%"
      GROUP BY subject, SUBSTRING( name, 5 );

      INSERT INTO service( method, path, restricted )
      SELECT "POST", CONCAT( subject, "/<id>/", SUBSTRING( name, 5 ) ), restricted
      FROM operation
      WHERE name LIKE "add_%" OR name LIKE "new_%"
      GROUP BY subject, SUBSTRING( name, 5 );
    END IF;
  END //
DELIMITER ;

CALL patch_service();
DROP PROCEDURE IF EXISTS patch_service;
