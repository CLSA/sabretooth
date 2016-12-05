DROP PROCEDURE IF EXISTS patch_role_has_service;
DELIMITER //
CREATE PROCEDURE patch_role_has_service()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

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
    TRUNCATE role_has_service;

    -- administrator
    SET @sql = CONCAT(
      "INSERT INTO role_has_service( role_id, service_id ) ",
      "SELECT role.id, service.id ",
      "FROM ", @cenozo, ".role, service ",
      "WHERE role.name = 'administrator' ",
      "AND service.restricted = 1 ",
      "AND service.id NOT IN ( ",
        "SELECT id FROM service ",
        "WHERE subject = 'appointment' ",
        "OR subject = 'callback' ",
        "OR ( subject = 'assignment' AND method = 'POST' ) ",
        "OR ( subject = 'phone_call' AND method != 'DELETE' ) ",
        "OR ( subject = 'queue' AND method = 'PATCH' ) ",
        "OR subject IN( 'shift', 'shift_template' ) ",
      ")" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    -- curator
    SET @sql = CONCAT(
      "INSERT INTO role_has_service( role_id, service_id ) ",
      "SELECT role.id, service.id ",
      "FROM ", @cenozo, ".role, service ",
      "WHERE role.name = 'curator' ",
      "AND service.restricted = 1 ",
      "AND ( ",
        "service.subject IN ( ",
          "'address', 'alternate', 'consent', 'event', 'form', 'jurisdiction', 'language', 'note', ",
          "'participant', 'phone', 'region_site', 'report', 'report_type', 'source', 'state', 'token' ",
        ") ",
        "OR ( subject = 'report_restriction' AND method = 'GET' ) ",
      ")" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    -- helpline, operator and operator+
    SET @sql = CONCAT(
      "INSERT INTO role_has_service( role_id, service_id ) ",
      "SELECT role.id, service.id ",
      "FROM ", @cenozo, ".role, service ",
      "WHERE role.name IN( 'helpline', 'operator', 'operator+' ) ",
      "AND service.restricted = 1 ",
      "AND service.subject IN ( 'appointment', 'assignment', 'participant', 'phone_call', 'token' )" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    -- helpline can edit notes
    SET @sql = CONCAT(
      "INSERT INTO role_has_service( role_id, service_id ) ",
      "SELECT role.id, service.id ",
      "FROM ", @cenozo, ".role, service ",
      "WHERE role.name = 'helpline' ",
      "AND service.restricted = 1 ",
      "AND service.subject  = 'note'" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    -- remove participant list from operator role
    SET @sql = CONCAT(
      "DELETE FROM role_has_service ",
      "WHERE role_id = ( SELECT id FROM ", @cenozo, ".role WHERE name = 'operator' ) ",
      "AND service_id IN ( ",
        "SELECT id FROM service ",
        "WHERE subject = 'participant' AND method = 'GET' AND resource = 0 ",
      ")" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    -- supervisor
    SET @sql = CONCAT(
      "INSERT INTO role_has_service( role_id, service_id ) ",
      "SELECT role.id, service.id ",
      "FROM ", @cenozo, ".role, service ",
      "WHERE role.name = 'supervisor' ",
      "AND service.restricted = 1 ",
      "AND service.id NOT IN ( ",
        "SELECT id FROM service ",
        "WHERE subject IN( ",
          "'address', 'alternate', 'application', 'availability_type', 'collection', 'consent', 'consent_type', ",
          "'event', 'event_type', 'export', 'export_file', 'export_column', 'export_restriction', 'form', ",
          "'hin', 'interview', 'jurisdiction', 'language', 'phone', 'qnaire', 'quota', 'recording', ",
          "'recording_file', 'region_site', 'report_schedule', 'script', 'source', 'state' ) ",
        "OR ( subject = 'report_restriction' AND method IN( 'DELETE', 'PATCH', 'POST' ) ) ",
        "OR ( subject = 'report_type' AND method IN( 'DELETE', 'PATCH', 'POST' ) ) ",
        "OR ( subject = 'queue' AND method = 'PATCH' ) ",
        "OR ( subject = 'setting' AND method = 'GET' ) ",
        "OR ( subject = 'site' AND method IN ( 'DELETE', 'POST' ) ) ",
      ")" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    -- only tier > 1 can view collections
    SET @sql = CONCAT(
      "INSERT IGNORE INTO role_has_service( role_id, service_id ) ",
      "SELECT role.id, service.id ",
      "FROM service, ", @cenozo, ".role ",
      "JOIN ", @cenozo, ".application_type_has_role ",
        "ON role.id = application_type_has_role.role_id ",
      "JOIN ", @cenozo, ".application_type ",
        "ON application_type_has_role.application_type_id = application_type.id ",
        "AND application_type.name = 'sabretooth' ",
      "WHERE role.tier > 1 ",
      "AND service.restricted = 1 ",
      "AND service.subject = 'collection' ",
      "AND service.method = 'GET' ",
      "AND service.resource = 1" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_role_has_service();
DROP PROCEDURE IF EXISTS patch_role_has_service;
