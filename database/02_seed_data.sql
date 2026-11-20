USE ai_freelance_platform;

-- Inserting sample data into tables

-- 1. USERS
INSERT INTO users (full_name, email, password_hash, phone, role, account_status) VALUES
('Nimal Perera', 'nimal.perera@aihub.lk', '$2y$10$demo_hash_01', '+94771000001', 'Freelancer', 'Active'),
('Kavindi Silva', 'kavindi.silva@aihub.lk', '$2y$10$demo_hash_02', '+94771000002', 'Freelancer', 'Active'),
('Roshan Fernando', 'roshan.fernando@aihub.lk', '$2y$10$demo_hash_03', '+94771000003', 'Freelancer', 'Active'),
('Tharushi Jayawardena', 'tharushi.j@aihub.lk', '$2y$10$demo_hash_04', '+94771000004', 'Freelancer', 'Active'),
('Dinesh Wickramasinghe', 'dinesh.w@aihub.lk', '$2y$10$demo_hash_05', '+94771000005', 'Freelancer', 'Active'),
('Anushka Senanayake', 'anushka.s@aihub.lk', '$2y$10$demo_hash_06', '+94771000006', 'Freelancer', 'Active'),
('Sachithra Bandara', 'sachithra.b@aihub.lk', '$2y$10$demo_hash_07', '+94771000007', 'Freelancer', 'Active'),
('Dilshan Gunasekara', 'dilshan.g@aihub.lk', '$2y$10$demo_hash_08', '+94771000008', 'Freelancer', 'Active'),
('Malithi Rajapaksha', 'malithi.r@aihub.lk', '$2y$10$demo_hash_09', '+94771000009', 'Freelancer', 'Active'),
('Chamara Dissanayake', 'chamara.d@aihub.lk', '$2y$10$demo_hash_10', '+94771000010', 'Freelancer', 'Active'),
('Lanka FinTech Labs', 'hello@lankafintech.lk', '$2y$10$demo_hash_11', '+94112000011', 'Client', 'Active'),
('Ceylon Retail Group', 'procurement@ceylonretail.lk', '$2y$10$demo_hash_12', '+94112000012', 'Client', 'Active'),
('Helix Health Systems', 'ai@helixhealth.org', '$2y$10$demo_hash_13', '+94112000013', 'Client', 'Active'),
('Nuwan De Silva', 'nuwan.desilva@mail.lk', '$2y$10$demo_hash_14', '+94711000014', 'Client', 'Active'),
('Ayesha Farook', 'ayesha.farook@mail.lk', '$2y$10$demo_hash_15', '+94711000015', 'Client', 'Active'),
('GreenGrid Energy', 'ops@greengrid.lk', '$2y$10$demo_hash_16', '+94112000016', 'Client', 'Active'),
('Horizon Logistics', 'tech@horizonlogistics.lk', '$2y$10$demo_hash_17', '+94112000017', 'Client', 'Active'),
('Kushan Rathnayake', 'kushan.r@mail.lk', '$2y$10$demo_hash_18', '+94761000018', 'Client', 'Active'),
('Southern Agro Tech', 'data@southernagro.lk', '$2y$10$demo_hash_19', '+94912000019', 'Client', 'Active'),
('Metro Media Digital', 'innovation@metromedia.lk', '$2y$10$demo_hash_20', '+94112000020', 'Client', 'Active'),
('Admin Samadhi', 'admin.samadhi@aihub.lk', '$2y$10$demo_hash_21', '+94771000021', 'Admin', 'Active'),
('Admin Ravindu', 'admin.ravindu@aihub.lk', '$2y$10$demo_hash_22', '+94771000022', 'Admin', 'Active'),
('Manager Nadeesha', 'manager.nadeesha@aihub.lk', '$2y$10$demo_hash_23', '+94771000023', 'Manager', 'Active');

