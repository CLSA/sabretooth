DROP PROCEDURE IF EXISTS patch_queue_state;
DELIMITER //
CREATE PROCEDURE patch_queue_state()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    -- determine the service name
    SET @service = (
      SELECT RIGHT( DATABASE(),
                    CHAR_LENGTH( DATABASE() ) -
                    CHAR_LENGTH( LEFT( USER(), LOCATE( '@', USER() ) ) ) ) );

    SELECT "Adding new queue_state table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "queue_state" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS queue_state ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "queue_id INT UNSIGNED NOT NULL, ",
          "site_id INT UNSIGNED NOT NULL, ",
          "qnaire_id INT UNSIGNED NOT NULL, ",
          "enabled TINYINT(1) NOT NULL DEFAULT 0, ",
          "PRIMARY KEY (id), ",
          "INDEX fk_queue_id (queue_id ASC), ",
          "INDEX fk_site_id (site_id ASC), ",
          "INDEX fk_qnaire_id (qnaire_id ASC), ",
          "UNIQUE INDEX uq_queue_id_site_id_qnaire_id (queue_id ASC, site_id ASC, qnaire_id ASC), ",
          "CONSTRAINT fk_queue_state_queue_id ",
            "FOREIGN KEY (queue_id) ",
            "REFERENCES queue (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_queue_state_site_id ",
            "FOREIGN KEY (site_id) ",
            "REFERENCES ", @cenozo, ".site (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_queue_state_qnaire_id ",
            "FOREIGN KEY (qnaire_id) ",
            "REFERENCES qnaire (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      
      SET @sql = CONCAT(
        "INSERT INTO queue_state( queue_id, site_id, qnaire_id, enabled ) ",
        "SELECT queue.id, site.id, qnaire.id, false ",
        "FROM qnaire, setting ",
        "JOIN queue ON setting.name = queue.name ",
        "JOIN ", @cenozo, ".site ",
        "JOIN ", @cenozo, ".service ON site.service_id = service.id ",
        "LEFT join setting_value ON setting.id = setting_value.setting_id ",
        "AND site.id = setting_value.site_id ",
        "WHERE service.name = '", @service, "' ",
        "AND setting.category = 'queue state' ",
        "AND IFNULL( setting_value.value, setting.value ) = 'false' ",
        "ORDER BY site.name, queue.name" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

CALL patch_queue_state();
DROP PROCEDURE IF EXISTS patch_queue_state;
