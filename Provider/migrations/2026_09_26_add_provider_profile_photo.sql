-- Stores only the random filename for each provider's privately stored profile photo.
ALTER TABLE service_providers
    ADD COLUMN provider_photo_file VARCHAR(100) NULL AFTER government_document_file;