-- 2. FREELANCERS
INSERT INTO freelancers (freelancer_id, headline, bio, hourly_rate, experience_years, availability_status) VALUES
(1, 'Machine Learning & Predictive Modeling Engineer', 'Builds tabular ML pipelines, credit scoring models, and churn predictors.', 35.00, 4, 'Available'),
(2, 'NLP & Conversational AI Specialist', 'Develops domain chatbots, text classifiers, and summarization tools.', 40.00, 5, 'Available'),
(3, 'Computer Vision & Image Inspection Engineer', 'Specializes in object detection, quality inspection, and OCR pipelines.', 42.00, 4, 'Busy'),
(4, 'Generative AI & RAG Architect', 'Designs retrieval-augmented generation workflows and LLM evaluation suites.', 50.00, 3, 'Available'),
(5, 'Data Analytics & BI Visualization Specialist', 'Turns raw operational datasets into forecasting models and KPI dashboards.', 30.00, 3, 'Available'),
(6, 'Deep Learning & Time-Series Researcher', 'Focuses on neural forecasting, anomaly detection, and model tuning.', 38.00, 4, 'Available'),
(7, 'Applied ML & MLOps Developer', 'Packages ML models into reliable APIs with drift monitoring.', 45.00, 5, 'Available'),
(8, 'Document AI & OCR Specialist', 'Automates invoice, receipt, and prescription text extraction.', 32.00, 2, 'Available'),
(9, 'Customer Analytics & Segmentation Consultant', 'Builds cohort analysis, RFM models, and recommendation engines.', 28.00, 3, 'Available'),
(10, 'Multimodal & Vision-Language Engineer', 'Works on visual search, captioning, and video analytics.', 46.00, 4, 'Unavailable');

-- 3. CLIENTS
INSERT INTO clients (client_id, client_type, organization_name, industry, country) VALUES
(11, 'Organization', 'Lanka FinTech Labs', 'Financial Services', 'Sri Lanka'),
(12, 'Organization', 'Ceylon Retail Group', 'Retail & E-Commerce', 'Sri Lanka'),
(13, 'Organization', 'Helix Health Systems', 'Healthcare', 'Singapore'),
(14, 'Individual', NULL, 'EdTech & Research', 'Sri Lanka'),
(15, 'Individual', NULL, 'Digital Marketing', 'United Kingdom'),
(16, 'Organization', 'GreenGrid Energy', 'Renewable Energy', 'Sri Lanka'),
(17, 'Organization', 'Horizon Logistics', 'Supply Chain & Logistics', 'UAE'),
(18, 'Individual', NULL, 'FinTech Startup', 'Australia'),
(19, 'Organization', 'Southern Agro Tech', 'Agriculture & IoT', 'Sri Lanka'),
(20, 'Organization', 'Metro Media Digital', 'Media & Publishing', 'Sri Lanka');

-- 4. AI_SKILLS
INSERT INTO ai_skills (skill_name, ai_domain, description) VALUES
('Scikit-Learn & XGBoost', 'Machine Learning', 'Gradient boosting, classification, and regression modeling'),
('PyTorch Neural Networks', 'Deep Learning', 'Custom neural architectures, CNNs, and sequence models'),
('Retrieval-Augmented Generation (RAG)', 'Generative AI', 'Vector search grounding and LLM response pipelines'),
('YOLOv8 Object Detection', 'Computer Vision', 'Real-time visual detection and bounding-box tracking'),
('Named Entity Recognition (NER)', 'NLP', 'Extracting structured entities from unstructured text'),
('SQL & PowerBI Analytics', 'Data Analytics', 'Analytical querying, cohort metrics, and business dashboards'),
('Time-Series Forecasting', 'Data Analytics', 'Demand, load, and sales forecasting models'),
('Prompt Engineering & LLM Evaluation', 'Generative AI', 'Structured prompting, guardrails, and accuracy benchmarking'),
('Optical Character Recognition (OCR)', 'Computer Vision', 'Extracting text from scanned documents and images'),
('Sentiment & Intent Analysis', 'NLP', 'Classifying customer feedback, reviews, and support tickets'),
('Anomaly Detection', 'Machine Learning', 'Detecting fraud, sensor faults, and unusual transactions'),
('Transformer Fine-Tuning', 'Deep Learning', 'Adapting pretrained language and vision backbones');

