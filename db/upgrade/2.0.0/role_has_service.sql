DROP PROCEDURE IF EXISTS patch_role_has_service;
DELIMITER //
CREATE PROCEDURE patch_role_has_service()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_queue_state_site_id" );

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
            "ON DELETE CASCADE ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_role_has_service_role_id ",
            "FOREIGN KEY (role_id) ",
            "REFERENCES ", @cenozo, ".role (id) ",
            "ON DELETE CASCADE ",
            "ON UPDATE NO ACTION) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

    -- populate table
    DELETE FROM role_has_service;
    SET @sql = CONCAT(
      "INSERT INTO role_has_service ( role_id, service_id ) ",

      -- framework services

      "SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'access' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'access' AND method = 'GET' AND resource = 0 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'access' AND method = 'POST' AND resource = 0 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'activity' AND method = 'GET' AND resource = 0 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'application' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'application' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'application' AND method = 'POST' AND resource = 0 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'collection' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'collection' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'collection' AND method = 'POST' AND resource = 0 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'consent' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'curator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'consent' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'curator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'consent' AND method = 'POST' AND resource = 0 ",
      "AND role.name IN( 'administrator', 'curator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'event' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'curator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'event' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'curator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'event' AND method = 'POST' AND resource = 0 ",
      "AND role.name IN( 'administrator', 'curator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'jurisdiction' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'jurisdiction' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'jurisdiction' AND method = 'POST' AND resource = 0 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'language' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'note' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'curator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'note' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'curator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'participant' AND method = 'GET' AND resource = 0 ",
      "AND role.name IN( 'administrator', 'curator', 'helpline', 'operator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'quota' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'quota' AND method = 'GET' AND resource = 0 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'quota' AND method = 'GET' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'quota' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'quota' AND method = 'POST' AND resource = 0 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'region_site' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'region_site' AND method = 'GET' AND resource = 0 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'region_site' AND method = 'GET' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'region_site' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'region_site' AND method = 'POST' AND resource = 0 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'setting' AND method = 'GET' AND resource = 0 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'setting' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'site' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'site' AND method = 'GET' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'site' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'site' AND method = 'POST' AND resource = 0 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'state' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'state' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'state' AND method = 'POST' AND resource = 0 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'system_message' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'system_message' AND method = 'GET' AND resource = 0 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'system_message' AND method = 'GET' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'system_message' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'system_message' AND method = 'POST' AND resource = 0 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'user' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'user' AND method = 'GET' AND resource = 0 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'user' AND method = 'GET' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'user' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'user' AND method = 'POST' AND resource = 0 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      -- application services

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'assignment' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'helpline', 'operator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'assignment' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'helpline', 'operator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'assignment' AND method = 'POST' AND resource = 0 ",
      "AND role.name IN( 'helpline', 'operator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'cedar_instance' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'cedar_instance' AND method = 'GET' AND resource = 0 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'cedar_instance' AND method = 'GET' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'cedar_instance' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'cedar_instance' AND method = 'POST' AND resource = 0 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'interview' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'opal_instance' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'opal_instance' AND method = 'GET' AND resource = 0 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'opal_instance' AND method = 'GET' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'opal_instance' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'opal_instance' AND method = 'POST' AND resource = 0 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'phase' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'phase' AND method = 'GET' AND resource = 0 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'phase' AND method = 'GET' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'phase' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'phase' AND method = 'POST' AND resource = 0 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'qnaire' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'qnaire' AND method = 'GET' AND resource = 0 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'qnaire' AND method = 'GET' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'qnaire' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'qnaire' AND method = 'POST' AND resource = 0 ",
      "AND role.name IN( 'administrator' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'queue' AND method = 'GET' AND resource = 0 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'queue' AND method = 'GET' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'queue' AND method = 'PATCH' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'queue_state' AND method = 'DELETE' AND resource = 1 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'queue_state' AND method = 'GET' AND resource = 0 ",
      "AND role.name IN( 'administrator', 'supervisor' ) ",

      "UNION SELECT role.id, service.id FROM ", @cenozo, ".role, service ",
      "WHERE subject = 'queue_state' AND method = 'POST' AND resource = 0 ",
      "AND role.name IN( 'administrator', 'supervisor' )" );

    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_role_has_service();
DROP PROCEDURE IF EXISTS patch_role_has_service;
