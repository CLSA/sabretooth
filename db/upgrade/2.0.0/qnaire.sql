DROP PROCEDURE IF EXISTS patch_qnaire;
  DELIMITER //
  CREATE PROCEDURE patch_qnaire()
  BEGIN

    SELECT "Renaming default_interview_method_id to interview_method_id in qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "default_interview_method_id" );
    IF @test = 1 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      ALTER TABLE qnaire
      DROP FOREIGN KEY fk_qnaire_default_interview_method_id,
      DROP INDEX fk_default_interview_method_id;

      ALTER TABLE qnaire
      CHANGE default_interview_method_id interview_method_id INT UNSIGNED NOT NULL;

      ALTER TABLE qnaire
      ADD INDEX fk_interview_method_id (interview_method_id ASC),
      ADD CONSTRAINT fk_qnaire_interview_method_id 
      FOREIGN KEY( interview_method_id ) REFERENCES interview_method( id ) 
      ON DELETE NO ACTION ON UPDATE NO ACTION;

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_qnaire();
DROP PROCEDURE IF EXISTS patch_qnaire;
