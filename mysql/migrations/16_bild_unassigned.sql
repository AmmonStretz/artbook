USE artbook;

-- Allow images to exist without a participant (unassigned)
ALTER TABLE teilnehmer_bild
    DROP FOREIGN KEY fk_bild_teilnehmer;

ALTER TABLE teilnehmer_bild
    MODIFY COLUMN teilnehmer_id INT UNSIGNED NULL;

ALTER TABLE teilnehmer_bild
    ADD CONSTRAINT fk_bild_teilnehmer
        FOREIGN KEY (teilnehmer_id) REFERENCES teilnehmer(id) ON DELETE SET NULL;