-- 5. FREELANCER_SKILLS
INSERT INTO freelancer_skills (freelancer_id, skill_id, proficiency_level, years_used) VALUES
(1, 1, 'Expert', 4),
(1, 11, 'Intermediate', 3),
(2, 5, 'Expert', 4),
(2, 10, 'Expert', 5),
(3, 4, 'Expert', 4),
(3, 9, 'Intermediate', 3),
(4, 3, 'Expert', 3),
(4, 8, 'Expert', 3),
(5, 6, 'Expert', 3),
(5, 7, 'Intermediate', 2),
(6, 2, 'Expert', 4),
(6, 7, 'Expert', 4),
(7, 1, 'Expert', 5),
(8, 9, 'Expert', 2);

-- 6. AI_SERVICES
INSERT INTO ai_services (freelancer_id, service_title, ai_domain, description, base_price, delivery_days, is_active) VALUES
(1, 'Customer Churn & Risk Prediction Model', 'Machine Learning', 'End-to-end training, evaluation, and feature importance report.', 650.00, 10, 1),
(2, 'Customer Support Ticket Classifier & Bot', 'NLP', 'Automated intent routing and sentiment tagging for support teams.', 800.00, 14, 1),
(3, 'Shelf & Packaging Visual Defect Detector', 'Computer Vision', 'Custom object detection pipeline for retail and factory images.', 1100.00, 15, 1),
(4, 'Private Knowledge Base RAG Assistant', 'Generative AI', 'Document indexing, semantic retrieval, and citation-backed Q&A.', 1250.00, 12, 1),
(5, 'Sales & Revenue Analytics Dashboard', 'Data Analytics', 'Interactive reporting views and KPI trend analysis.', 500.00, 7, 1),
(6, 'Energy & Demand Time-Series Forecast', 'Deep Learning', 'Multi-horizon forecasting with error metrics and baselines.', 900.00, 12, 1),
(7, 'ML Model API & Monitoring Setup', 'Machine Learning', 'Containerized inference endpoint with basic drift checks.', 850.00, 8, 1),
(8, 'Automated Invoice & Receipt OCR Pipeline', 'Computer Vision', 'Structured JSON/table extraction from scanned receipts.', 550.00, 7, 1),
(9, 'Customer Segmentation & Retention Study', 'Data Analytics', 'Behavioral clustering and retention opportunity breakdown.', 480.00, 6, 1),
(10, 'Multimodal Product Tagging Prototype', 'Generative AI', 'Automated attribute tagging for visual catalogs.', 950.00, 10, 0),
(2, 'Document Summarization & Entity Extractor', 'NLP', 'Key entity and summary extraction for long reports.', 700.00, 9, 1);

