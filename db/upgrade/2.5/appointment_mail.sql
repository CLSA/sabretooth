DROP PROCEDURE IF EXISTS patch_appointment_mail;
DELIMITER //
CREATE PROCEDURE patch_appointment_mail()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );

    SELECT "Adding new appointment_mail table" AS "";
    
    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS appointment_mail ( ",
        "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
        "update_timestamp TIMESTAMP NOT NULL, ",
        "create_timestamp TIMESTAMP NOT NULL, ",
        "site_id INT UNSIGNED NOT NULL, ",
        "language_id INT UNSIGNED NOT NULL, ",
        "from_name VARCHAR(255) NULL DEFAULT NULL, ",
        "from_address VARCHAR(127) NOT NULL, ",
        "cc_address VARCHAR(255) NULL DEFAULT NULL, ",
        "bcc_address VARCHAR(255) NULL DEFAULT NULL, ",
        "delay INT UNSIGNED NOT NULL, ",
        "subject VARCHAR(255) NOT NULL, ",
        "body TEXT NOT NULL, ",
        "PRIMARY KEY (id), ",
        "INDEX fk_site_id (site_id ASC), ",
        "INDEX fk_language_id (language_id ASC), ",
        "UNIQUE INDEX uq_site_id_language_id_delay (site_id ASC, language_id ASC, delay ASC), ",
        "CONSTRAINT fk_appointment_mail_site_id ",
          "FOREIGN KEY (site_id) ",
          "REFERENCES ", @cenozo, ".site (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION, ",
        "CONSTRAINT fk_appointment_mail_language_id ",
          "FOREIGN KEY (language_id) ",
          "REFERENCES ", @cenozo, ".language (id) ",
          "ON DELETE NO ACTION ",
          "ON UPDATE NO ACTION) ",
      "ENGINE = InnoDB"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_appointment_mail();
DROP PROCEDURE IF EXISTS patch_appointment_mail;



