SELECT "Adding new qnaire_has_interview_method table" AS "";

CREATE TABLE IF NOT EXISTS qnaire_has_interview_method (
  qnaire_id INT UNSIGNED NOT NULL,
  interview_method_id INT UNSIGNED NOT NULL,
  update_timestamp TIMESTAMP NOT NULL,
  create_timestamp TIMESTAMP NOT NULL,
  PRIMARY KEY (qnaire_id, interview_method_id),
  INDEX fk_interview_method_id (interview_method_id ASC),
  INDEX fk_qnaire_id (qnaire_id ASC),
  CONSTRAINT fk_qnaire_has_interview_method_qnaire_id
    FOREIGN KEY (qnaire_id)
    REFERENCES qnaire (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_qnaire_has_interview_method_interview_method_id
    FOREIGN KEY (interview_method_id)
    REFERENCES interview_method (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

SELECT "Adding default interview method to qnaire_has_interview_method table" AS "";

INSERT IGNORE INTO qnaire_has_interview_method( qnaire_id, interview_method_id )
SELECT qnaire.id, qnaire.default_interview_method_id
FROM qnaire;