-- 7. PROJECTS
INSERT INTO projects (client_id, title, description, budget, deadline, project_status) VALUES
(11, 'Fraudulent Transaction Screening Model', 'Flag high-risk card and transfer transactions using historical patterns.', 1800.00, '2026-12-28', 'Open'),
(12, 'Retail Shelf Stock-Out Vision System', 'Detect empty shelf gaps from store camera snapshots.', 2400.00, '2027-01-15', 'In Progress'),
(13, 'Clinical Note Entity Extraction Pipeline', 'Extract medications, symptoms, and lab terms from unstructured notes.', 2100.00, '2027-01-10', 'In Progress'),
(14, 'Course Syllabus RAG Study Assistant', 'Answer student questions grounded strictly in lecture PDFs.', 1200.00, '2026-12-20', 'Open'),
(15, 'Ad Copy Sentiment & Engagement Analyzer', 'Analyze campaign comments and predict engagement tone.', 750.00, '2026-12-18', 'Completed'),
(16, 'Solar & Grid Load Forecasting Model', 'Forecast daily power demand across regional substations.', 2200.00, '2027-01-25', 'In Progress'),
(17, 'Shipment Delay Risk & Route Analytics', 'Identify late-delivery corridors and operational bottlenecks.', 1600.00, '2026-12-30', 'Open'),
(18, 'Receipt & Expense OCR Data Extractor', 'Parse uploaded expense bills into structured line items.', 950.00, '2026-12-22', 'Completed'),
(19, 'Crop Yield & Weather Trend Analysis', 'Analyze seasonal yield records and rainfall indicators.', 1100.00, '2027-01-05', 'Open'),
(20, 'News Article Topic & Summary Generator', 'Generate concise briefs and topic tags for editorial drafts.', 1400.00, '2026-12-27', 'In Progress'),
(12, 'Customer Cohort & Repeat Purchase Report', 'Build repeat-purchase and churn cohorts for loyalty members.', 900.00, '2026-12-19', 'Completed');

-- 8. PROJECT_REQUIREMENTS
INSERT INTO project_requirements (project_id, skill_id, required_level, is_mandatory) VALUES
(1, 11, 'Expert', 1),
(1, 1, 'Intermediate', 1),
(2, 4, 'Expert', 1),
(3, 5, 'Expert', 1),
(3, 12, 'Intermediate', 0),
(4, 3, 'Intermediate', 1),
(4, 8, 'Intermediate', 1),
(5, 10, 'Intermediate', 1),
(6, 7, 'Expert', 1),
(6, 2, 'Intermediate', 1),
(7, 6, 'Intermediate', 1),
(8, 9, 'Intermediate', 1),
(10, 3, 'Intermediate', 1);

-- 9. PROPOSALS
INSERT INTO proposals (project_id, freelancer_id, bid_amount, estimated_days, cover_letter, proposal_status) VALUES
(1, 1, 1750.00, 14, 'Experienced with imbalanced fraud datasets and precision-recall tuning.', 'Pending'),
(1, 7, 1800.00, 12, 'Can deliver a scored model plus a clean inference script.', 'Pending'),
(2, 3, 2350.00, 18, 'Built similar retail shelf detection pipelines with YOLOv8.', 'Accepted'),
(3, 2, 2050.00, 16, 'Strong background in domain NER and clinical text normalization.', 'Accepted'),
(4, 4, 1150.00, 10, 'Will implement chunking, embeddings, and source citations.', 'Pending'),
(5, 2, 700.00, 7, 'Ready to deliver sentiment polarity and topic breakdown fast.', 'Accepted'),
(6, 6, 2150.00, 15, 'Specialize in multivariate time-series neural forecasting.', 'Accepted'),
(7, 5, 1500.00, 11, 'Will build clear delay root-cause breakdowns and dashboards.', 'Pending'),
(8, 8, 900.00, 8, 'Experienced with noisy receipt layouts and field validation.', 'Accepted'),
(9, 5, 1050.00, 9, 'Can produce clear seasonal trend comparisons and summaries.', 'Pending'),
(10, 4, 1350.00, 11, 'Will build structured summarization with factual checks.', 'Accepted'),
(11, 9, 850.00, 7, 'Experienced in cohort retention tables and customer segments.', 'Accepted');

-- 10. PROJECT_ASSIGNMENTS
INSERT INTO project_assignments (project_id, freelancer_id, proposal_id, agreed_amount, assigned_date, completion_date, assignment_status) VALUES
(2, 3, 3, 2350.00, '2026-11-02', NULL, 'Active'),
(3, 2, 4, 2050.00, '2026-11-03', NULL, 'Active'),
(5, 2, 6, 700.00, '2026-10-15', '2026-10-23', 'Completed'),
(6, 6, 7, 2150.00, '2026-11-05', NULL, 'Active'),
(8, 8, 9, 900.00, '2026-10-18', '2026-10-27', 'Completed'),
(10, 4, 11, 1350.00, '2026-11-06', NULL, 'Active'),
(11, 9, 12, 850.00, '2026-10-20', '2026-10-28', 'Completed'),
(1, 1, 1, 1750.00, '2026-11-08', NULL, 'Disputed'),
(4, 4, 5, 1150.00, '2026-11-09', NULL, 'Active'),
(7, 5, 8, 1500.00, '2026-11-10', NULL, 'Active');

