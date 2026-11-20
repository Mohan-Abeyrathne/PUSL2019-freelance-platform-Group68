CREATE DATABASE IF NOT EXISTS ai_freelance_platform;

USE ai_freelance_platform;

-- 1. USERS (table for Freelancers, Clients, Admins, and Managers)
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(25) NULL,
    role ENUM('Freelancer', 'Client', 'Admin', 'Manager') NOT NULL,
    account_status ENUM('Active', 'Suspended', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 2. FREELANCERS
CREATE TABLE freelancers (
    freelancer_id INT PRIMARY KEY NOT NULL,
    headline VARCHAR(150) NOT NULL,
    bio TEXT NULL,
    hourly_rate DECIMAL(10, 2) NOT NULL,
    experience_years INT NOT NULL DEFAULT 0,
    availability_status ENUM('Available', 'Busy', 'Unavailable') NOT NULL DEFAULT 'Available',
    CONSTRAINT fk_freelancers_user FOREIGN KEY (freelancer_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT chk_freelancer_rate CHECK (hourly_rate > 0),
    CONSTRAINT chk_freelancer_exp CHECK (experience_years >= 0)
);

-- 3. CLIENTS
CREATE TABLE clients (
    client_id INT PRIMARY KEY NOT NULL,
    client_type ENUM('Individual', 'Organization') NOT NULL,
    organization_name VARCHAR(150) NULL,
    industry VARCHAR(100) NULL,
    country VARCHAR(80) NOT NULL,
    CONSTRAINT fk_clients_user FOREIGN KEY (client_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- 4. AI_SKILLS
CREATE TABLE ai_skills (
    skill_id INT AUTO_INCREMENT PRIMARY KEY,
    skill_name VARCHAR(100) NOT NULL UNIQUE,
    ai_domain ENUM(
        'Machine Learning',
        'Deep Learning',
        'NLP',
        'Computer Vision',
        'Generative AI',
        'Data Analytics'
    ) NOT NULL,
    description VARCHAR(255) NULL
);

-- 5. FREELANCER_SKILLS
CREATE TABLE freelancer_skills (
    freelancer_id INT NOT NULL,
    skill_id INT NOT NULL,
    proficiency_level ENUM('Beginner', 'Intermediate', 'Expert') NOT NULL DEFAULT 'Intermediate',
    years_used INT NOT NULL DEFAULT 1,
    CONSTRAINT pk_freelancer_skills PRIMARY KEY (freelancer_id, skill_id),
    CONSTRAINT fk_fs_freelancer FOREIGN KEY (freelancer_id)
        REFERENCES freelancers(freelancer_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_fs_skill FOREIGN KEY (skill_id)
        REFERENCES ai_skills(skill_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT chk_fs_years CHECK (years_used >= 0)
);

-- 6. AI_SERVICES
CREATE TABLE ai_services (
    service_id INT PRIMARY KEY AUTO_INCREMENT,
    freelancer_id INT NOT NULL,
    service_title VARCHAR(150) NOT NULL,
    ai_domain ENUM(
        'Machine Learning',
        'Deep Learning',
        'NLP',
        'Computer Vision',
        'Generative AI',
        'Data Analytics'
    ) NOT NULL,
    description TEXT NOT NULL,
    base_price DECIMAL(10, 2) NOT NULL,
    delivery_days INT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_services_freelancer FOREIGN KEY (freelancer_id)
        REFERENCES freelancers(freelancer_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT chk_service_price CHECK (base_price > 0),
    CONSTRAINT chk_service_days CHECK (delivery_days >= 1)
);

-- 7. PROJECTS
CREATE TABLE projects (
    project_id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    budget DECIMAL(10, 2) NOT NULL,
    deadline DATE NOT NULL,
    project_status ENUM('Open', 'In Progress', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_projects_client FOREIGN KEY (client_id)
        REFERENCES clients(client_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT chk_project_budget CHECK (budget > 0)
);

-- 8. PROJECT_REQUIREMENTS
CREATE TABLE project_requirements (
    project_id INT NOT NULL,
    skill_id INT NOT NULL,
    required_level ENUM('Beginner', 'Intermediate', 'Expert') NOT NULL DEFAULT 'Intermediate',
    is_mandatory TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT pk_project_requirements PRIMARY KEY (project_id, skill_id),
    CONSTRAINT fk_pr_project FOREIGN KEY (project_id)
        REFERENCES projects(project_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_pr_skill FOREIGN KEY (skill_id)
        REFERENCES ai_skills(skill_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

-- 9. PROPOSALS
CREATE TABLE proposals (
    proposal_id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    freelancer_id INT NOT NULL,
    bid_amount DECIMAL(10, 2) NOT NULL,
    estimated_days INT NOT NULL,
    cover_letter TEXT NOT NULL,
    proposal_status ENUM('Pending', 'Accepted', 'Rejected', 'Withdrawn') NOT NULL DEFAULT 'Pending',
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_project_freelancer_proposal UNIQUE (project_id, freelancer_id),
    CONSTRAINT fk_proposals_project FOREIGN KEY (project_id)
        REFERENCES projects(project_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_proposals_freelancer FOREIGN KEY (freelancer_id)
        REFERENCES freelancers(freelancer_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT chk_proposal_bid CHECK (bid_amount > 0),
    CONSTRAINT chk_proposal_days CHECK (estimated_days >= 1)
);

-- 10. PROJECT_ASSIGNMENTS
CREATE TABLE project_assignments (
    assignment_id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT UNIQUE NOT NULL,
    freelancer_id INT NOT NULL,
    proposal_id INT UNIQUE NOT NULL,
    agreed_amount DECIMAL(10, 2) NOT NULL,
    assigned_date DATE NOT NULL,
    completion_date DATE NULL,
    assignment_status ENUM('Active', 'Completed', 'Disputed') NOT NULL DEFAULT 'Active',
    CONSTRAINT fk_assign_project FOREIGN KEY (project_id)
        REFERENCES projects(project_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_assign_freelancer FOREIGN KEY (freelancer_id)
        REFERENCES freelancers(freelancer_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_assign_proposal FOREIGN KEY (proposal_id)
        REFERENCES proposals(proposal_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT chk_agreed_amount CHECK (agreed_amount > 0)
);

-- 11. PAYMENTS
CREATE TABLE payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    platform_fee DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('Credit Card', 'Bank Transfer', 'PayPal', 'Escrow Wallet') NOT NULL,
    payment_status ENUM('Pending', 'Completed', 'Failed', 'Refunded') NOT NULL DEFAULT 'Pending',
    payment_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_assignment FOREIGN KEY (assignment_id)
        REFERENCES project_assignments(assignment_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT chk_payment_amount CHECK (amount > 0),
    CONSTRAINT chk_payment_fee CHECK (platform_fee >= 0)
);

-- 12. REVIEWS
CREATE TABLE reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    rating INT NOT NULL,
    comment TEXT NULL,
    reviewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_assignment_reviewer UNIQUE (assignment_id, reviewer_id),
    CONSTRAINT fk_reviews_assignment FOREIGN KEY (assignment_id)
        REFERENCES project_assignments(assignment_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_reviews_reviewer FOREIGN KEY (reviewer_id)
        REFERENCES users(user_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_reviews_reviewee FOREIGN KEY (reviewee_id)
        REFERENCES users(user_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5)
);

-- 13. DISPUTES
CREATE TABLE disputes (
    dispute_id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT NOT NULL,
    raised_by INT NOT NULL,
    resolved_by INT NULL,
    reason TEXT NOT NULL,
    resolution_notes TEXT NULL,
    dispute_status ENUM('Open', 'Under Review', 'Resolved') NOT NULL DEFAULT 'Open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_disputes_assignment FOREIGN KEY (assignment_id)
        REFERENCES project_assignments(assignment_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_disputes_raiser FOREIGN KEY (raised_by)
        REFERENCES users(user_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_disputes_resolver FOREIGN KEY (resolved_by)
        REFERENCES users(user_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);