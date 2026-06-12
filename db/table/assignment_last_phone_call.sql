CREATE TABLE assignment_last_phone_call (
  assignment_id int(10) unsigned NOT NULL,
  phone_call_id int(10) unsigned DEFAULT NULL,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (assignment_id),
  KEY fk_phone_call_id (phone_call_id),
  CONSTRAINT fk_assignment_last_phone_call_assignment_id
    FOREIGN KEY (assignment_id)
    REFERENCES assignment (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_assignment_last_phone_call_phone_call_id
    FOREIGN KEY (phone_call_id)
    REFERENCES phone_call (id)
    ON DELETE SET NULL
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
