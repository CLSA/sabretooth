-- add the new tier column
-- we need to create a procedure which only alters the role table if the tier column is missing
DROP PROCEDURE IF EXISTS patch_role;
DELIMITER //
CREATE PROCEDURE patch_role()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "role"
      AND COLUMN_NAME = "tier" );
     IF @test = 0 THEN
       ALTER TABLE role
       ADD COLUMN tier INT UNSIGNED NOT NULL DEFAULT 1
       COMMENT '1 = normal, 2 = site admin, 3 = global admin';
     END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_role();
DROP PROCEDURE IF EXISTS patch_role;

-- make admin tier 3 and supervisor tier 2
UPDATE role SET tier = 3 WHERE name = "administrator";
UPDATE role SET tier = 2 WHERE name = "supervisor";
