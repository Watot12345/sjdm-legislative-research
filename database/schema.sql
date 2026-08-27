-- ====================================================================
-- Legislative Research System Complete Database Migration & Schema
-- Database Target: legislative_db
-- ====================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------------------
-- 1. Table: users
-- Core system authentication, RBAC roles (admin, researcher, data_encoder, viewer),
-- and account status management.
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `role` ENUM('admin','researcher','data_encoder','viewer') DEFAULT 'viewer',
  `department` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('active','inactive','suspended') DEFAULT 'active',
  `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `two_factor_type` ENUM('email_otp','totp') NOT NULL DEFAULT 'email_otp',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` VARCHAR(50) DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------------------
-- 1.1 Table: user_otps
-- Manages secure 6-digit one-time passwords for 2FA authentication.
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `user_otps`;
CREATE TABLE `user_otps` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `otp_code` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `is_used` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_otp` (`user_id`, `expires_at`, `is_used`),
  CONSTRAINT `fk_user_otps_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------------------
-- 1.2 Table: user_trusted_devices
-- Stores 12-hour trusted device tokens to prevent repetitive OTP prompts.
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `user_trusted_devices`;
CREATE TABLE `user_trusted_devices` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `device_token` VARCHAR(64) NOT NULL UNIQUE,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_device_token` (`device_token`, `user_id`, `expires_at`),
  CONSTRAINT `fk_trusted_devices_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------------------
