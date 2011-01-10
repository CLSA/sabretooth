-- -----------------------------------------------------
-- Data for table .`operation`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- generic operations
DELETE FROM `operation`;
INSERT INTO `operation` (`id`, `name`, `reval`, `description`)
VALUES (NULL, "login_halt", NULL, "Logs out all users (except the user who executes this operation).");
INSERT INTO `operation` (`id`, `name`, `reval`, `description`)
VALUES (NULL, "login_suspend", NULL, "Prevents all users from logging in (except the user who executes this operation).");
INSERT INTO `operation` (`id`, `name`, `reval`, `description`)
VALUES (NULL, "voip_halt", NULL, "Disconnects all VOIP sessions.");
INSERT INTO `operation` (`id`, `name`, `reval`, `description`)
VALUES (NULL, "voip_suspend", NULL, "Prevents any new VOIP sessions from connecting.");

-- user
INSERT INTO `operation` (`id`, `name`, `reval`, `description`)
VALUES (NULL, "user", "remove", "");
INSERT INTO `operation` (`id`, `name`, `reval`, `description`)
VALUES (NULL, "user", "edit", "");
INSERT INTO `operation` (`id`, `name`, `reval`, `description`)
VALUES (NULL, "user", "view", "");
INSERT INTO `operation` (`id`, `name`, `reval`, `description`)
VALUES (NULL, "user", "add", "");
INSERT INTO `operation` (`id`, `name`, `reval`, `description`)
VALUES (NULL, "user", "list", "");
INSERT INTO `operation` (`id`, `name`, `reval`, `description`)
VALUES (NULL, "user_login", NULL, "Logs current user in as different user (like su).");

-- TODO finish list

COMMIT;
