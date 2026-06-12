CREATE TABLE assignment (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  user_id int(10) unsigned NOT NULL,
  role_id int(10) unsigned NOT NULL,
  site_id int(10) unsigned NOT NULL COMMENT 'The site from which the user was assigned.',
  interview_id int(10) unsigned NOT NULL,
  queue_id int(10) unsigned NOT NULL COMMENT 'The queue that the assignment came from.',
  start_datetime datetime NOT NULL,
  end_datetime datetime DEFAULT NULL,
  PRIMARY KEY (id),
  KEY fk_interview_id (interview_id),
  KEY fk_queue_id (queue_id),
  KEY dk_start_datetime (start_datetime),
  KEY dk_end_datetime (end_datetime),
  KEY fk_user_id (user_id),
  KEY fk_site_id (site_id),
  KEY fk_role_id (role_id),
  CONSTRAINT fk_assignment_interview_id
    FOREIGN KEY (interview_id)
    REFERENCES interview (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_assignment_queue_id
    FOREIGN KEY (queue_id)
    REFERENCES queue (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_assignment_role_id
    FOREIGN KEY (role_id)
    REFERENCES cenozo.role (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_assignment_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_assignment_user_id
    FOREIGN KEY (user_id)
    REFERENCES cenozo.user (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