-- 2. Table: activity_logs
-- System-wide audit trail capturing actions across all modules.
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user` VARCHAR(100) NOT NULL,
  `action` VARCHAR(255) NOT NULL,
  `document_id` VARCHAR(50) DEFAULT NULL,
  `module` VARCHAR(100) NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------------------
-- 3. Table: ai_cache
-- Caches Gemini AI API prompt-response pairs via MD5 hashing
-- to optimize token consumption and enforce Free Tier compliance.
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `ai_cache`;
CREATE TABLE `ai_cache` (
  `cache_id` INT AUTO_INCREMENT PRIMARY KEY,
  `prompt_hash` VARCHAR(32) UNIQUE NOT NULL,
  `response_text` LONGTEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_prompt_hash` (`prompt_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------------------
-- 4. Table: policy_documents
-- Primary repository for uploaded policies, extracted texts, legal
-- citations, and AI analysis findings.
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `policy_documents`;
CREATE TABLE `policy_documents` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `document_id` VARCHAR(20) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `issues` TEXT DEFAULT NULL,
  `objectives` TEXT DEFAULT NULL,
  `researcher` VARCHAR(100) DEFAULT NULL,
  `file_name` VARCHAR(255) DEFAULT NULL,
  `file_path` VARCHAR(500) DEFAULT NULL,
  `file_content` LONGTEXT DEFAULT NULL,
  `status` ENUM('Draft','Pending','Approved','Archived') DEFAULT 'Pending',
  `upload_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ai_processed` ENUM('Yes','No') DEFAULT 'No',
  `selected_for_analysis` ENUM('Yes','No') DEFAULT 'No',
  `analysis_notes` TEXT DEFAULT NULL,
  `nlp_keywords` VARCHAR(500) DEFAULT NULL,
  `similar_ordinance` VARCHAR(255) DEFAULT NULL,
  `ai_analysis_result` TEXT DEFAULT NULL,
  `ai_processed_date` DATETIME DEFAULT NULL,
  `data_collection_status` ENUM('Not Started','Submitted for Approval','Approved for Collection','Approved','Rejected','Imported') DEFAULT 'Not Started',
  `dataset_id` VARCHAR(20) DEFAULT NULL,
  `data_collection_date` DATETIME DEFAULT NULL,
  `impact_assessment_status` VARCHAR(50) DEFAULT 'Pending',
  `keywords` TEXT DEFAULT NULL,
  `legal_citations` TEXT DEFAULT NULL,
  `legal_analysis` TEXT DEFAULT NULL,
  `legal_analysis_status` ENUM('Pending','Completed','Error') DEFAULT 'Pending',
  `legal_analysis_date` DATETIME DEFAULT NULL,
  `analyzed_by` VARCHAR(100) DEFAULT NULL,
  `legal_summary` TEXT DEFAULT NULL,
  `summary_generated` ENUM('Yes','No') DEFAULT 'No',
  `summary_date` DATETIME DEFAULT NULL,
  `short_description` TEXT DEFAULT NULL,
  `legal_provisions` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_id` (`document_id`),
  KEY `idx_document_id` (`document_id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_upload_date` (`upload_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `upcoming_deadlines` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) DEFAULT 'General',
  `due_date` DATE NOT NULL,
  `priority` ENUM('high', 'medium', 'low') DEFAULT 'medium',
  `status` ENUM('pending', 'completed') DEFAULT 'pending',
  `created_by` VARCHAR(50) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------
-- 5. Table: datasets
-- Sectoral datasets collected for policy evaluation and analysis.
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `datasets`;
CREATE TABLE `datasets` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `dataset_id` VARCHAR(20) NOT NULL,
  `doc_id` VARCHAR(50) DEFAULT NULL,
  `dataset_name` VARCHAR(255) NOT NULL,
  `dataset_category` VARCHAR(50) NOT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `source_office` VARCHAR(100) NOT NULL,
  `file_name` VARCHAR(255) DEFAULT NULL,
  `file_path` VARCHAR(500) DEFAULT NULL,
  `file_type` VARCHAR(50) DEFAULT NULL,
  `file_size` VARCHAR(50) DEFAULT NULL,
  `record_count` INT(11) DEFAULT 0,
  `data_period` VARCHAR(50) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('Validated','Pending','Needs Review','Approved','Rejected') DEFAULT 'Pending',
  `validation_notes` TEXT DEFAULT NULL,
  `ai_analyzed` TINYINT(1) DEFAULT 0,
  `ai_summary` TEXT DEFAULT NULL,
  `validation_completed` TINYINT(1) DEFAULT 0,
  `ai_processed` ENUM('Yes','No') DEFAULT 'No',
  `uploaded_by` VARCHAR(100) DEFAULT NULL,
  `upload_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approval_status` VARCHAR(50) DEFAULT 'Approved',
  `approval_date` DATETIME DEFAULT NULL,
  `approved_by` VARCHAR(100) DEFAULT NULL,
  `rejection_date` DATETIME DEFAULT NULL,
  `rejected_by` VARCHAR(100) DEFAULT NULL,
  `supporting_docs_generated` ENUM('Yes','No') DEFAULT 'No',
  `ai_analysis_date` DATETIME DEFAULT NULL,
  `ready_for_impact_assessment` VARCHAR(3) DEFAULT 'No',
  `impact_assessment_id` VARCHAR(50) DEFAULT NULL,
  `impact_assessment_created` VARCHAR(3) DEFAULT 'No',
  `impact_assessment_date` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dataset_id` (`dataset_id`),
  KEY `idx_dataset_id` (`dataset_id`),
  KEY `idx_source` (`source_office`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`dataset_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------------------
-- 6. Table: supporting_documents
-- AI-generated legal framework documents (Comparative, Harmonization,
-- Legal Mapping, Implementation, M&E, Recommendations) per dataset.
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `supporting_documents`;
CREATE TABLE `supporting_documents` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `document_id` VARCHAR(50) NOT NULL,
  `dataset_id` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `content` TEXT DEFAULT NULL,
  `generated_date` DATETIME DEFAULT NULL,
  `generated_by` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_id` (`document_id`),
  KEY `dataset_id` (`dataset_id`),
  CONSTRAINT `supporting_documents_ibfk_1` FOREIGN KEY (`dataset_id`) REFERENCES `datasets` (`dataset_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------------------
-- 7. Table: impact_assessments
-- Regulatory Impact Assessments (RIA) scoring relevance, effectiveness,
-- sustainability, and equity.
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `impact_assessments`;
CREATE TABLE `impact_assessments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `assessment_id` VARCHAR(20) NOT NULL,
  `policy_title` VARCHAR(255) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `assessment_period` VARCHAR(20) NOT NULL,
  `overall_rating` ENUM('High','Moderate','Low') NOT NULL,
  `assessment_summary` TEXT NOT NULL,
  `implementation_rate` INT(11) DEFAULT 0,
  `beneficiaries` INT(11) DEFAULT 0,
  `budget_utilization` INT(11) DEFAULT 0,
  `status` ENUM('Pending','Completed') DEFAULT 'Pending',
  `ai_evaluation` TEXT DEFAULT NULL,
  `ai_recommendations` TEXT DEFAULT NULL,
  `created_by` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `assessment_status` VARCHAR(50) DEFAULT 'Pending',
  `updated_date` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` VARCHAR(100) DEFAULT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `impact_rating` VARCHAR(50) DEFAULT 'Pending',
  `dataset_id` VARCHAR(50) DEFAULT NULL,
  `kpi_relevance` INT(11) DEFAULT 0,
  `kpi_sustainability` INT(11) DEFAULT 0,
  `kpi_equity` INT(11) DEFAULT 0,
  `impact_percentage` INT(11) DEFAULT 0,
  `kpi_evaluated` VARCHAR(3) DEFAULT 'No',
  `kpi_evaluation_date` DATETIME DEFAULT NULL,
  `workflow_action` VARCHAR(50) DEFAULT NULL,
  `submitted_to_benchmark` VARCHAR(3) DEFAULT 'No',
  `submitted_to_benchmark_date` DATETIME DEFAULT NULL,
  `kpi_efficiency` INT(11) DEFAULT 0,
  `kpi_effectiveness` INT(11) DEFAULT 0,
  `submitted_to_benchmark_metric` VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assessment_id` (`assessment_id`),
  KEY `idx_assessment_id` (`assessment_id`),
  KEY `idx_policy_title` (`policy_title`),
  KEY `idx_department` (`department`),
  KEY `idx_rating` (`overall_rating`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------------------
-- 8. Table: benchmarking_submissions
-- Queued policies submitted for LGU cross-jurisdictional benchmarking.
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `benchmarking_submissions`;
CREATE TABLE `benchmarking_submissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `benchmark_id` VARCHAR(50) NOT NULL,
  `assessment_id` VARCHAR(50) NOT NULL,
  `policy_title` VARCHAR(255) NOT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `impact_rating` VARCHAR(20) DEFAULT NULL,
  `impact_percentage` INT(11) DEFAULT NULL,
  `combined_content` LONGTEXT DEFAULT NULL,
  `document_count` INT(11) DEFAULT 0,
  `submitted_by` VARCHAR(100) DEFAULT NULL,
  `submitted_date` DATETIME DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'Pending Comparison',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `benchmark_id` (`benchmark_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------------------
-- 9. Table: benchmarking_matrix
-- 10-criteria quantitative evaluation matrix for policy benchmarking.
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `benchmarking_matrix`;
CREATE TABLE `benchmarking_matrix` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `benchmark_id` VARCHAR(50) NOT NULL,
  `criteria1` INT(11) DEFAULT 0,
  `criteria2` INT(11) DEFAULT 0,
  `criteria3` INT(11) DEFAULT 0,
  `criteria4` INT(11) DEFAULT 0,
  `criteria5` INT(11) DEFAULT 0,
  `criteria6` INT(11) DEFAULT 0,
  `criteria7` INT(11) DEFAULT 0,
  `criteria8` INT(11) DEFAULT 0,
  `criteria9` INT(11) DEFAULT 0,
  `criteria10` INT(11) DEFAULT 0,
  `average_score` DECIMAL(3,1) DEFAULT 0.0,
  `rating` VARCHAR(20) DEFAULT NULL,
  `recommendation` VARCHAR(50) DEFAULT NULL,
  `comments` TEXT DEFAULT NULL,
  `evaluated_by` VARCHAR(100) DEFAULT NULL,
  `evaluated_date` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `benchmark_id` (`benchmark_id`),
  CONSTRAINT `benchmarking_matrix_ibfk_1` FOREIGN KEY (`benchmark_id`) REFERENCES `benchmarking_submissions` (`benchmark_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------------------
-- 10. Table: benchmark_comparisons
-- Cross-LGU policy alignment and similarity score records.
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `benchmark_comparisons`;
CREATE TABLE `benchmark_comparisons` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `comparison_id` VARCHAR(20) NOT NULL,
  `policy_a` VARCHAR(255) NOT NULL,
  `policy_b` VARCHAR(255) NOT NULL,
  `lgu_a` VARCHAR(100) NOT NULL,
  `lgu_b` VARCHAR(100) NOT NULL,
  `similarity_score` DECIMAL(5,2) NOT NULL,
  `key_differences` TEXT DEFAULT NULL,
  `best_practices` TEXT DEFAULT NULL,
  `recommendations` TEXT DEFAULT NULL,
  `comparison_method` VARCHAR(50) DEFAULT 'Manual Analysis',
  `status` ENUM('Completed','Pending') DEFAULT 'Completed',
  `analyzed_by` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `comparison_id` (`comparison_id`),
  KEY `idx_comparison_id` (`comparison_id`),
  KEY `idx_similarity` (`similarity_score`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------------------
-- 11. Table: reports
-- Generated legislative briefs, executive summaries, and output files.
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `report_id` VARCHAR(20) NOT NULL,
  `report_title` VARCHAR(255) NOT NULL,
  `report_type` VARCHAR(50) NOT NULL,
  `policy_category` VARCHAR(50) NOT NULL,
  `output_format` VARCHAR(20) NOT NULL,
  `report_description` TEXT DEFAULT NULL,
  `date_from` DATE DEFAULT NULL,
  `date_to` DATE DEFAULT NULL,
  `report_status` ENUM('Draft','Pending Review','Published') DEFAULT 'Draft',
  `ai_content` TEXT DEFAULT NULL,
  `created_by` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_id` (`report_id`),
  KEY `idx_report_id` (`report_id`),
  KEY `idx_report_type` (`report_type`),
  KEY `idx_status` (`report_status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_category` (`policy_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------------------
-- 12. Table: research_projects
-- Grouped legislative research projects and timeline milestones.
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `research_projects`;
CREATE TABLE `research_projects` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_name` VARCHAR(255) NOT NULL,
  `document_id` VARCHAR(20) DEFAULT NULL,
  `status` ENUM('Active','Completed','On Hold') DEFAULT 'Active',
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `researcher` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`),
  CONSTRAINT `research_projects_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `policy_documents` (`document_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------------------
-- 13. Table: policy_keywords
-- Extracted keyword mappings for policy documents.
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `policy_keywords`;
CREATE TABLE `policy_keywords` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `document_id` VARCHAR(20) DEFAULT NULL,
  `keyword` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_keyword_document` (`document_id`),
  CONSTRAINT `policy_keywords_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `policy_documents` (`document_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ====================================================================
-- DATABASE VIEWS (Real-Time Aggregated Dashboard Metrics)
-- ====================================================================

-- View 1: dashboard_stats
DROP VIEW IF EXISTS `dashboard_stats`;
CREATE VIEW `dashboard_stats` AS
SELECT
  (SELECT COUNT(0) FROM `policy_documents`) AS `total_policies`,
  (SELECT COUNT(0) FROM `policy_documents` WHERE `status` = 'Approved') AS `approved_policies`,
  (SELECT COUNT(0) FROM `policy_documents` WHERE `status` = 'Pending') AS `pending_policies`,
  (SELECT COUNT(0) FROM `policy_documents` WHERE `status` = 'Archived') AS `archived_policies`,
  (SELECT COUNT(0) FROM `impact_assessments`) AS `total_assessments`,
  (SELECT COUNT(0) FROM `impact_assessments` WHERE `status` = 'Completed') AS `completed_assessments`,
  (SELECT COUNT(0) FROM `impact_assessments` WHERE `overall_rating` = 'High') AS `high_impact`,
  (SELECT COUNT(0) FROM `impact_assessments` WHERE `ai_evaluation` IS NOT NULL) AS `ai_analyses`,
  (SELECT COUNT(0) FROM `policy_keywords`) AS `total_keywords`;

-- View 2: dataset_stats
DROP VIEW IF EXISTS `dataset_stats`;
CREATE VIEW `dataset_stats` AS
SELECT
  COUNT(0) AS `total_datasets`,
  COUNT(DISTINCT `source_office`) AS `total_sources`,
  COUNT(DISTINCT `dataset_category`) AS `total_categories`,
  SUM(CASE WHEN `status` = 'Validated' THEN 1 ELSE 0 END) AS `validated`,
  SUM(CASE WHEN `status` = 'Pending' THEN 1 ELSE 0 END) AS `pending`,
  SUM(CASE WHEN `status` = 'Needs Review' THEN 1 ELSE 0 END) AS `needs_review`,
  SUM(CASE WHEN `upload_date` >= CURDATE() THEN 1 ELSE 0 END) AS `uploaded_today`,
  SUM(CASE WHEN `ai_analyzed` = 1 THEN 1 ELSE 0 END) AS `ai_analyzed`,
  SUM(CASE WHEN `validation_completed` = 1 THEN 1 ELSE 0 END) AS `validated_files`
FROM `datasets`;

-- View 3: benchmark_stats
DROP VIEW IF EXISTS `benchmark_stats`;
CREATE VIEW `benchmark_stats` AS
SELECT
  COUNT(0) AS `total_comparisons`,
  AVG(`similarity_score`) AS `avg_similarity`,
  SUM(CASE WHEN `similarity_score` >= 90 THEN 1 ELSE 0 END) AS `best_practices`,
  SUM(CASE WHEN `comparison_method` = 'AI Similarity Analysis' THEN 1 ELSE 0 END) AS `ai_analyses`,
  COUNT(DISTINCT `policy_a`) + COUNT(DISTINCT `policy_b`) AS `total_policies`,
  SUM(CASE WHEN `status` = 'Completed' THEN 1 ELSE 0 END) AS `completed`,
  SUM(CASE WHEN `status` = 'Pending' THEN 1 ELSE 0 END) AS `pending`
FROM `benchmark_comparisons`;

-- View 4: report_stats
DROP VIEW IF EXISTS `report_stats`;
CREATE VIEW `report_stats` AS
SELECT
  COUNT(0) AS `total_reports`,
  SUM(CASE WHEN `report_status` = 'Published' THEN 1 ELSE 0 END) AS `published`,
  SUM(CASE WHEN `report_status` = 'Draft' THEN 1 ELSE 0 END) AS `draft`,
  SUM(CASE WHEN `report_status` = 'Pending Review' THEN 1 ELSE 0 END) AS `pending_review`,
  SUM(CASE WHEN `output_format` = 'PDF' THEN 1 ELSE 0 END) AS `pdf_reports`,
  SUM(CASE WHEN `output_format` = 'Microsoft Word' THEN 1 ELSE 0 END) AS `word_reports`,
  SUM(CASE WHEN `ai_content` IS NOT NULL THEN 1 ELSE 0 END) AS `ai_generated`,
  SUM(CASE WHEN `created_at` >= CURRENT_TIMESTAMP() - INTERVAL 30 DAY THEN 1 ELSE 0 END) AS `last_30_days`
FROM `reports`;

-- ====================================================================
-- INITIAL SEED DATA (Full dataset matching legislative_db.sql)
-- Default Password for seed user accounts: admin123
-- ====================================================================

-- 1. Users Seed
INSERT IGNORE INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `department`, `status`, `two_factor_enabled`, `two_factor_type`, `created_at`, `updated_at`, `created_by`, `last_login`) VALUES
(1, 'admin', '$2y$10$e.w0S8D84k0FwIerCsqpvuY4b3/9KjW8iG76vW3S5291.ZqV81p9K', 'System Administrator', 'admin@sjdm.gov.ph', 'admin', 'Legislative Research Division', 'active', 1, 'email_otp', '2026-08-17 07:53:21', '2026-08-22 00:27:20', 'system', '2026-08-22 00:27:20'),
(2, 'admin2', '$2y$12$o17wXkZfnI3zb3ICxCCsWuNxQLN1iBQnkPZ21LSNagoTyZzsIRMRC', 'Legislative Administrator', 'asierra389@gmail.com', 'admin', 'Sanggunian Panlungsod', 'active', 1, 'email_otp', '2026-08-17 07:53:21', '2026-08-22 00:27:20', 'system', '2026-08-22 00:27:20'),
(5, 'researcher', '$2y$12$3eC2CuZnSieTgqup8RYZHOkGCnKcbHRRn.klN2akAktfo553Vppqy', 'Senior Legislative Researcher', 'researcher@sjdm.gov.ph', 'researcher', 'Policy Research & Analysis Division', 'active', 1, 'email_otp', '2026-08-22 00:08:58', '2026-08-22 00:27:33', 'system', '2026-08-22 00:27:33'),
(6, 'encoder', '$2y$12$3eC2CuZnSieTgqup8RYZHOkGCnKcbHRRn.klN2akAktfo553Vppqy', 'Data Specialist & Records Officer', 'encoder@sjdm.gov.ph', 'data_encoder', 'Records & Archives Management', 'active', 1, 'email_otp', '2026-08-22 00:08:58', '2026-08-22 00:28:09', 'system', '2026-08-22 00:28:09'),
(7, 'viewer', '$2y$12$3eC2CuZnSieTgqup8RYZHOkGCnKcbHRRn.klN2akAktfo553Vppqy', 'Legislative Reviewer / Council Liaison', 'viewer@sjdm.gov.ph', 'viewer', 'Sangguniang Panlungsod (City Council)', 'active', 1, 'email_otp', '2026-08-22 00:08:58', '2026-08-22 00:12:51', 'system', '2026-08-22 00:12:51');

-- 2. Policy Documents Seed
INSERT IGNORE INTO `policy_documents` (`id`, `document_id`, `title`, `category`, `description`, `issues`, `objectives`, `researcher`, `file_name`, `file_path`, `file_content`, `status`, `upload_date`, `updated_at`, `ai_processed`, `selected_for_analysis`, `analysis_notes`, `nlp_keywords`, `similar_ordinance`, `ai_analysis_result`, `ai_processed_date`, `data_collection_status`, `dataset_id`, `data_collection_date`, `impact_assessment_status`, `keywords`, `legal_citations`, `legal_analysis`, `legal_analysis_status`, `legal_analysis_date`, `analyzed_by`, `legal_summary`, `summary_generated`, `summary_date`, `short_description`, `legal_provisions`) VALUES
(1, 'POL-20260817-001', 'Comprehensive Solid Waste Segregation & MRF Ordinance', 'Environmental', 'An ordinance enforcing mandatory household waste segregation at source and establishing Barangay Materials Recovery Facilities across San Jose Del Monte.', 'High volume of unsegregated solid waste, low recycling rate, illegal dumping along waterways.', 'Reduce landfilled waste by 40%, establish 100% barangay MRFs, enforce fines for non-segregation.', 'admin', NULL, NULL, NULL, 'Pending', '2026-08-17 08:55:59', '2026-08-17 09:50:48', 'No', 'No', NULL, NULL, NULL, NULL, NULL, 'Approved', NULL, NULL, 'Ready for Assessment', 'Waste, Segregation, Comprehensive, Solid, Environmental, Enforcing, Mandatory, Household', 'RA 9003 (Ecological Solid Waste Management Act of 2000), SJDM Ordinance No. 2021-04', 'RA 9003 (Ecological Solid Waste Management Act of 2000), RA 7160 (Local Government Code of 1991)', 'Completed', '2026-08-17 09:17:23', 'admin2', NULL, 'No', NULL, NULL, NULL),
(2, 'POL-20260817-002', 'Local Public Transport Route Plan & Traffic Strategy', 'Local Governance', 'A strategic transport management ordinance regulating tricycle terminals and jeepney routes along Quirino Highway and Tungkong Mangga.', 'Peak hour traffic bottlenecks around Tungko crossing, illegal tricycle terminals blocking public roads.', 'Streamline public transport routes, establish designated passenger loading bays, clear illegal obstructions.', 'admin', NULL, NULL, NULL, 'Pending', '2026-08-17 08:55:59', '2026-08-17 19:05:46', 'No', 'No', NULL, NULL, NULL, NULL, NULL, 'Approved', NULL, NULL, 'Ready for Assessment', 'Traffic Management, Quirino Highway, Transport Route, Tricycle Terminals, SJDM', 'RA 4136 (Land Transportation Code), SJDM Ordinance No. 2022-12', NULL, 'Pending', NULL, NULL, NULL, 'No', NULL, NULL, NULL),
(3, 'POL-20260817-003', 'Barangay Health Center Free Maintenance Medicine Program', 'Public Health', 'An ordinance expanding municipal health center services to provide free maintenance medications for senior citizens and indigent residents.', 'High out-of-pocket medical expenses for indigent seniors, limited access to maintenance medicine in rural barangays.', 'Provide 100% free hypertension and diabetes medication in all 59 barangay health centers.', 'admin', NULL, NULL, NULL, 'Approved', '2026-08-17 08:55:59', '2026-08-17 08:55:59', 'No', 'No', NULL, NULL, NULL, NULL, NULL, 'Not Started', NULL, NULL, 'Pending', 'Public Health, Senior Citizens, Free Medicine, Barangay Health Center, SJDM', 'RA 11223 (Universal Health Care Act), RA 9994 (Expanded Senior Citizens Act)', NULL, 'Pending', NULL, NULL, NULL, 'No', NULL, NULL, NULL);

-- 3. Datasets Seed
INSERT IGNORE INTO `datasets` (`id`, `dataset_id`, `doc_id`, `dataset_name`, `dataset_category`, `category`, `source_office`, `file_name`, `file_path`, `file_type`, `file_size`, `record_count`, `data_period`, `description`, `status`, `validation_notes`, `ai_analyzed`, `ai_summary`, `validation_completed`, `ai_processed`, `uploaded_by`, `upload_date`, `created_at`, `approval_status`, `approval_date`, `approved_by`, `rejection_date`, `rejected_by`, `supporting_docs_generated`, `ai_analysis_date`, `ready_for_impact_assessment`, `impact_assessment_id`, `impact_assessment_created`, `impact_assessment_date`) VALUES
(1, 'DS-4265', 'POL-20260817-001', 'Comprehensive Solid Waste Segregation & MRF Ordinance', '', 'Environmental', 'Policy Research', '', '', '', '0', 0, NULL, 'An ordinance enforcing mandatory household waste segregation at source and establishing Barangay Materials Recovery Facilities across San Jose Del Monte.', 'Approved', NULL, 0, NULL, 0, 'Yes', 'admin2', '2026-08-17 09:37:22', '2026-08-17 01:37:22', 'Approved', '2026-08-17 09:50:44', 'admin2', NULL, NULL, 'Yes', '2026-08-17 09:50:48', 'Yes', 'IA-20260817-9599', 'Yes', '2026-08-17 09:50:48'),
(2, 'DS-8151', 'POL-20260817-002', 'Local Public Transport Route Plan & Traffic Strategy', '', 'Local Governance', 'Policy Research', '', '', '', '0', 0, NULL, 'A strategic transport management ordinance regulating tricycle terminals and jeepney routes along Quirino Highway and Tungkong Mangga.', 'Approved', NULL, 0, NULL, 0, 'Yes', 'admin2', '2026-08-17 19:05:32', '2026-08-17 11:05:32', 'Approved', '2026-08-17 19:05:42', 'admin2', NULL, NULL, 'Yes', '2026-08-17 19:05:46', 'Yes', 'IA-20260817-1856', 'Yes', '2026-08-17 19:05:46');

-- 4. Impact Assessments Seed
INSERT IGNORE INTO `impact_assessments` (`id`, `assessment_id`, `policy_title`, `department`, `assessment_period`, `overall_rating`, `assessment_summary`, `implementation_rate`, `beneficiaries`, `budget_utilization`, `status`, `ai_evaluation`, `ai_recommendations`, `created_by`, `created_at`, `updated_at`, `created_date`, `assessment_status`, `updated_date`, `updated_by`, `category`, `impact_rating`, `dataset_id`, `kpi_relevance`, `kpi_sustainability`, `kpi_equity`, `impact_percentage`, `kpi_evaluated`, `kpi_evaluation_date`, `workflow_action`, `submitted_to_benchmark`, `submitted_to_benchmark_date`, `kpi_efficiency`, `kpi_effectiveness`, `submitted_to_benchmark_metric`) VALUES
(1, 'IA-20260817-9599', 'Comprehensive Solid Waste Segregation & MRF Ordinance', 'Policy Research', '2026-Q3', 'Moderate', 'Assessment pending for Comprehensive Solid Waste Segregation & MRF Ordinance', 0, 0, 0, 'Pending', NULL, NULL, 'admin2', '2026-08-17 09:50:48', '2026-08-17 16:17:21', '2026-08-17 09:50:48', 'Submitted to Benchmarking', '2026-08-17 16:17:21', 'admin2', 'Environmental', 'Moderate', 'DS-4265', 85, 70, 80, 78, 'Yes', '2026-08-17 16:16:11', 'Proceed to Benchmarking', 'Yes', '2026-08-17 16:17:21', 75, 80, 'BENCH-20260817-6889'),
(3, 'IA-20260817-9769', 'sample', 'Mayor''s Office', '2026-Q3', 'Moderate', 'Assessment pending for sample', 0, 0, 0, 'Pending', NULL, NULL, 'admin2', '2026-08-17 19:28:57', '2026-08-17 19:28:57', '2026-08-17 19:28:57', 'Pending', NULL, NULL, 'Population', 'Pending', 'DS-874', 0, 0, 0, 0, 'No', NULL, NULL, 'No', NULL, 0, 0, NULL),
(4, 'IA-20260817-8540', 'Local Public Transport Route Plan & Traffic Strategy', 'Policy Research', '2026-Q3', 'Moderate', 'Assessment pending for Local Public Transport Route Plan & Traffic Strategy', 0, 0, 0, 'Pending', NULL, NULL, 'admin2', '2026-08-17 19:43:00', '2026-08-17 19:43:43', '2026-08-17 19:43:00', 'Submitted to Benchmarking', '2026-08-17 19:43:43', 'admin2', 'Local Governance', 'High', 'DS-8151', 100, 100, 92, 98, 'Yes', '2026-08-17 19:43:30', 'Proceed to Benchmarking', 'Yes', '2026-08-17 19:43:43', 100, 100, 'BENCH-20260817-8060');

-- 5. Benchmarking Matrix & Submissions Seed
INSERT IGNORE INTO `benchmarking_matrix` (`id`, `benchmark_id`, `criteria1`, `criteria2`, `criteria3`, `criteria4`, `criteria5`, `criteria6`, `criteria7`, `criteria8`, `criteria9`, `criteria10`, `average_score`, `rating`, `recommendation`, `comments`, `evaluated_by`, `evaluated_date`, `created_at`) VALUES
(1, 'BENCH-20260817-6889', 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9.0, 'Excellent', 'Strongly Recommend for Adoption', 'good job', 'admin2', '2026-08-17 16:18:23', '2026-08-17 08:18:23');

INSERT IGNORE INTO `benchmarking_submissions` (`id`, `benchmark_id`, `assessment_id`, `policy_title`, `department`, `impact_rating`, `impact_percentage`, `combined_content`, `document_count`, `submitted_by`, `submitted_date`, `status`, `created_at`) VALUES
(1, 'BENCH-20260817-6889', 'IA-20260817-9599', 'Comprehensive Solid Waste Segregation & MRF Ordinance', 'Policy Research', 'Moderate', 78, 'Benchmarking Report for Comprehensive Solid Waste Segregation & MRF Ordinance', 3, 'admin2', '2026-08-17 16:17:21', 'Report Generated', '2026-08-17 08:17:21'),
(2, 'BENCH-20260817-8060', 'IA-20260817-8540', 'Local Public Transport Route Plan & Traffic Strategy', 'Policy Research', 'High', 98, 'Benchmarking Report for Local Public Transport Route Plan & Traffic Strategy', 3, 'admin2', '2026-08-17 19:43:43', 'Report Generated', '2026-08-17 11:43:43');

-- 6. Supporting Documents Seed
INSERT IGNORE INTO `supporting_documents` (`id`, `document_id`, `dataset_id`, `title`, `category`, `content`, `generated_date`, `generated_by`) VALUES
(1, 'SD-20260817095048-4070', 'DS-4265', 'Fact-Checking & Legal Validation', 'Fact-Checking & Legal Validation', 'The ordinance aligns with Republic Act No. 9003 and Republic Act No. 7160.', '2026-08-17 09:50:48', 'Gemini AI'),
(2, 'SD-20260817095048-2156', 'DS-4265', 'Sectoral Data & Baseline Metrics', 'Sectoral Data & Baseline Metrics', 'Required municipal datasets include household population counts and daily waste generation rates.', '2026-08-17 09:50:48', 'Gemini AI'),
(3, 'SD-20260817095048-5889', 'DS-4265', 'Implementation & Enforcement Roadmap', 'Implementation & Enforcement Roadmap', 'Departmental responsibilities place CENRO and CSWMB at the helm of policy execution.', '2026-08-17 09:50:48', 'Gemini AI'),
(4, 'SD-20260817190546-8064', 'DS-8151', 'Fact-Checking & Legal Validation', 'Fact-Checking & Legal Validation', 'The Local Public Transport Route Plan complies with RA 7160 and RA 9003.', '2026-08-17 19:05:46', 'Gemini AI'),
(5, 'SD-20260817190546-3838', 'DS-8151', 'Sectoral Data & Baseline Metrics', 'Sectoral Data & Baseline Metrics', 'Municipal dataset captures baseline metrics along Quirino Highway transport corridor.', '2026-08-17 19:05:46', 'Gemini AI'),
(6, 'SD-20260817190546-5080', 'DS-8151', 'Implementation & Enforcement Roadmap', 'Implementation & Enforcement Roadmap', 'Implementation roadmap designates CENRO and Traffic Management Office as lead agencies.', '2026-08-17 19:05:46', 'Gemini AI');

-- 7. Upcoming Deadlines & Reports Seed
INSERT IGNORE INTO `upcoming_deadlines` (`id`, `title`, `category`, `due_date`, `priority`, `status`, `created_by`, `created_at`) VALUES
(1, 'Submit Impact Assessment for Local Health Code', 'Impact Assessment', '2026-08-20', 'high', 'completed', 'admin', '2026-08-17 22:41:08'),
(2, 'Comparative Benchmarking Review with Antipolo City', 'Benchmarking Analysis', '2026-08-23', 'medium', 'pending', 'admin', '2026-08-17 22:41:08'),
(3, 'Legislative Quarterly Policy Report Draft', 'Report Generation', '2026-08-31', 'medium', 'pending', 'admin', '2026-08-17 22:41:08'),
(4, 'Data Ingestion: Q3 Demographic Survey', 'Data Collection', '2026-09-07', 'low', 'pending', 'admin', '2026-08-17 22:41:08');

INSERT IGNORE INTO `reports` (`id`, `report_id`, `report_title`, `report_type`, `policy_category`, `output_format`, `report_description`, `date_from`, `date_to`, `report_status`, `ai_content`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'BENCH-20260817-8060', 'Local Public Transport Route Plan & Traffic Strategy', 'Policy Impact Assessment', 'Policy Research', 'PDF', NULL, NULL, NULL, 'Published', NULL, 'admin', '2026-08-18 20:02:54', '2026-08-18 20:02:54');

SET FOREIGN_KEY_CHECKS = 1;



