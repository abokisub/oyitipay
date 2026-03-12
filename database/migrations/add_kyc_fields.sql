-- Run this script to apply KYC migration
-- Command: mysql -u root -p kobopoint < database/migrations/add_kyc_fields.sql
-- Or copy-paste into MySQL Workbench/phpMyAdmin

USE kobopoint; -- Change to your database name

-- Add KYC fields to user table
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `customer_id` VARCHAR(255) NULL COMMENT 'Xixapay Customer ID' AFTER `apikey`;
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `bvn` VARCHAR(11) NULL COMMENT 'Bank Verification Number' AFTER `customer_id`;
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `nin` VARCHAR(11) NULL COMMENT 'National ID Number' AFTER `bvn`;
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `date_of_birth` DATE NULL COMMENT 'Date of Birth (YYYY-MM-DD)' AFTER `nin`;
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `address` TEXT NULL COMMENT 'Residential Address' AFTER `date_of_birth`;
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `city` VARCHAR(100) NULL COMMENT 'City' AFTER `address`;
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `state` VARCHAR(100) NULL COMMENT 'State' AFTER `city`;
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `postal_code` VARCHAR(10) NULL COMMENT 'Postal Code' AFTER `state`;
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `id_card_path` VARCHAR(255) NULL COMMENT 'ID Card File Path' AFTER `postal_code`;
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `utility_bill_path` VARCHAR(255) NULL COMMENT 'Utility Bill Path' AFTER `id_card_path`;
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `kyc_documents` JSON NULL COMMENT 'KYC Files and Metadata' AFTER `utility_bill_path`;
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `kyc_status` ENUM('pending', 'submitted', 'approved', 'rejected') DEFAULT 'pending' AFTER `kyc_documents`;
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `kyc_submitted_at` TIMESTAMP NULL AFTER `kyc_status`;

-- Add indexes for performance
ALTER TABLE `user` ADD INDEX IF NOT EXISTS `idx_customer_id` (`customer_id`);
ALTER TABLE `user` ADD INDEX IF NOT EXISTS `idx_kyc_status` (`kyc_status`);

-- Verify the changes
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'user' 
  AND COLUMN_NAME IN ('customer_id', 'bvn', 'nin', 'date_of_birth', 'address', 'kyc_status')
ORDER BY ORDINAL_POSITION;
