DROP PROCEDURE IF EXISTS patch_service;
  DELIMITER //
  CREATE PROCEDURE patch_service()
  BEGIN

    SELECT "Creating new service table" AS "";

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
        subject VARCHAR(45) NOT NULL,
        resource TINYINT(1) NOT NULL DEFAULT 0,
        restricted TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (id),
        UNIQUE INDEX uq_method_subject_resource (method ASC, subject ASC, resource ASC))
      ENGINE = InnoDB;

      INSERT INTO service ( subject, method, resource, restricted ) VALUES

      -- framework services
      ( 'access', 'DELETE', 1, 1 ),
      ( 'access', 'GET', 0, 1 ),
      ( 'access', 'GET', 1, 1 ),
      ( 'access', 'POST', 0, 1 ),
      ( 'activity', 'GET', 0, 1 ),
      ( 'address', 'DELETE', 1, 0 ),
      ( 'address', 'GET', 0, 0 ),
      ( 'address', 'GET', 1, 0 ),
      ( 'address', 'PATCH', 1, 0 ),
      ( 'address', 'POST', 0, 0 ),
      ( 'age_group', 'GET', 0, 0 ),
      ( 'alternate', 'DELETE', 1, 0 ),
      ( 'alternate', 'GET', 0, 0 ),
      ( 'alternate', 'GET', 1, 0 ),
      ( 'alternate', 'PATCH', 1, 0 ),
      ( 'alternate', 'POST', 0, 0 ),
      ( 'application', 'DELETE', 1, 1 ),
      ( 'application', 'GET', 0, 0 ),
      ( 'application', 'GET', 1, 0 ),
      ( 'application', 'PATCH', 1, 1 ),
      ( 'application', 'POST', 0, 1 ),
      ( 'collection', 'DELETE', 1, 1 ),
      ( 'collection', 'GET', 0, 0 ),
      ( 'collection', 'GET', 1, 0 ),
      ( 'collection', 'PATCH', 1, 1 ),
      ( 'collection', 'POST', 0, 1 ),
      ( 'consent', 'DELETE', 1, 1 ),
      ( 'consent', 'GET', 0, 0 ),
      ( 'consent', 'GET', 1, 0 ),
      ( 'consent', 'PATCH', 1, 1 ),
      ( 'consent', 'POST', 0, 1 ),
      ( 'event', 'DELETE', 1, 1 ),
      ( 'event', 'GET', 0, 0 ),
      ( 'event', 'GET', 1, 0 ),
      ( 'event', 'PATCH', 1, 1 ),
      ( 'event', 'POST', 0, 1 ),
      ( 'event_type', 'GET', 0, 0 ),
      ( 'event_type', 'GET', 1, 0 ),
      ( 'jurisdiction', 'DELETE', 1, 1 ),
      ( 'jurisdiction', 'GET', 0, 0 ),
      ( 'jurisdiction', 'GET', 1, 0 ),
      ( 'jurisdiction', 'PATCH', 1, 1 ),
      ( 'jurisdiction', 'POST', 0, 1 ),
      ( 'language', 'GET', 0, 0 ),
      ( 'language', 'GET', 1, 0 ),
      ( 'participant', 'GET', 0, 1 ),
      ( 'participant', 'GET', 1, 1 ),
      ( 'participant', 'PATCH', 1, 1 ),
      ( 'phone', 'DELETE', 1, 0 ),
      ( 'phone', 'GET', 0, 0 ),
      ( 'phone', 'GET', 1, 0 ),
      ( 'phone', 'PATCH', 1, 0 ),
      ( 'phone', 'POST', 0, 0 ),
      ( 'quota', 'DELETE', 1, 1 ),
      ( 'quota', 'GET', 0, 1 ),
      ( 'quota', 'GET', 1, 1 ),
      ( 'quota', 'PATCH', 1, 1 ),
      ( 'quota', 'POST', 0, 1 ),
      ( 'region', 'GET', 0, 0 ),
      ( 'region', 'GET', 1, 0 ),
      ( 'region_site', 'DELETE', 1, 1 ),
      ( 'region_site', 'GET', 0, 1 ),
      ( 'region_site', 'GET', 1, 1 ),
      ( 'region_site', 'PATCH', 1, 1 ),
      ( 'region_site', 'POST', 0, 1 ),
      ( 'role', 'GET', 0, 0 ),
      ( 'role', 'GET', 1, 0 ),
      ( 'self', 'GET', 1, 0 ),
      ( 'self', 'PATCH', 1, 0 ),
      ( 'site', 'DELETE', 1, 1 ),
      ( 'site', 'GET', 0, 0 ),
      ( 'site', 'GET', 1, 1 ),
      ( 'site', 'PATCH', 1, 1 ),
      ( 'site', 'POST', 0, 1 ),
      ( 'state', 'DELETE', 1, 1 ),
      ( 'state', 'GET', 0, 0 ),
      ( 'state', 'GET', 1, 0 ),
      ( 'state', 'PATCH', 1, 1 ),
      ( 'state', 'POST', 0, 1 ),
      ( 'system_message', 'DELETE', 1, 1 ),
      ( 'system_message', 'GET', 0, 1 ),
      ( 'system_message', 'GET', 1, 1 ),
      ( 'system_message', 'PATCH', 1, 1 ),
      ( 'system_message', 'POST', 0, 1 ),
      ( 'user', 'DELETE', 1, 1 ),
      ( 'user', 'GET', 0, 1 ),
      ( 'user', 'GET', 1, 1 ),
      ( 'user', 'PATCH', 1, 1 ),
      ( 'user', 'POST', 0, 1 ),

      -- application services
      ( 'assignment', 'DELETE', 1, 1 ),
      ( 'assignment', 'GET', 0, 1 ),
      ( 'assignment', 'GET', 1, 1 ),
      ( 'assignment', 'PATCH', 1, 1 ),
      ( 'interview', 'DELETE', 1, 1 ),
      ( 'interview', 'GET', 0, 0 ),
      ( 'interview', 'GET', 1, 0 ),
      ( 'interview', 'PATCH', 1, 1 ),
      ( 'interview', 'POST', 0, 1 ),
      ( 'opal_instance', 'DELETE', 1, 1 ),
      ( 'opal_instance', 'GET', 0, 1 ),
      ( 'opal_instance', 'GET', 1, 1 ),
      ( 'opal_instance', 'PATCH', 1, 1 ),
      ( 'opal_instance', 'POST', 0, 1 ),
      ( 'qnaire', 'DELETE', 1, 1 ),
      ( 'qnaire', 'GET', 0, 1 ),
      ( 'qnaire', 'GET', 1, 1 ),
      ( 'qnaire', 'PATCH', 1, 1 ),
      ( 'qnaire', 'POST', 0, 1 ),
      ( 'queue', 'GET', 0, 1 ),
      ( 'queue', 'GET', 1, 1 ),
      ( 'queue', 'PATCH', 1, 1 );
    END IF;
  END //
DELIMITER ;

CALL patch_service();
DROP PROCEDURE IF EXISTS patch_service;
