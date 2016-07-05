DROP PROCEDURE IF EXISTS patch_qnaire_has_quota;
  DELIMITER //
  CREATE PROCEDURE patch_qnaire_has_quota()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Modifiying constraint delete rules in qnaire_has_quota table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.REFERENTIAL_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire_has_quota"
      AND REFERENCED_TABLE_NAME = "qnaire"
      AND ( UPDATE_RULE = "NO ACTION" OR DELETE_RULE = "NO ACTION" ) );
    IF @test > 0 THEN
      ALTER TABLE qnaire_has_quota
      DROP FOREIGN KEY fk_qnaire_has_quota_qnaire_id;

      ALTER TABLE qnaire_has_quota
      ADD CONSTRAINT fk_qnaire_has_quota_qnaire_id
      FOREIGN KEY (qnaire_id)
      REFERENCES qnaire (id)
      ON DELETE CASCADE
      ON UPDATE CASCADE;
    END IF;

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.REFERENTIAL_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire_has_quota"
      AND REFERENCED_TABLE_NAME = "quota"
      AND ( UPDATE_RULE = "NO ACTION" OR DELETE_RULE = "NO ACTION" ) );
    IF @test > 0 THEN
      ALTER TABLE qnaire_has_quota
      DROP FOREIGN KEY fk_qnaire_has_quota_quota_id;

      SET @sql = CONCAT(
        "ALTER TABLE qnaire_has_quota ",
        "ADD CONSTRAINT fk_qnaire_has_quota_quota_id ",
        "FOREIGN KEY (quota_id) ",
        "REFERENCES ", @cenozo, ".quota (id) ",
        "ON DELETE CASCADE ",
        "ON UPDATE CASCADE" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_qnaire_has_quota();
DROP PROCEDURE IF EXISTS patch_qnaire_has_quota;