-- 11. PAYMENTS
INSERT INTO payments (assignment_id, amount, platform_fee, payment_method, payment_status) VALUES
(1, 1175.00, 117.50, 'Bank Transfer', 'Completed'),
(2, 1025.00, 102.50, 'Escrow Wallet', 'Completed'),
(3, 700.00, 70.00, 'Credit Card', 'Completed'),
(4, 1075.00, 107.50, 'Bank Transfer', 'Completed'),
(5, 900.00, 90.00, 'PayPal', 'Completed'),
(6, 675.00, 67.50, 'Escrow Wallet', 'Completed'),
(7, 850.00, 85.00, 'Credit Card', 'Completed'),
(8, 500.00, 50.00, 'Escrow Wallet', 'Pending'),
(9, 575.00, 57.50, 'Bank Transfer', 'Pending'),
(10, 750.00, 75.00, 'Credit Card', 'Completed'),
(1, 1175.00, 117.50, 'Bank Transfer', 'Pending');

-- 12. REVIEWS
INSERT INTO reviews (assignment_id, reviewer_id, reviewee_id, rating, comment) VALUES
(3, 15, 2, 5, 'Clear sentiment analysis and delivered ahead of schedule.'),
(3, 2, 15, 5, 'Well-defined requirements and prompt feedback.'),
(5, 18, 8, 4, 'Accurate extraction on most receipts; minor formatting edge cases.'),
(5, 8, 18, 5, 'Smooth communication and clear sample files.'),
(7, 12, 9, 5, 'Helpful cohort breakdown that guided our retention plan.'),
(7, 9, 12, 4, 'Good data access and clear business questions.'),
(1, 12, 3, 4, 'Strong computer vision progress on test images.'),
(2, 13, 2, 5, 'Thorough domain understanding for clinical text.'),
(6, 20, 4, 5, 'Clean summarization workflow and helpful documentation.'),
(8, 11, 1, 2, 'Scope and dataset readiness caused a delay during review.');

-- 13. DISPUTES
INSERT INTO disputes (assignment_id, raised_by, resolved_by, reason, resolution_notes, dispute_status) VALUES
(8, 11, 21, 'Dataset schema mismatch delayed initial model training.', 'Clarified column mapping and extended milestone window.', 'Open'),
(1, 3, 21, 'Sample lighting variations differed from production images.', 'Added augmented test samples to evaluation set.', 'Under Review'),
(2, 13, 22, 'Clarification needed on handling rare abbreviation edge cases.', 'Documented fallback rule for rare clinical abbreviations.', 'Resolved'),
(4, 6, 21, 'Missing historical meter readings for one regional feeder.', 'Supplied interpolated baseline series from archive.', 'Resolved'),
(6, 4, 22, 'Revision request exceeded initial draft count.', 'Agreed on one extra revision pass.', 'Resolved'),
(7, 5, 21, 'Delayed export of shipping transit logs.', 'Provided CSV export from logistics portal.', 'Resolved'),
(9, 14, 22, 'PDF scan quality low on two reference chapters.', 'Replaced with higher-resolution source files.', 'Resolved'),
(10, 17, 21, 'Additional region filter requested mid-task.', 'Scoped as a follow-up enhancement.', 'Resolved'),
(3, 15, 22, 'Timezone overlap for final walkthrough.', 'Completed walkthrough via recorded summary.', 'Resolved'),
(5, 18, 21, 'Requested custom field order in output table.', 'Updated export mapping and verified output.', 'Resolved');