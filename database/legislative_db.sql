-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 17, 2026 at 02:54 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `legislative_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user` varchar(100) NOT NULL,
  `action` varchar(255) NOT NULL,
  `document_id` varchar(50) DEFAULT NULL,
  `module` varchar(100) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user`, `action`, `document_id`, `module`, `timestamp`, `is_read`) VALUES
(1, 'admin2', 'User logged in', NULL, 'Authentication', '2026-08-17 07:56:52', 0),
(2, 'admin2', 'User logged in', NULL, 'Authentication', '2026-08-17 08:39:08', 0),
(3, 'admin', 'Created sample policy: Comprehensive Solid Waste Segregation & MRF Ordinance', 'POL-20260817-001', 'Policy Research', '2026-08-17 08:55:59', 1),
(4, 'admin', 'Created sample policy: Local Public Transport Route Plan & Traffic Strategy', 'POL-20260817-002', 'Policy Research', '2026-08-17 08:55:59', 1),
(5, 'admin', 'Created sample policy: Barangay Health Center Free Maintenance Medicine Program', 'POL-20260817-003', 'Policy Research', '2026-08-17 08:55:59', 1),
(6, 'admin2', 'Updated policy: Local Public Transport Route Plan & Traffic Strategy', 'POL-20260817-002', 'Policy Research', '2026-08-17 09:11:07', 1),
(7, 'admin2', 'Generated Legal Citations and Similar Ordinances', 'POL-20260817-001', 'Policy Research', '2026-08-17 09:17:23', 1),
(8, 'admin2', 'Submitted policy for data collection approval', 'POL-20260817-001', 'Data Collection', '2026-08-17 09:37:22', 1),
(9, 'admin2', 'Auto-created Impact Assessment from Dataset', 'DS-4265', 'Impact Assessment', '2026-08-17 09:50:48', 1),
(10, 'admin2', 'Approved Dataset with Gemini AI Analysis and Auto-created Impact Assessment', 'DS-4265', 'Data Collection', '2026-08-17 09:50:48', 1),
(11, 'admin2', 'Viewed supporting document', 'SD-20260817095048-2156', 'Data Collection', '2026-08-17 10:01:07', 1),
(12, 'admin2', 'Viewed supporting document', 'SD-20260817095048-2156', 'Data Collection', '2026-08-17 10:03:07', 1),
(13, 'admin2', 'User logged in', NULL, 'Authentication', '2026-08-17 16:09:05', 0),
(14, 'admin2', 'Viewed supporting document', 'SD-20260817095048-4070', 'Data Collection', '2026-08-17 16:14:22', 1),
(15, 'admin2', 'Viewed supporting document', 'SD-20260817095048-4070', 'Data Collection', '2026-08-17 16:15:09', 1),
(16, 'admin2', 'Submitted to Benchmarking with BENCH-20260817-6889', 'IA-20260817-9599', 'Impact Assessment', '2026-08-17 16:17:21', 1),
(17, 'admin2', 'Viewed supporting document', 'SD-20260817095048-5889', 'Data Collection', '2026-08-17 16:23:28', 1),
(18, 'admin2', 'Viewed supporting document', 'SD-20260817095048-2156', 'Data Collection', '2026-08-17 16:27:22', 1),
(19, 'admin2', 'Viewed supporting document', 'SD-20260817095048-2156', 'Data Collection', '2026-08-17 16:29:42', 1),
(20, 'admin', 'User logged in', NULL, 'Authentication', '2026-08-17 16:51:08', 0),
(21, 'admin2', 'Viewed supporting document', 'SD-20260817095048-4070', 'Data Collection', '2026-08-17 17:48:09', 1),
(22, 'admin2', 'Viewed supporting document', 'SD-20260817095048-4070', 'Data Collection', '2026-08-17 18:16:34', 1),
(23, 'admin2', 'Viewed supporting document', 'SD-20260817095048-4070', 'Data Collection', '2026-08-17 18:18:01', 1),
(24, 'admin2', 'Viewed supporting document', 'SD-20260817095048-4070', 'Data Collection', '2026-08-17 18:18:17', 1),
(25, 'admin2', 'Viewed supporting document', 'SD-20260817095048-4070', 'Data Collection', '2026-08-17 18:19:55', 1),
(26, 'admin2', 'Viewed supporting document', 'SD-20260817095048-4070', 'Data Collection', '2026-08-17 18:20:15', 1),
(27, 'admin2', 'Viewed supporting document', 'SD-20260817095048-2156', 'Data Collection', '2026-08-17 18:20:38', 1),
(28, 'admin2', 'Viewed supporting document', 'SD-20260817095048-4070', 'Data Collection', '2026-08-17 18:33:31', 1),
(29, 'admin2', 'User logged in', NULL, 'Authentication', '2026-08-17 18:49:42', 0),
(30, 'admin2', 'Viewed supporting document', 'SD-20260817095048-4070', 'Data Collection', '2026-08-17 18:57:01', 1),
(31, 'admin2', 'Submitted policy for data collection approval', 'POL-20260817-002', 'Data Collection', '2026-08-17 19:05:32', 1),
(32, 'admin2', 'Auto-created Impact Assessment from Dataset', 'DS-8151', 'Impact Assessment', '2026-08-17 19:05:46', 1),
(33, 'admin2', 'Approved Dataset with Gemini AI Analysis and Auto-created Impact Assessment', 'DS-8151', 'Data Collection', '2026-08-17 19:05:46', 1),
(34, 'admin2', 'Viewed supporting document', 'SD-20260817190546-8064', 'Data Collection', '2026-08-17 19:06:19', 1),
(35, 'admin2', 'Viewed supporting document', 'SD-20260817095048-4070', 'Data Collection', '2026-08-17 19:11:21', 1),
(36, 'admin2', 'Auto-created Impact Assessment from Dataset', 'DS-874', 'Impact Assessment', '2026-08-17 19:28:57', 1),
(37, 'admin2', 'Viewed supporting document', 'SD-20260817192857-2853', 'Data Collection', '2026-08-17 19:29:07', 1),
(38, 'admin2', 'KPI Evaluation completed. Impact: 50% (Low) - Archived', 'IA-20260817-1856', 'Impact Assessment', '2026-08-17 19:40:32', 1),
(39, 'admin2', 'KPI Evaluation completed. Impact: 98% (High) - Proceed to Benchmarking', 'IA-20260817-8540', 'Impact Assessment', '2026-08-17 19:43:30', 1),
(40, 'admin2', 'Submitted to Benchmarking with BENCH-20260817-8060', 'IA-20260817-8540', 'Impact Assessment', '2026-08-17 19:43:43', 1);

-- --------------------------------------------------------

--
-- Table structure for table `ai_cache`
--

CREATE TABLE `ai_cache` (
  `cache_id` int(11) NOT NULL,
  `prompt_hash` varchar(32) NOT NULL,
  `response_text` longtext NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_cache`
--

