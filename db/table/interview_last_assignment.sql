CREATE TABLE interview_last_assignment (
  interview_id int(10) unsigned NOT NULL,
  assignment_id int(10) unsigned DEFAULT NULL,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (interview_id),
  KEY fk_assignment_id (assignment_id),
  CONSTRAINT fk_interview_last_assignment_assignment_id
    FOREIGN KEY (assignment_id)
    REFERENCES assignment (id)
    ON DELETE SET NULL
    ON UPDATE NO ACTION,
  CONSTRAINT fk_interview_last_assignment_interview_id
    FOREIGN KEY (interview_id)
    REFERENCES interview (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;