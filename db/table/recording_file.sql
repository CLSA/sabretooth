CREATE TABLE recording_file (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  recording_id int(10) unsigned NOT NULL,
  language_id int(10) unsigned NOT NULL,
  filename varchar(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_recording_id_language_id (recording_id,language_id),
  KEY fk_recording_id (recording_id),
  KEY fk_language_id (language_id),
  CONSTRAINT fk_recording_file_language_id
    FOREIGN KEY (language_id)
    REFERENCES cenozo.language (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_recording_file_recording_id
    FOREIGN KEY (recording_id)
    REFERENCES recording (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;