INSERT INTO `ai_cache` (`cache_id`, `prompt_hash`, `response_text`, `created_at`) VALUES
(1, '97d3c77f9c63dfbbf92e014b54ebff6a', '**RELEVANT LAWS AND ORDINANCES (3 citations):**\n1. Republic Act No. 7160 (1991)\n   - Key provisions: It grants local government units autonomy, decentralization of powers, and broader responsibilities for local fiscal management and public service delivery.\n   - Relevance to Local Governance: It serves as the primary legal framework empowering the City of San Jose Del Monte to formulate local policies and administer its internal affairs.\n\n2. City Ordinance No. [Series of Local Governance Code of San Jose Del Monte]\n   - Key provisions: It establishes the structural organization, administrative procedures, and legislative processes specific to the city government units.\n   - Relevance to Local Governance: It operationalizes national decentralization mandates to ensure efficient bureaucratic functions and responsive local legislation within the city.\n\n3. Republic Act No. 6713 (1989)\n   - Key provisions: It sets the code of conduct and ethical standards for public officials and employees, emphasizing accountability, transparency, and public service.\n   - Relevance to Local Governance: It binds all local officials and personnel in San Jose Del Monte to uphold integrity and public trust in local governance administration.', '2026-08-17 09:11:00'),
(2, '9d84f5ab314bec6e9fd317f764c86ae7', '# LEGAL RESEARCH REPORT\n\n**TO:** Policy Review Committee / Local Legislative Body  \n**FROM:** Legal Research Assistant, Philippine Laws and San Jose Del Monte (SJDM) Local Ordinances  \n**SUBJECT:** Comprehensive Legal Research Report on the \"Comprehensive Solid Waste Segregation & MRF Ordinance\" of San Jose Del Monte, Bulacan  \n**DATE:** October 24, 2023  \n\n---\n\n## 1. PHILIPPINE LEGAL CITATIONS\n\n### Republic Acts (RA)\n- **RA No. 9003 - Ecological Solid Waste Management Act of 2000**\n  - *Key provisions:* \n    - Section 21: Mandatory segregation of solid waste at source (shall be practiced at the household, institutional, industrial, and commercial sources: biodegradable, recyclable, non-recyclable, and special hazardous waste).\n    - Section 32: Establishment of Materials Recovery Facility (MRF) in every barangay or cluster of barangays.\n    - Section 48: Prohibited acts and corresponding penalties (e.g., littering, throwing, dumping of solid waste matters in public places, open dumpsites, and waterways).\n    - Section 10: Role of Local Government Units (LGUs) in solid waste management.\n  - *Relevance to policy:* This is the primary national statute governing the proposed ordinance. The policy directly adopts its mandates on source segregation and mandatory barangay MRFs.\n\n- **RA No. 7160 - The Local Government Code of 1991**\n  - *Key provisions:* \n    - Section 16 (General Welfare Clause): LGUs shall exercise powers necessary, appropriate, or incidental for efficient and effective governance, and those which promote the general welfare, enhance public safety, and maintain ecological balance.\n    - Section 17: Basic services and facilities, including solid waste disposal system and environmental management services.\n    - Section 447 / 458: Powers, duties, and functions of the Sangguniang Panlungsod to enact ordinances for the general welfare and enforcement of environmental protection.\n  - *Relevance to policy:* Provides the corporate and police powers of the City of San Jose Del Monte to enact local ordinances regulating waste management and imposing penal sanctions.\n\n- **RA No. 9275 - Philippine Clean Water Act of 2004**\n  - *Key provisions:* \n    - Section 27: Prohibited acts, including the dumping of waste directly into water bodies, waterways, or drainage systems.\n  - *Relevance to policy:* Directly addresses the policy issue of illegal dumping along waterways within SJDM.\n\n### Presidential Decrees (PD)\n- **PD No. 1152 - Philippine Environment Code**\n  - *Key provisions:* \n    - Title VI (Management of Land-Based Pollution), Sections 45-53: Establishes policies on solid waste management, safe disposal, and prohibition of indiscriminate dumping.\n  - *Relevance to policy:* Serves as a foundational environmental framework recognizing the state\'s policy to maintain a clean environment and manage waste efficiently.\n\n### Executive Orders (EO)\n- **EO No. 774, series of 2008**\n  - *Key provisions:* \n    - Directs the reorganization of the National Solid Waste Management Commission (NSWMC) and streamlines government action against climate change and solid waste mismanagement.\n  - *Relevance to policy:* Emphasizes national government-LGU coordination in implementing localized solid waste reduction targets.\n\n### Administrative Orders (AO)\n- **DENR Administrative Order (DAO) No. 2001-34**\n  - *Key provisions:* \n    - Implementing Rules and Regulations (IRR) of Republic Act No. 9003. Specifically details the standards for MRF design, operation, waste diversion calculations, and enforcement mechanisms.\n  - *Relevance to policy:* Provides technical standards required for establishing 100% barangay MRFs in San Jose Del Monte.\n\n### Other Relevant Legal Issuances\n- **NSWMC Resolution No. 138, series of 2015:** Adopting the National Solid Waste Management Strategy (2012-2016) which pushes for waste diversion goals.\n- **DILG Memorandum Circular No. 2018-112:** Strict implementation of RA 9003, mandating local chief executives to ensure barangay compliance regarding waste segregation and MRF establishment.\n\n---\n\n## 2. SAN JOSE DEL MONTE, BULACAN ORDINANCES\n\n*(Note: City-specific ordinances of San Jose Del Monte, Bulacan, operate in alignment with provincial frameworks and national environmental statutes. The following categories reflect the localized legislative structure.)*\n\n### City Ordinances\n- **Ordinance No. [Insert Local Ordinance Number, e.g., 2018-045] - Environmental Code / Solid Waste Management Ordinance of the City of San Jose Del Monte**\n  - *Date approved:* [Subject to official legislative records; typically updated post-RA 9003 adoption]\n  - *Key provisions:* \n    - Establishes local collection schedules, penalties for indiscriminate dumping, and guidelines for barangay environmental police.\n  - *Relevance to policy:* Serves as the pre-existing baseline ordinance that the new \"Comprehensive Solid Waste Segregation & MRF Ordinance\" aims to amend, strengthen, or supplement.\n  - *Relationship to national laws:* Directly operationalizes RA 9003 at the city level, translating national waste diversion goals into local enforcement mechanisms.\n\n### City Resolutions\n- **Resolution No. [Insert Resolution Number] - Resolution Strongly Supporting the Establishment of Barangay Materials Recovery Facilities (MRFs) Across All Barangays in San Jose Del Monte**\n  - *Date approved:* [Subject to official legislative records]\n  - *Key provisions:* \n    - Urges the 59 barangays of SJDM to allocate land space and operational budgets for standard MRFs pursuant to RA 9003.\n  - *Relevance to policy:* Directly supports the objective of achieving 100% barangay MRF coverage.\n\n### Local Executive Orders\n- **Executive Order No. [Insert EO Number] - Reconstituting the City Solid Waste Management Board (CSWMB) of San Jose Del Monte, Bulacan**\n  - *Date approved:* [Subject to official legislative records]\n  - *Key provisions:* \n    - Enumerates members, duties, and operational strategies of the CSWMB in monitoring city-wide waste management, overseeing barangay MRF operations, and reporting to the DENR-EMB.\n  - *Relevance to policy:* Provides the administrative machinery required to monitor the 40% landfilled waste reduction target.\n\n---\n\n## 3. LEGAL FRAMEWORK MAPPING\n\n### Hierarchy of Laws\n1. **Constitution of the Philippines:** Section 16, Article II (The State shall protect and advance the right of the people to a balanced and healthful ecology...).\n2. **Republic Acts (National Laws):** RA 9003, RA 7160, RA 9275.\n3. **Executive Orders / Administrative Orders:** DAO 2001-34, DILG MCs.\n4. **Local Government Code (RA 7160) provisions:** Sections 16, 17, and 458.\n5. **San Jose Del Monte City Ordinances:** City Environmental Code and Supplemental MRF/Segregation Ordinances.\n6. **Implementing Rules and Regulations (IRR) / Barangay Resolutions:** Operational guidelines issued by the CSWMB.\n\n### Applicable Legal Principles\n- **Principle of Local Autonomy:** LGUs possess fiscal and administrative independence under RA 7160 to formulate local environmental solutions tailored to their rapid urbanization challenges (e.g., SJDM’s population density and housing subdivisions).\n- **\"Polluter Pays\" Principle:** Embedded in RA 9003 and operationalized via fines for non-segregation and illegal dumping.\n- **Police Power:** Exercised by the City to restrict individual property or behavior rights for the promotion of public health, safety, and general welfare.\n- **Constitutional Right to Health and a Balanced Ecology:** Section 16, Article II serves as the overriding interpretive lens for strict environmental enforcement.\n\n### Policy Issues Analysis\n- **High volume of unsegregated solid waste:** Addressed nationally by RA 9003 Sec. 21; locally addressed by tightening collection protocols (no segregation, no collection policy).\n- **Low recycling rate:** Addressed by mandating the establishment of MRFs to process recyclables before they reach final disposal sites.\n- **Illegal dumping along waterways:** Addressed by combining RA 9003 Sec. 48 and RA 9275 Sec. 27, reinforced by local penal provisions, deployment of barangay environmental enforcers, and installation of surveillance systems near vulnerable rivers/creeks.\n- **Legal Gaps Identified:** Lax barangay enforcement, lack of space or funding for MRFs in densely populated urban barangays of SJDM, and inadequate tracking mechanisms for waste diversion percentages.\n\n### Policy Objectives Alignment\n- **Reduce landfilled waste by 40%:** Aligns directly with Section 20 of RA 9003 (Waste Diversion goals). Requires strict segregation at source to make recycling and composting viable.\n- **Establish 100% barangay MRFs:** Fulfills Section 32 of RA 9003 and DILG mandates. Requires city-to-barangay financial and logistical support.\n- **Enforce fines for non-segregation:** Aligned with LGC penal limitation clauses (RA 7160 permits cities to impose fines up to prescribed limits for ordinance violations) and RA 9003 penalty structures.\n\n---\n\n## 4. RECOMMENDATIONS\n\n### Implementation Recommendations\n1. **Procedural Requirements:** \n   - Draft the ordinance ensuring penalty structures comply with Section 18 of RA 7160 (limits on fines imposed by city ordinances).\n   - Coordinate with the City Legal Office and City Environment and Natural Resources Office (CENRO).\n2. **Approval Process Needed:** \n   - Submission as a **City Ordinance** enacted by the Sangguniang Panlungsod, subject to review by the Sangguniang Panlalawigan of Bulacan.\n3. **Required Consultations or Hearings:** \n   - Conduct mandatory public consultations/hearings inviting Barangay Captains, Homeowners\' Associations (HOAs)—which are prolific in SJDM—market vendors, junk shop operators, and civil society organizations (CSOs) in compliance with RA 7160 (Local Government Code provisions on public hearings for local legislation).\n\n### Monitoring and Evaluation\n1. **Key Legal Compliance Indicators:**\n   - Percentage of barangays with fully operational MRFs (Target: 100%).\n   - Volume (in tons) of diverted waste vs. landfilled waste (Target: 40% reduction).\n   - Number of issued citation tickets and collected fines for non-segregation and illegal dumping.\n2. **Reporting Requirements:**\n   - Barangay MRFs must submit monthly waste audit reports to the City Environment and Natural Resources Office (CENRO).\n   - CENRO must submit quarterly progress reports to the City Solid Waste Management Board (CSWMB) and the Environmental Management Bureau (EMB - Region III).\n3. **Review Mechanisms:**\n   - Annual review conducted by the CSWMB to assess the effectiveness of penalty enforcement and adjust diversion strategies accordingly.', '2026-08-17 09:17:23'),
(3, '41aca38525ca5725c08488b7bd5c71bf', '{\n  \"fact_check_validation\": \"The ordinance aligns with Republic Act No. 9003 (Ecological Solid Waste Management Act of 2000) and Republic Act No. 7160 (Local Government Code of 1991). Data accuracy checks confirm mandatory household segregation at source and the establishment of Barangay Materials Recovery Facilities (MRFs) within San Jose Del Monte, Bulacan. Legal validation verifies that local penal provisions and administrative fines comply with DILG and DENR-EMB joint memorandum circulars for municipal environmental ordinances.\",\n  \"sectoral_data_baseline\": \"Required municipal datasets include household population counts, daily waste generation rates per capita (categorized into biodegradable, recyclable, residual, and special wastes), and geographical mapping of all 59 barangays in San Jose Del Monte. Baseline metrics track total tonnage diverted from landfills, operational status of Barangay MRFs, and municipal hauling efficiency. Data collection sources rely on CENRO field audits, barangay environmental desk reports, and waste characterization and quantification studies (WCQS).\",\n  \"implementation_enforcement_roadmap\": \"Departmental responsibilities place the City Environment and Natural Resources Office (CENRO) and the City Solid Waste Management Board (CSWMB) at the helm of policy execution, supported by Barangay Captains and Eco-Aides. The enforcement timeline spans a 3-month information and education campaign, followed by a 6-month phased rollout of Barangay MRFs, and full penal enforcement by Q4. Evaluation criteria include a minimum 40 percent waste diversion rate within the first year, monthly barangay compliance reporting, and quarterly environmental audits conducted by the CSWMB.\"\n}', '2026-08-17 09:50:48'),
(4, '1edb7a06b38933f8bd4de5bbe7efb21a', '{\n  \"fact_check_validation\": \"The Local Public Transport Route Plan and Traffic Strategy for San Jose Del Monte, Bulacan complies with Republic Act No. 7160, also known as the Local Government Code of 1991, which grants local government units the authority to regulate the use of thoroughfares and establish franchise zones for tricycles. Alignment with Republic Act No. 9003 (Ecological Solid Waste Management Act) is maintained by prohibiting commuter terminal operations that generate unmanaged vehicular waste along Quirino Highway and Tungkong Mangga. Data accuracy checks confirm that designated loading and unloading bays align with the Department of Transportation and Land Transportation Franchising and Regulatory Board joint memorandum circulars on local public transport route plans.\",\n  \"sectoral_data_baseline\": \"The municipal dataset captures critical baseline metrics along the Quirino Highway and Tungkong Mangga commercial corridors. Key datasets include an inventory of 1,450 registered motorized tricycles, 320 operational public utility jeepneys, and peak-hour passenger volume metrics averaging 12,000 commuters per hour. Traffic velocity tracking indicates an average speed reduction of 45 percent during peak morning hours due to roadside bottlenecks. Collection sources comprise primary traffic count surveys conducted by the City Traffic Management Office, LTFRB franchise registries, and barangay transport bureau logs.\",\n  \"implementation_enforcement_roadmap\": \"The implementation and enforcement roadmap designates the City Environment and Natural Resources Office (CENRO) and the City Solid Waste Management Board (CSWMB) as lead agencies alongside the local Traffic Management Office. The enforcement timeline spans three phases: Phase 1 (Months 1-2) involves stakeholder consultations and public signage installations; Phase 2 (Months 3-5) executes the clearing of illegal terminals along Quirino Highway; Phase 3 (Month 6 onward) establishes full operational enforcement and daily monitoring. Evaluation criteria focus on achieving a 30 percent reduction in corridor travel time, zero tolerance for illegal roadside loading bays, and monthly compliance audits reported directly to the Office of the City Mayor.\"\n}', '2026-08-17 19:05:46'),
(5, '4f03a44b5fc2109e89a6ad06513c87b7', '{\n  \"fact_check_validation\": \"Compliance with Republic Act No. 9003 (Ecological Solid Waste Management Act of 2000) and Republic Act No. 7160 (Local Government Code of 1991) is strictly validated for the City of San Jose Del Monte, Bulacan. Data accuracy checks cross-reference Mayor\'s Office baseline population metrics against projected waste generation coefficients to ensure regulatory alignment and legal enforceability.\",\n  \"sectoral_data_baseline\": \"Municipal datasets focus on San Jose Del Monte population demographics, housing density, and per capita municipal solid waste generation. Baseline metrics are derived from Mayor\'s Office administrative records and localized barangay profiling, establishing the quantitative foundation for urban sanitation planning and resource allocation.\",\n  \"implementation_enforcement_roadmap\": \"Operational execution is led by the City Environment and Natural Resources Office (CENRO) and the City Solid Waste Management Board (CSWMB). The enforcement timeline spans immediate baseline integration, mid-term monitoring, and annual policy evaluation, measured against specific waste diversion targets, compliance rates, and community audit metrics.\"\n}', '2026-08-17 19:28:57');

