DROP PROCEDURE IF EXISTS patch_cedar_instance;
DELIMITER //
CREATE PROCEDURE patch_cedar_instance()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_queue_state_site_id" );

    SELECT "Removing defunct cedar_instance table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "cedar_instance" );
    IF @test = 1 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      SET @sql = CONCAT(
        "DELETE FROM ", @cenozo, ".access ",
        "WHERE user_id IN ( SELECT user_id FROM cedar_instance )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "DELETE FROM ", @cenozo, ".activity ",
        "WHERE user_id IN ( SELECT user_id FROM cedar_instance )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "DELETE FROM ", @cenozo, ".user_has_application ",
        "WHERE user_id IN ( SELECT user_id FROM cedar_instance )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "DELETE FROM ", @cenozo, ".user_has_language ",
        "WHERE user_id IN ( SELECT user_id FROM cedar_instance )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "DELETE FROM ", @cenozo, ".user_has_collection ",
        "WHERE user_id IN ( SELECT user_id FROM cedar_instance )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "DELETE FROM ", @cenozo, ".user ",
        "WHERE id IN ( SELECT user_id FROM cedar_instance )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE IF EXISTS cedar_instance;

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
    END IF;
  END //
DELIMITER ;

CALL patch_cedar_instance();
DROP PROCEDURE IF EXISTS patch_cedar_instance;
