-- Existing installations: run once, after backing up article.
-- Admin saving also adds this nullable field when ALTER privilege is available.
ALTER TABLE article ADD COLUMN articleimages LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
-- Rollback: keep this column and restore the previous PHP files. No data deletion is needed.