-- --------------------------------------------------------

--
-- Table structure for table `benchmarking_matrix`
--

CREATE TABLE `benchmarking_matrix` (
  `id` int(11) NOT NULL,
  `benchmark_id` varchar(50) NOT NULL,
  `criteria1` int(11) DEFAULT 0,
  `criteria2` int(11) DEFAULT 0,
  `criteria3` int(11) DEFAULT 0,
  `criteria4` int(11) DEFAULT 0,
  `criteria5` int(11) DEFAULT 0,
  `criteria6` int(11) DEFAULT 0,
  `criteria7` int(11) DEFAULT 0,
  `criteria8` int(11) DEFAULT 0,
  `criteria9` int(11) DEFAULT 0,
  `criteria10` int(11) DEFAULT 0,
  `average_score` decimal(3,1) DEFAULT 0.0,
  `rating` varchar(20) DEFAULT NULL,
  `recommendation` varchar(50) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `evaluated_by` varchar(100) DEFAULT NULL,
  `evaluated_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `benchmarking_matrix`
--

INSERT INTO `benchmarking_matrix` (`id`, `benchmark_id`, `criteria1`, `criteria2`, `criteria3`, `criteria4`, `criteria5`, `criteria6`, `criteria7`, `criteria8`, `criteria9`, `criteria10`, `average_score`, `rating`, `recommendation`, `comments`, `evaluated_by`, `evaluated_date`, `created_at`) VALUES
(1, 'BENCH-20260817-6889', 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9.0, 'Excellent', 'Strongly Recommend for Adoption', 'good job', 'admin2', '2026-08-17 16:18:23', '2026-08-17 08:18:23');

-- --------------------------------------------------------

--
-- Table structure for table `benchmarking_submissions`
--

CREATE TABLE `benchmarking_submissions` (
  `id` int(11) NOT NULL,
  `benchmark_id` varchar(50) NOT NULL,
  `assessment_id` varchar(50) NOT NULL,
  `policy_title` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `impact_rating` varchar(20) DEFAULT NULL,
  `impact_percentage` int(11) DEFAULT NULL,
  `combined_content` longtext DEFAULT NULL,
  `document_count` int(11) DEFAULT 0,
  `submitted_by` varchar(100) DEFAULT NULL,
  `submitted_date` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending Comparison',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `benchmarking_submissions`
--

INSERT INTO `benchmarking_submissions` (`id`, `benchmark_id`, `assessment_id`, `policy_title`, `department`, `impact_rating`, `impact_percentage`, `combined_content`, `document_count`, `submitted_by`, `submitted_date`, `status`, `created_at`) VALUES
(1, 'BENCH-20260817-6889', 'IA-20260817-9599', 'Comprehensive Solid Waste Segregation & MRF Ordinance', 'Policy Research', 'Moderate', 78, '=== BENCHMARKING AND COMPARATIVE ANALYSIS REPORT ===\n\nBenchmark ID: BENCH-20260817-6889\nGenerated: August 17, 2026 04:17 PM\nAssessment ID: IA-20260817-9599\nPolicy Title: Comprehensive Solid Waste Segregation & MRF Ordinance\nDepartment: Policy Research\nImpact Rating: Moderate\nImpact Percentage: 78%\n\n=== KPI SCORES ===\nEffectiveness: 80%\nEfficiency: 75%\nRelevance: 85%\nSustainability: 70%\nEquity: 80%\n\n=== PERFORMANCE METRICS ===\nImplementation Rate: 0%\nBudget Utilization: 0%\nBeneficiaries: 0\n\n=== ASSESSMENT SUMMARY ===\nAssessment pending for Comprehensive Solid Waste Segregation & MRF Ordinance\n\n=== LEGAL DOCUMENTS ===\n\n--- Document 1: Fact-Checking & Legal Validation ---\nDocument ID: SD-20260817095048-4070\nGenerated By: Gemini AI\nGenerated Date: August 17, 2026\nThe ordinance aligns with Republic Act No. 9003 (Ecological Solid Waste Management Act of 2000) and Republic Act No. 7160 (Local Government Code of 1991). Data accuracy checks confirm mandatory household segregation at source and the establishment of Barangay Materials Recovery Facilities (MRFs) within San Jose Del Monte, Bulacan. Legal validation verifies that local penal provisions and administrative fines comply with DILG and DENR-EMB joint memorandum circulars for municipal environmental ordinances.\n--------------------------------------------------------------------------------\n\n--- Document 2: Sectoral Data & Baseline Metrics ---\nDocument ID: SD-20260817095048-2156\nGenerated By: Gemini AI\nGenerated Date: August 17, 2026\nRequired municipal datasets include household population counts, daily waste generation rates per capita (categorized into biodegradable, recyclable, residual, and special wastes), and geographical mapping of all 59 barangays in San Jose Del Monte. Baseline metrics track total tonnage diverted from landfills, operational status of Barangay MRFs, and municipal hauling efficiency. Data collection sources rely on CENRO field audits, barangay environmental desk reports, and waste characterization and quantification studies (WCQS).\n--------------------------------------------------------------------------------\n\n--- Document 3: Implementation & Enforcement Roadmap ---\nDocument ID: SD-20260817095048-5889\nGenerated By: Gemini AI\nGenerated Date: August 17, 2026\nDepartmental responsibilities place the City Environment and Natural Resources Office (CENRO) and the City Solid Waste Management Board (CSWMB) at the helm of policy execution, supported by Barangay Captains and Eco-Aides. The enforcement timeline spans a 3-month information and education campaign, followed by a 6-month phased rollout of Barangay MRFs, and full penal enforcement by Q4. Evaluation criteria include a minimum 40 percent waste diversion rate within the first year, monthly barangay compliance reporting, and quarterly environmental audits conducted by the CSWMB.\n--------------------------------------------------------------------------------\n', 3, 'admin2', '2026-08-17 16:17:21', 'Report Generated', '2026-08-17 08:17:21'),
(2, 'BENCH-20260817-8060', 'IA-20260817-8540', 'Local Public Transport Route Plan & Traffic Strategy', 'Policy Research', 'High', 98, '=== BENCHMARKING AND COMPARATIVE ANALYSIS REPORT ===\n\nBenchmark ID: BENCH-20260817-8060\nGenerated: August 17, 2026 07:43 PM\nAssessment ID: IA-20260817-8540\nPolicy Title: Local Public Transport Route Plan & Traffic Strategy\nDepartment: Policy Research\nImpact Rating: High\nImpact Percentage: 98%\n\n=== KPI SCORES ===\nEffectiveness: 100%\nEfficiency: 100%\nRelevance: 100%\nSustainability: 100%\nEquity: 92%\n\n=== PERFORMANCE METRICS ===\nImplementation Rate: 0%\nBudget Utilization: 0%\nBeneficiaries: 0\n\n=== ASSESSMENT SUMMARY ===\nAssessment pending for Local Public Transport Route Plan & Traffic Strategy\n\n=== LEGAL DOCUMENTS ===\n\n--- Document 1: Fact-Checking & Legal Validation ---\nDocument ID: SD-20260817190546-8064\nGenerated By: Gemini AI\nGenerated Date: August 17, 2026\nThe Local Public Transport Route Plan and Traffic Strategy for San Jose Del Monte, Bulacan complies with Republic Act No. 7160, also known as the Local Government Code of 1991, which grants local government units the authority to regulate the use of thoroughfares and establish franchise zones for tricycles. Alignment with Republic Act No. 9003 (Ecological Solid Waste Management Act) is maintained by prohibiting commuter terminal operations that generate unmanaged vehicular waste along Quirino Highway and Tungkong Mangga. Data accuracy checks confirm that designated loading and unloading bays align with the Department of Transportation and Land Transportation Franchising and Regulatory Board joint memorandum circulars on local public transport route plans.\n--------------------------------------------------------------------------------\n\n--- Document 2: Sectoral Data & Baseline Metrics ---\nDocument ID: SD-20260817190546-3838\nGenerated By: Gemini AI\nGenerated Date: August 17, 2026\nThe municipal dataset captures critical baseline metrics along the Quirino Highway and Tungkong Mangga commercial corridors. Key datasets include an inventory of 1,450 registered motorized tricycles, 320 operational public utility jeepneys, and peak-hour passenger volume metrics averaging 12,000 commuters per hour. Traffic velocity tracking indicates an average speed reduction of 45 percent during peak morning hours due to roadside bottlenecks. Collection sources comprise primary traffic count surveys conducted by the City Traffic Management Office, LTFRB franchise registries, and barangay transport bureau logs.\n--------------------------------------------------------------------------------\n\n--- Document 3: Implementation & Enforcement Roadmap ---\nDocument ID: SD-20260817190546-5080\nGenerated By: Gemini AI\nGenerated Date: August 17, 2026\nThe implementation and enforcement roadmap designates the City Environment and Natural Resources Office (CENRO) and the City Solid Waste Management Board (CSWMB) as lead agencies alongside the local Traffic Management Office. The enforcement timeline spans three phases: Phase 1 (Months 1-2) involves stakeholder consultations and public signage installations; Phase 2 (Months 3-5) executes the clearing of illegal terminals along Quirino Highway; Phase 3 (Month 6 onward) establishes full operational enforcement and daily monitoring. Evaluation criteria focus on achieving a 30 percent reduction in corridor travel time, zero tolerance for illegal roadside loading bays, and monthly compliance audits reported directly to the Office of the City Mayor.\n--------------------------------------------------------------------------------\n', 3, 'admin2', '2026-08-17 19:43:43', 'Report Generated', '2026-08-17 11:43:43');

-- --------------------------------------------------------

--
-- Table structure for table `benchmark_comparisons`
--

CREATE TABLE `benchmark_comparisons` (
  `id` int(11) NOT NULL,
  `comparison_id` varchar(20) NOT NULL,
  `policy_a` varchar(255) NOT NULL,
  `policy_b` varchar(255) NOT NULL,
  `lgu_a` varchar(100) NOT NULL,
  `lgu_b` varchar(100) NOT NULL,
  `similarity_score` decimal(5,2) NOT NULL,
  `key_differences` text DEFAULT NULL,
  `best_practices` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `comparison_method` varchar(50) DEFAULT 'Manual Analysis',
  `status` enum('Completed','Pending') DEFAULT 'Completed',
  `analyzed_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `benchmark_stats`
-- (See below for the actual view)
--
CREATE TABLE `benchmark_stats` (
`total_comparisons` bigint(21)
,`avg_similarity` decimal(9,6)
,`best_practices` decimal(22,0)
,`ai_analyses` decimal(22,0)
,`total_policies` bigint(22)
,`completed` decimal(22,0)
,`pending` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `dashboard_stats`
-- (See below for the actual view)
--
CREATE TABLE `dashboard_stats` (
`total_policies` bigint(21)
,`approved_policies` bigint(21)
,`pending_policies` bigint(21)
,`archived_policies` bigint(21)
,`total_assessments` bigint(21)
,`completed_assessments` bigint(21)
,`high_impact` bigint(21)
,`ai_analyses` bigint(21)
,`total_keywords` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `datasets`
--

CREATE TABLE `datasets` (
  `id` int(11) NOT NULL,
  `dataset_id` varchar(20) NOT NULL,
  `doc_id` varchar(50) DEFAULT NULL,
  `dataset_name` varchar(255) NOT NULL,
  `dataset_category` varchar(100) DEFAULT '',
  `category` varchar(100) DEFAULT NULL,
  `source_office` varchar(100) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` varchar(50) DEFAULT NULL,
  `record_count` int(11) DEFAULT 0,
  `data_period` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Validated','Pending','Needs Review','Approved','Rejected') DEFAULT 'Pending',
  `validation_notes` text DEFAULT NULL,
  `ai_analyzed` tinyint(1) DEFAULT 0,
  `ai_summary` text DEFAULT NULL,
  `validation_completed` tinyint(1) DEFAULT 0,
  `ai_processed` enum('Yes','No') DEFAULT 'No',
  `uploaded_by` varchar(100) DEFAULT NULL,
  `upload_date` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approval_status` varchar(50) DEFAULT 'Approved',
  `approval_date` datetime DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `rejection_date` datetime DEFAULT NULL,
  `rejected_by` varchar(100) DEFAULT NULL,
  `supporting_docs_generated` enum('Yes','No') DEFAULT 'No',
  `ai_analysis_date` datetime DEFAULT NULL,
  `ready_for_impact_assessment` varchar(3) DEFAULT 'No',
  `impact_assessment_id` varchar(50) DEFAULT NULL,
  `impact_assessment_created` varchar(3) DEFAULT 'No',
  `impact_assessment_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `datasets`
--

INSERT INTO `datasets` (`id`, `dataset_id`, `doc_id`, `dataset_name`, `dataset_category`, `category`, `source_office`, `file_name`, `file_path`, `file_type`, `file_size`, `record_count`, `data_period`, `description`, `status`, `validation_notes`, `ai_analyzed`, `ai_summary`, `validation_completed`, `ai_processed`, `uploaded_by`, `upload_date`, `created_at`, `approval_status`, `approval_date`, `approved_by`, `rejection_date`, `rejected_by`, `supporting_docs_generated`, `ai_analysis_date`, `ready_for_impact_assessment`, `impact_assessment_id`, `impact_assessment_created`, `impact_assessment_date`) VALUES
(1, 'DS-4265', 'POL-20260817-001', 'Comprehensive Solid Waste Segregation & MRF Ordinance', '', 'Environmental', 'Policy Research', '', '', '', '0', 0, NULL, 'An ordinance enforcing mandatory household waste segregation at source and establishing Barangay Materials Recovery Facilities across San Jose Del Monte.', 'Approved', NULL, 0, NULL, 0, 'Yes', 'admin2', '2026-08-17 09:37:22', '2026-08-17 01:37:22', 'Approved', '2026-08-17 09:50:44', 'admin2', NULL, NULL, 'Yes', '2026-08-17 09:50:48', 'Yes', 'IA-20260817-9599', 'Yes', '2026-08-17 09:50:48'),
(2, 'DS-8151', 'POL-20260817-002', 'Local Public Transport Route Plan & Traffic Strategy', '', 'Local Governance', 'Policy Research', '', '', '', '0', 0, NULL, 'A strategic transport management ordinance regulating tricycle terminals and jeepney routes along Quirino Highway and Tungkong Mangga.', 'Approved', NULL, 0, NULL, 0, 'Yes', 'admin2', '2026-08-17 19:05:32', '2026-08-17 11:05:32', 'Approved', '2026-08-17 19:05:42', 'admin2', NULL, NULL, 'Yes', '2026-08-17 19:05:46', 'Yes', 'IA-20260817-1856', 'Yes', '2026-08-17 19:05:46');

-- --------------------------------------------------------

--
-- Stand-in structure for view `dataset_stats`
-- (See below for the actual view)
--
CREATE TABLE `dataset_stats` (
`total_datasets` bigint(21)
,`total_sources` bigint(21)
,`total_categories` bigint(21)
,`validated` decimal(22,0)
,`pending` decimal(22,0)
,`needs_review` decimal(22,0)
,`uploaded_today` decimal(22,0)
,`ai_analyzed` decimal(22,0)
,`validated_files` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `impact_assessments`
--

CREATE TABLE `impact_assessments` (
  `id` int(11) NOT NULL,
  `assessment_id` varchar(20) NOT NULL,
  `policy_title` varchar(255) NOT NULL,
  `department` varchar(100) NOT NULL,
  `assessment_period` varchar(20) NOT NULL,
  `overall_rating` enum('High','Moderate','Low') NOT NULL,
  `assessment_summary` text NOT NULL,
  `implementation_rate` int(11) DEFAULT 0,
  `beneficiaries` int(11) DEFAULT 0,
  `budget_utilization` int(11) DEFAULT 0,
  `status` enum('Pending','Completed') DEFAULT 'Pending',
  `ai_evaluation` text DEFAULT NULL,
  `ai_recommendations` text DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_date` datetime DEFAULT current_timestamp(),
  `assessment_status` varchar(50) DEFAULT 'Pending',
  `updated_date` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `impact_rating` varchar(50) DEFAULT 'Pending',
  `dataset_id` varchar(50) DEFAULT NULL,
  `kpi_relevance` int(11) DEFAULT 0,
  `kpi_sustainability` int(11) DEFAULT 0,
  `kpi_equity` int(11) DEFAULT 0,
  `impact_percentage` int(11) DEFAULT 0,
  `kpi_evaluated` varchar(3) DEFAULT 'No',
  `kpi_evaluation_date` datetime DEFAULT NULL,
  `workflow_action` varchar(50) DEFAULT NULL,
  `submitted_to_benchmark` varchar(3) DEFAULT 'No',
  `submitted_to_benchmark_date` datetime DEFAULT NULL,
  `kpi_efficiency` int(11) DEFAULT 0,
  `kpi_effectiveness` int(11) DEFAULT 0,
  `submitted_to_benchmark_metric` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `impact_assessments`
--

INSERT INTO `impact_assessments` (`id`, `assessment_id`, `policy_title`, `department`, `assessment_period`, `overall_rating`, `assessment_summary`, `implementation_rate`, `beneficiaries`, `budget_utilization`, `status`, `ai_evaluation`, `ai_recommendations`, `created_by`, `created_at`, `updated_at`, `created_date`, `assessment_status`, `updated_date`, `updated_by`, `category`, `impact_rating`, `dataset_id`, `kpi_relevance`, `kpi_sustainability`, `kpi_equity`, `impact_percentage`, `kpi_evaluated`, `kpi_evaluation_date`, `workflow_action`, `submitted_to_benchmark`, `submitted_to_benchmark_date`, `kpi_efficiency`, `kpi_effectiveness`, `submitted_to_benchmark_metric`) VALUES
(1, 'IA-20260817-9599', 'Comprehensive Solid Waste Segregation & MRF Ordinance', 'Policy Research', '2026-Q3', 'Moderate', 'Assessment pending for Comprehensive Solid Waste Segregation & MRF Ordinance', 0, 0, 0, 'Pending', NULL, NULL, 'admin2', '2026-08-17 09:50:48', '2026-08-17 16:17:21', '2026-08-17 09:50:48', 'Submitted to Benchmarking', '2026-08-17 16:17:21', 'admin2', 'Environmental', 'Moderate', 'DS-4265', 85, 70, 80, 78, 'Yes', '2026-08-17 16:16:11', 'Proceed to Benchmarking', 'Yes', '2026-08-17 16:17:21', 75, 80, 'BENCH-20260817-6889'),
(3, 'IA-20260817-9769', 'sample', 'Mayor\'s Office', '2026-Q3', 'Moderate', 'Assessment pending for sample', 0, 0, 0, 'Pending', NULL, NULL, 'admin2', '2026-08-17 19:28:57', '2026-08-17 19:28:57', '2026-08-17 19:28:57', 'Pending', NULL, NULL, 'Population', 'Pending', 'DS-874', 0, 0, 0, 0, 'No', NULL, NULL, 'No', NULL, 0, 0, NULL),
(4, 'IA-20260817-8540', 'Local Public Transport Route Plan & Traffic Strategy', 'Policy Research', '2026-Q3', 'Moderate', 'Assessment pending for Local Public Transport Route Plan & Traffic Strategy', 0, 0, 0, 'Pending', NULL, NULL, 'admin2', '2026-08-17 19:43:00', '2026-08-17 19:43:43', '2026-08-17 19:43:00', 'Submitted to Benchmarking', '2026-08-17 19:43:43', 'admin2', 'Local Governance', 'High', 'DS-8151', 100, 100, 92, 98, 'Yes', '2026-08-17 19:43:30', 'Proceed to Benchmarking', 'Yes', '2026-08-17 19:43:43', 100, 100, 'BENCH-20260817-8060');

-- --------------------------------------------------------

--
-- Table structure for table `policy_documents`
--

CREATE TABLE `policy_documents` (
  `id` int(11) NOT NULL,
  `document_id` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `issues` text DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `researcher` varchar(100) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_content` longtext DEFAULT NULL,
  `status` enum('Draft','Pending','Approved','Archived') DEFAULT 'Pending',
  `upload_date` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ai_processed` enum('Yes','No') DEFAULT 'No',
  `selected_for_analysis` enum('Yes','No') DEFAULT 'No',
  `analysis_notes` text DEFAULT NULL,
  `nlp_keywords` varchar(500) DEFAULT NULL,
  `similar_ordinance` varchar(255) DEFAULT NULL,
  `ai_analysis_result` text DEFAULT NULL,
  `ai_processed_date` datetime DEFAULT NULL,
  `data_collection_status` enum('Not Started','Submitted for Approval','Approved for Collection','Approved','Rejected','Imported') DEFAULT 'Not Started',
  `dataset_id` varchar(20) DEFAULT NULL,
  `data_collection_date` datetime DEFAULT NULL,
  `impact_assessment_status` varchar(50) DEFAULT 'Pending',
  `keywords` text DEFAULT NULL,
  `legal_citations` text DEFAULT NULL,
  `legal_analysis` text DEFAULT NULL,
  `legal_analysis_status` enum('Pending','Completed','Error') DEFAULT 'Pending',
  `legal_analysis_date` datetime DEFAULT NULL,
  `analyzed_by` varchar(100) DEFAULT NULL,
  `legal_summary` text DEFAULT NULL,
  `summary_generated` enum('Yes','No') DEFAULT 'No',
  `summary_date` datetime DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `legal_provisions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `policy_documents`
--

INSERT INTO `policy_documents` (`id`, `document_id`, `title`, `category`, `description`, `issues`, `objectives`, `researcher`, `file_name`, `file_path`, `file_content`, `status`, `upload_date`, `updated_at`, `ai_processed`, `selected_for_analysis`, `analysis_notes`, `nlp_keywords`, `similar_ordinance`, `ai_analysis_result`, `ai_processed_date`, `data_collection_status`, `dataset_id`, `data_collection_date`, `impact_assessment_status`, `keywords`, `legal_citations`, `legal_analysis`, `legal_analysis_status`, `legal_analysis_date`, `analyzed_by`, `legal_summary`, `summary_generated`, `summary_date`, `short_description`, `legal_provisions`) VALUES
(1, 'POL-20260817-001', 'Comprehensive Solid Waste Segregation & MRF Ordinance', 'Environmental', 'An ordinance enforcing mandatory household waste segregation at source and establishing Barangay Materials Recovery Facilities across San Jose Del Monte.', 'High volume of unsegregated solid waste, low recycling rate, illegal dumping along waterways.', 'Reduce landfilled waste by 40%, establish 100% barangay MRFs, enforce fines for non-segregation.', 'admin', NULL, NULL, NULL, 'Pending', '2026-08-17 08:55:59', '2026-08-17 09:50:48', 'No', 'No', NULL, NULL, NULL, NULL, NULL, 'Approved', NULL, NULL, 'Ready for Assessment', 'Waste, Segregation, Comprehensive, Solid, Environmental, Enforcing, Mandatory, Household', 'RA 9003 (Ecological Solid Waste Management Act of 2000), SJDM Ordinance No. 2021-04', '# LEGAL RESEARCH REPORT\n\n**TO:** Policy Review Committee / Local Legislative Body  \n**FROM:** Legal Research Assistant, Philippine Laws and San Jose Del Monte (SJDM) Local Ordinances  \n**SUBJECT:** Comprehensive Legal Research Report on the \"Comprehensive Solid Waste Segregation & MRF Ordinance\" of San Jose Del Monte, Bulacan  \n**DATE:** October 24, 2023  \n\n---\n\n## 1. PHILIPPINE LEGAL CITATIONS\n\n### Republic Acts (RA)\n- **RA No. 9003 - Ecological Solid Waste Management Act of 2000**\n  - *Key provisions:* \n    - Section 21: Mandatory segregation of solid waste at source (shall be practiced at the household, institutional, industrial, and commercial sources: biodegradable, recyclable, non-recyclable, and special hazardous waste).\n    - Section 32: Establishment of Materials Recovery Facility (MRF) in every barangay or cluster of barangays.\n    - Section 48: Prohibited acts and corresponding penalties (e.g., littering, throwing, dumping of solid waste matters in public places, open dumpsites, and waterways).\n    - Section 10: Role of Local Government Units (LGUs) in solid waste management.\n  - *Relevance to policy:* This is the primary national statute governing the proposed ordinance. The policy directly adopts its mandates on source segregation and mandatory barangay MRFs.\n\n- **RA No. 7160 - The Local Government Code of 1991**\n  - *Key provisions:* \n    - Section 16 (General Welfare Clause): LGUs shall exercise powers necessary, appropriate, or incidental for efficient and effective governance, and those which promote the general welfare, enhance public safety, and maintain ecological balance.\n    - Section 17: Basic services and facilities, including solid waste disposal system and environmental management services.\n    - Section 447 / 458: Powers, duties, and functions of the Sangguniang Panlungsod to enact ordinances for the general welfare and enforcement of environmental protection.\n  - *Relevance to policy:* Provides the corporate and police powers of the City of San Jose Del Monte to enact local ordinances regulating waste management and imposing penal sanctions.\n\n- **RA No. 9275 - Philippine Clean Water Act of 2004**\n  - *Key provisions:* \n    - Section 27: Prohibited acts, including the dumping of waste directly into water bodies, waterways, or drainage systems.\n  - *Relevance to policy:* Directly addresses the policy issue of illegal dumping along waterways within SJDM.\n\n### Presidential Decrees (PD)\n- **PD No. 1152 - Philippine Environment Code**\n  - *Key provisions:* \n    - Title VI (Management of Land-Based Pollution), Sections 45-53: Establishes policies on solid waste management, safe disposal, and prohibition of indiscriminate dumping.\n  - *Relevance to policy:* Serves as a foundational environmental framework recognizing the state\'s policy to maintain a clean environment and manage waste efficiently.\n\n### Executive Orders (EO)\n- **EO No. 774, series of 2008**\n  - *Key provisions:* \n    - Directs the reorganization of the National Solid Waste Management Commission (NSWMC) and streamlines government action against climate change and solid waste mismanagement.\n  - *Relevance to policy:* Emphasizes national government-LGU coordination in implementing localized solid waste reduction targets.\n\n### Administrative Orders (AO)\n- **DENR Administrative Order (DAO) No. 2001-34**\n  - *Key provisions:* \n    - Implementing Rules and Regulations (IRR) of Republic Act No. 9003. Specifically details the standards for MRF design, operation, waste diversion calculations, and enforcement mechanisms.\n  - *Relevance to policy:* Provides technical standards required for establishing 100% barangay MRFs in San Jose Del Monte.\n\n### Other Relevant Legal Issuances\n- **NSWMC Resolution No. 138, series of 2015:** Adopting the National Solid Waste Management Strategy (2012-2016) which pushes for waste diversion goals.\n- **DILG Memorandum Circular No. 2018-112:** Strict implementation of RA 9003, mandating local chief executives to ensure barangay compliance regarding waste segregation and MRF establishment.\n\n---\n\n## 2. SAN JOSE DEL MONTE, BULACAN ORDINANCES\n\n*(Note: City-specific ordinances of San Jose Del Monte, Bulacan, operate in alignment with provincial frameworks and national environmental statutes. The following categories reflect the localized legislative structure.)*\n\n### City Ordinances\n- **Ordinance No. [Insert Local Ordinance Number, e.g., 2018-045] - Environmental Code / Solid Waste Management Ordinance of the City of San Jose Del Monte**\n  - *Date approved:* [Subject to official legislative records; typically updated post-RA 9003 adoption]\n  - *Key provisions:* \n    - Establishes local collection schedules, penalties for indiscriminate dumping, and guidelines for barangay environmental police.\n  - *Relevance to policy:* Serves as the pre-existing baseline ordinance that the new \"Comprehensive Solid Waste Segregation & MRF Ordinance\" aims to amend, strengthen, or supplement.\n  - *Relationship to national laws:* Directly operationalizes RA 9003 at the city level, translating national waste diversion goals into local enforcement mechanisms.\n\n### City Resolutions\n- **Resolution No. [Insert Resolution Number] - Resolution Strongly Supporting the Establishment of Barangay Materials Recovery Facilities (MRFs) Across All Barangays in San Jose Del Monte**\n  - *Date approved:* [Subject to official legislative records]\n  - *Key provisions:* \n    - Urges the 59 barangays of SJDM to allocate land space and operational budgets for standard MRFs pursuant to RA 9003.\n  - *Relevance to policy:* Directly supports the objective of achieving 100% barangay MRF coverage.\n\n### Local Executive Orders\n- **Executive Order No. [Insert EO Number] - Reconstituting the City Solid Waste Management Board (CSWMB) of San Jose Del Monte, Bulacan**\n  - *Date approved:* [Subject to official legislative records]\n  - *Key provisions:* \n    - Enumerates members, duties, and operational strategies of the CSWMB in monitoring city-wide waste management, overseeing barangay MRF operations, and reporting to the DENR-EMB.\n  - *Relevance to policy:* Provides the administrative machinery required to monitor the 40% landfilled waste reduction target.\n\n---\n\n## 3. LEGAL FRAMEWORK MAPPING\n\n### Hierarchy of Laws\n1. **Constitution of the Philippines:** Section 16, Article II (The State shall protect and advance the right of the people to a balanced and healthful ecology...).\n2. **Republic Acts (National Laws):** RA 9003, RA 7160, RA 9275.\n3. **Executive Orders / Administrative Orders:** DAO 2001-34, DILG MCs.\n4. **Local Government Code (RA 7160) provisions:** Sections 16, 17, and 458.\n5. **San Jose Del Monte City Ordinances:** City Environmental Code and Supplemental MRF/Segregation Ordinances.\n6. **Implementing Rules and Regulations (IRR) / Barangay Resolutions:** Operational guidelines issued by the CSWMB.\n\n### Applicable Legal Principles\n- **Principle of Local Autonomy:** LGUs possess fiscal and administrative independence under RA 7160 to formulate local environmental solutions tailored to their rapid urbanization challenges (e.g., SJDM’s population density and housing subdivisions).\n- **\"Polluter Pays\" Principle:** Embedded in RA 9003 and operationalized via fines for non-segregation and illegal dumping.\n- **Police Power:** Exercised by the City to restrict individual property or behavior rights for the promotion of public health, safety, and general welfare.\n- **Constitutional Right to Health and a Balanced Ecology:** Section 16, Article II serves as the overriding interpretive lens for strict environmental enforcement.\n\n### Policy Issues Analysis\n- **High volume of unsegregated solid waste:** Addressed nationally by RA 9003 Sec. 21; locally addressed by tightening collection protocols (no segregation, no collection policy).\n- **Low recycling rate:** Addressed by mandating the establishment of MRFs to process recyclables before they reach final disposal sites.\n- **Illegal dumping along waterways:** Addressed by combining RA 9003 Sec. 48 and RA 9275 Sec. 27, reinforced by local penal provisions, deployment of barangay environmental enforcers, and installation of surveillance systems near vulnerable rivers/creeks.\n- **Legal Gaps Identified:** Lax barangay enforcement, lack of space or funding for MRFs in densely populated urban barangays of SJDM, and inadequate tracking mechanisms for waste diversion percentages.\n\n### Policy Objectives Alignment\n- **Reduce landfilled waste by 40%:** Aligns directly with Section 20 of RA 9003 (Waste Diversion goals). Requires strict segregation at source to make recycling and composting viable.\n- **Establish 100% barangay MRFs:** Fulfills Section 32 of RA 9003 and DILG mandates. Requires city-to-barangay financial and logistical support.\n- **Enforce fines for non-segregation:** Aligned with LGC penal limitation clauses (RA 7160 permits cities to impose fines up to prescribed limits for ordinance violations) and RA 9003 penalty structures.\n\n---\n\n## 4. RECOMMENDATIONS\n\n### Implementation Recommendations\n1. **Procedural Requirements:** \n   - Draft the ordinance ensuring penalty structures comply with Section 18 of RA 7160 (limits on fines imposed by city ordinances).\n   - Coordinate with the City Legal Office and City Environment and Natural Resources Office (CENRO).\n2. **Approval Process Needed:** \n   - Submission as a **City Ordinance** enacted by the Sangguniang Panlungsod, subject to review by the Sangguniang Panlalawigan of Bulacan.\n3. **Required Consultations or Hearings:** \n   - Conduct mandatory public consultations/hearings inviting Barangay Captains, Homeowners\' Associations (HOAs)—which are prolific in SJDM—market vendors, junk shop operators, and civil society organizations (CSOs) in compliance with RA 7160 (Local Government Code provisions on public hearings for local legislation).\n\n### Monitoring and Evaluation\n1. **Key Legal Compliance Indicators:**\n   - Percentage of barangays with fully operational MRFs (Target: 100%).\n   - Volume (in tons) of diverted waste vs. landfilled waste (Target: 40% reduction).\n   - Number of issued citation tickets and collected fines for non-segregation and illegal dumping.\n2. **Reporting Requirements:**\n   - Barangay MRFs must submit monthly waste audit reports to the City Environment and Natural Resources Office (CENRO).\n   - CENRO must submit quarterly progress reports to the City Solid Waste Management Board (CSWMB) and the Environmental Management Bureau (EMB - Region III).\n3. **Review Mechanisms:**\n   - Annual review conducted by the CSWMB to assess the effectiveness of penalty enforcement and adjust diversion strategies accordingly.', 'Completed', '2026-08-17 09:17:23', 'admin2', NULL, 'No', NULL, NULL, NULL),
(2, 'POL-20260817-002', 'Local Public Transport Route Plan & Traffic Strategy', 'Local Governance', 'A strategic transport management ordinance regulating tricycle terminals and jeepney routes along Quirino Highway and Tungkong Mangga.', 'Peak hour traffic bottlenecks around Tungko crossing, illegal tricycle terminals blocking public roads.', 'Streamline public transport routes, establish designated passenger loading bays, clear illegal obstructions.', 'admin', NULL, NULL, NULL, 'Pending', '2026-08-17 08:55:59', '2026-08-17 19:05:46', 'No', 'No', NULL, NULL, NULL, NULL, NULL, 'Approved', NULL, NULL, 'Ready for Assessment', 'Traffic Management, Quirino Highway, Transport Route, Tricycle Terminals, SJDM', '**RELEVANT LAWS AND ORDINANCES (3 citations):**\r\n1. Republic Act No. 7160 (1991)\r\n   - Key provisions: It grants local government units autonomy, decentralization of powers, and broader responsibilities for local fiscal management and public service delivery.\r\n   - Relevance to Local Governance: It serves as the primary legal framework empowering the City of San Jose Del Monte to formulate local policies and administer its internal affairs.\r\n\r\n2. City Ordinance No. [Series of Local Governance Code of San Jose Del Monte]\r\n   - Key provisions: It establishes the structural organization, administrative procedures, and legislative processes specific to the city government units.\r\n   - Relevance to Local Governance: It operationalizes national decentralization mandates to ensure efficient bureaucratic functions and responsive local legislation within the city.\r\n\r\n3. Republic Act No. 6713 (1989)\r\n   - Key provisions: It sets the code of conduct and ethical standards for public officials and employees, emphasizing accountability, transparency, and public service.\r\n   - Relevance to Local Governance: It binds all local officials and personnel in San Jose Del Monte to uphold integrity and public trust in local governance administration.', NULL, 'Pending', NULL, NULL, NULL, 'No', NULL, NULL, NULL),
(3, 'POL-20260817-003', 'Barangay Health Center Free Maintenance Medicine Program', 'Public Health', 'An ordinance expanding municipal health center services to provide free maintenance medications for senior citizens and indigent residents.', 'High out-of-pocket medical expenses for indigent seniors, limited access to maintenance medicine in rural barangays.', 'Provide 100% free hypertension and diabetes medication in all 59 barangay health centers.', 'admin', NULL, NULL, NULL, 'Approved', '2026-08-17 08:55:59', '2026-08-17 08:55:59', 'No', 'No', NULL, NULL, NULL, NULL, NULL, 'Not Started', NULL, NULL, 'Pending', 'Public Health, Senior Citizens, Free Medicine, Barangay Health Center, SJDM', 'RA 11223 (Universal Health Care Act), RA 9994 (Expanded Senior Citizens Act)', NULL, 'Pending', NULL, NULL, NULL, 'No', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `policy_keywords`
--

CREATE TABLE `policy_keywords` (
  `id` int(11) NOT NULL,
  `document_id` varchar(20) DEFAULT NULL,
  `keyword` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `report_id` varchar(20) NOT NULL,
  `report_title` varchar(255) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `policy_category` varchar(50) NOT NULL,
  `output_format` varchar(20) NOT NULL,
  `report_description` text DEFAULT NULL,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `report_status` enum('Draft','Pending Review','Published') DEFAULT 'Draft',
  `ai_content` text DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `report_stats`
-- (See below for the actual view)
--
CREATE TABLE `report_stats` (
`total_reports` bigint(21)
,`published` decimal(22,0)
,`draft` decimal(22,0)
,`pending_review` decimal(22,0)
,`pdf_reports` decimal(22,0)
,`word_reports` decimal(22,0)
,`ai_generated` decimal(22,0)
,`last_30_days` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `research_projects`
--

CREATE TABLE `research_projects` (
  `id` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `document_id` varchar(20) DEFAULT NULL,
  `status` enum('Active','Completed','On Hold') DEFAULT 'Active',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `researcher` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supporting_documents`
--

CREATE TABLE `supporting_documents` (
  `id` int(11) NOT NULL,
  `document_id` varchar(50) NOT NULL,
  `dataset_id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `generated_date` datetime DEFAULT NULL,
  `generated_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supporting_documents`
--

INSERT INTO `supporting_documents` (`id`, `document_id`, `dataset_id`, `title`, `category`, `content`, `generated_date`, `generated_by`) VALUES
(1, 'SD-20260817095048-4070', 'DS-4265', 'Fact-Checking & Legal Validation', 'Fact-Checking & Legal Validation', 'The ordinance aligns with Republic Act No. 9003 (Ecological Solid Waste Management Act of 2000) and Republic Act No. 7160 (Local Government Code of 1991). Data accuracy checks confirm mandatory household segregation at source and the establishment of Barangay Materials Recovery Facilities (MRFs) within San Jose Del Monte, Bulacan. Legal validation verifies that local penal provisions and administrative fines comply with DILG and DENR-EMB joint memorandum circulars for municipal environmental ordinances.', '2026-08-17 09:50:48', 'Gemini AI'),
(2, 'SD-20260817095048-2156', 'DS-4265', 'Sectoral Data & Baseline Metrics', 'Sectoral Data & Baseline Metrics', 'Required municipal datasets include household population counts, daily waste generation rates per capita (categorized into biodegradable, recyclable, residual, and special wastes), and geographical mapping of all 59 barangays in San Jose Del Monte. Baseline metrics track total tonnage diverted from landfills, operational status of Barangay MRFs, and municipal hauling efficiency. Data collection sources rely on CENRO field audits, barangay environmental desk reports, and waste characterization and quantification studies (WCQS).', '2026-08-17 09:50:48', 'Gemini AI'),
(3, 'SD-20260817095048-5889', 'DS-4265', 'Implementation & Enforcement Roadmap', 'Implementation & Enforcement Roadmap', 'Departmental responsibilities place the City Environment and Natural Resources Office (CENRO) and the City Solid Waste Management Board (CSWMB) at the helm of policy execution, supported by Barangay Captains and Eco-Aides. The enforcement timeline spans a 3-month information and education campaign, followed by a 6-month phased rollout of Barangay MRFs, and full penal enforcement by Q4. Evaluation criteria include a minimum 40 percent waste diversion rate within the first year, monthly barangay compliance reporting, and quarterly environmental audits conducted by the CSWMB.', '2026-08-17 09:50:48', 'Gemini AI'),
(4, 'SD-20260817190546-8064', 'DS-8151', 'Fact-Checking & Legal Validation', 'Fact-Checking & Legal Validation', 'The Local Public Transport Route Plan and Traffic Strategy for San Jose Del Monte, Bulacan complies with Republic Act No. 7160, also known as the Local Government Code of 1991, which grants local government units the authority to regulate the use of thoroughfares and establish franchise zones for tricycles. Alignment with Republic Act No. 9003 (Ecological Solid Waste Management Act) is maintained by prohibiting commuter terminal operations that generate unmanaged vehicular waste along Quirino Highway and Tungkong Mangga. Data accuracy checks confirm that designated loading and unloading bays align with the Department of Transportation and Land Transportation Franchising and Regulatory Board joint memorandum circulars on local public transport route plans.', '2026-08-17 19:05:46', 'Gemini AI'),
(5, 'SD-20260817190546-3838', 'DS-8151', 'Sectoral Data & Baseline Metrics', 'Sectoral Data & Baseline Metrics', 'The municipal dataset captures critical baseline metrics along the Quirino Highway and Tungkong Mangga commercial corridors. Key datasets include an inventory of 1,450 registered motorized tricycles, 320 operational public utility jeepneys, and peak-hour passenger volume metrics averaging 12,000 commuters per hour. Traffic velocity tracking indicates an average speed reduction of 45 percent during peak morning hours due to roadside bottlenecks. Collection sources comprise primary traffic count surveys conducted by the City Traffic Management Office, LTFRB franchise registries, and barangay transport bureau logs.', '2026-08-17 19:05:46', 'Gemini AI'),
(6, 'SD-20260817190546-5080', 'DS-8151', 'Implementation & Enforcement Roadmap', 'Implementation & Enforcement Roadmap', 'The implementation and enforcement roadmap designates the City Environment and Natural Resources Office (CENRO) and the City Solid Waste Management Board (CSWMB) as lead agencies alongside the local Traffic Management Office. The enforcement timeline spans three phases: Phase 1 (Months 1-2) involves stakeholder consultations and public signage installations; Phase 2 (Months 3-5) executes the clearing of illegal terminals along Quirino Highway; Phase 3 (Month 6 onward) establishes full operational enforcement and daily monitoring. Evaluation criteria focus on achieving a 30 percent reduction in corridor travel time, zero tolerance for illegal roadside loading bays, and monthly compliance audits reported directly to the Office of the City Mayor.', '2026-08-17 19:05:46', 'Gemini AI');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','researcher','viewer') DEFAULT 'viewer',
  `department` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(50) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `department`, `status`, `created_at`, `updated_at`, `created_by`, `last_login`) VALUES
(1, 'admin', '$2y$12$o17wXkZfnI3zb3ICxCCsWuNxQLN1iBQnkPZ21LSNagoTyZzsIRMRC', 'System Administrator', 'admin@sjdm.gov.ph', 'admin', 'Legislative Research Division', 'active', '2026-08-17 07:42:07', '2026-08-17 07:56:24', 'system', NULL),
(2, 'admin2', '$2y$12$o17wXkZfnI3zb3ICxCCsWuNxQLN1iBQnkPZ21LSNagoTyZzsIRMRC', 'Legislative Administrator', 'admin2@sjdm.gov.ph', 'admin', 'Sanggunian Panlungsod', 'active', '2026-08-17 07:53:21', '2026-08-17 07:56:24', 'system', NULL);

-- --------------------------------------------------------

--
-- Structure for view `benchmark_stats`
--
DROP TABLE IF EXISTS `benchmark_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `benchmark_stats`  AS SELECT count(0) AS `total_comparisons`, avg(`benchmark_comparisons`.`similarity_score`) AS `avg_similarity`, sum(case when `benchmark_comparisons`.`similarity_score` >= 90 then 1 else 0 end) AS `best_practices`, sum(case when `benchmark_comparisons`.`comparison_method` = 'AI Similarity Analysis' then 1 else 0 end) AS `ai_analyses`, count(distinct `benchmark_comparisons`.`policy_a`) + count(distinct `benchmark_comparisons`.`policy_b`) AS `total_policies`, sum(case when `benchmark_comparisons`.`status` = 'Completed' then 1 else 0 end) AS `completed`, sum(case when `benchmark_comparisons`.`status` = 'Pending' then 1 else 0 end) AS `pending` FROM `benchmark_comparisons` ;

-- --------------------------------------------------------

--
-- Structure for view `dashboard_stats`
--
DROP TABLE IF EXISTS `dashboard_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `dashboard_stats`  AS SELECT (select count(0) from `policy_documents`) AS `total_policies`, (select count(0) from `policy_documents` where `policy_documents`.`status` = 'Approved') AS `approved_policies`, (select count(0) from `policy_documents` where `policy_documents`.`status` = 'Pending') AS `pending_policies`, (select count(0) from `policy_documents` where `policy_documents`.`status` = 'Archived') AS `archived_policies`, (select count(0) from `impact_assessments`) AS `total_assessments`, (select count(0) from `impact_assessments` where `impact_assessments`.`status` = 'Completed') AS `completed_assessments`, (select count(0) from `impact_assessments` where `impact_assessments`.`overall_rating` = 'High') AS `high_impact`, (select count(0) from `impact_assessments` where `impact_assessments`.`ai_evaluation` is not null) AS `ai_analyses`, (select count(0) from `policy_keywords`) AS `total_keywords` ;

-- --------------------------------------------------------

--
-- Structure for view `dataset_stats`
--
DROP TABLE IF EXISTS `dataset_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `dataset_stats`  AS SELECT count(0) AS `total_datasets`, count(distinct `datasets`.`source_office`) AS `total_sources`, count(distinct `datasets`.`dataset_category`) AS `total_categories`, sum(case when `datasets`.`status` = 'Validated' then 1 else 0 end) AS `validated`, sum(case when `datasets`.`status` = 'Pending' then 1 else 0 end) AS `pending`, sum(case when `datasets`.`status` = 'Needs Review' then 1 else 0 end) AS `needs_review`, sum(case when `datasets`.`upload_date` >= curdate() then 1 else 0 end) AS `uploaded_today`, sum(case when `datasets`.`ai_analyzed` = 1 then 1 else 0 end) AS `ai_analyzed`, sum(case when `datasets`.`validation_completed` = 1 then 1 else 0 end) AS `validated_files` FROM `datasets` ;

-- --------------------------------------------------------

--
-- Structure for view `report_stats`
--
DROP TABLE IF EXISTS `report_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `report_stats`  AS SELECT count(0) AS `total_reports`, sum(case when `reports`.`report_status` = 'Published' then 1 else 0 end) AS `published`, sum(case when `reports`.`report_status` = 'Draft' then 1 else 0 end) AS `draft`, sum(case when `reports`.`report_status` = 'Pending Review' then 1 else 0 end) AS `pending_review`, sum(case when `reports`.`output_format` = 'PDF' then 1 else 0 end) AS `pdf_reports`, sum(case when `reports`.`output_format` = 'Microsoft Word' then 1 else 0 end) AS `word_reports`, sum(case when `reports`.`ai_content` is not null then 1 else 0 end) AS `ai_generated`, sum(case when `reports`.`created_at` >= current_timestamp() - interval 30 day then 1 else 0 end) AS `last_30_days` FROM `reports` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ai_cache`
--
ALTER TABLE `ai_cache`
  ADD PRIMARY KEY (`cache_id`),
  ADD UNIQUE KEY `prompt_hash` (`prompt_hash`),
  ADD KEY `idx_prompt_hash` (`prompt_hash`);

--
-- Indexes for table `benchmarking_matrix`
--
ALTER TABLE `benchmarking_matrix`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `benchmark_id` (`benchmark_id`);

--
-- Indexes for table `benchmarking_submissions`
--
ALTER TABLE `benchmarking_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `benchmark_id` (`benchmark_id`);

--
-- Indexes for table `benchmark_comparisons`
--
ALTER TABLE `benchmark_comparisons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `comparison_id` (`comparison_id`),
  ADD KEY `idx_comparison_id` (`comparison_id`),
  ADD KEY `idx_similarity` (`similarity_score`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `datasets`
--
ALTER TABLE `datasets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dataset_id` (`dataset_id`),
  ADD KEY `idx_dataset_id` (`dataset_id`),
  ADD KEY `idx_source` (`source_office`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`dataset_category`);

--
-- Indexes for table `impact_assessments`
--
ALTER TABLE `impact_assessments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `assessment_id` (`assessment_id`),
  ADD KEY `idx_assessment_id` (`assessment_id`),
  ADD KEY `idx_policy_title` (`policy_title`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_rating` (`overall_rating`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `policy_documents`
--
ALTER TABLE `policy_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_id` (`document_id`),
  ADD KEY `idx_document_id` (`document_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_upload_date` (`upload_date`);

--
-- Indexes for table `policy_keywords`
--
ALTER TABLE `policy_keywords`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_keyword_document` (`document_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_id` (`report_id`),
  ADD KEY `idx_report_id` (`report_id`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_status` (`report_status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_category` (`policy_category`);

--
-- Indexes for table `research_projects`
--
ALTER TABLE `research_projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_id` (`document_id`);

--
-- Indexes for table `supporting_documents`
--
ALTER TABLE `supporting_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_id` (`document_id`),
  ADD KEY `dataset_id` (`dataset_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `ai_cache`
--
ALTER TABLE `ai_cache`
  MODIFY `cache_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `benchmarking_matrix`
--
ALTER TABLE `benchmarking_matrix`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `benchmarking_submissions`
--
ALTER TABLE `benchmarking_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `benchmark_comparisons`
--
ALTER TABLE `benchmark_comparisons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `datasets`
--
ALTER TABLE `datasets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `impact_assessments`
--
ALTER TABLE `impact_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `policy_documents`
--
ALTER TABLE `policy_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `policy_keywords`
--
ALTER TABLE `policy_keywords`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `research_projects`
--
ALTER TABLE `research_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supporting_documents`
--
ALTER TABLE `supporting_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `benchmarking_matrix`
--
ALTER TABLE `benchmarking_matrix`
  ADD CONSTRAINT `benchmarking_matrix_ibfk_1` FOREIGN KEY (`benchmark_id`) REFERENCES `benchmarking_submissions` (`benchmark_id`) ON DELETE CASCADE;

--
-- Constraints for table `policy_keywords`
--
ALTER TABLE `policy_keywords`
  ADD CONSTRAINT `policy_keywords_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `policy_documents` (`document_id`) ON DELETE CASCADE;

--
-- Constraints for table `research_projects`
--
ALTER TABLE `research_projects`
  ADD CONSTRAINT `research_projects_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `policy_documents` (`document_id`) ON DELETE SET NULL;

--
-- Constraints for table `supporting_documents`
--
ALTER TABLE `supporting_documents`
  ADD CONSTRAINT `supporting_documents_ibfk_1` FOREIGN KEY (`dataset_id`) REFERENCES `datasets` (`dataset_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
