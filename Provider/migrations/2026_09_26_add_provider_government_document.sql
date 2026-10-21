-- Stores only the random filename for each provider's privately stored ID upload.
ALTER TABLE service_providers
    ADD COLUMN government_document_file VARCHAR(100) NULL AFTER experience;
