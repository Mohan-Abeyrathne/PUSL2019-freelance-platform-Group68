USE ai_freelance_platform;

DROP VIEW IF EXISTS vw_ProjectDetails;
DROP VIEW IF EXISTS vw_HighBudgetProjects;
DROP PROCEDURE IF EXISTS sp_DisplayAllProjects;
DROP PROCEDURE IF EXISTS sp_GetProjectsByMinBudget;
DROP PROCEDURE IF EXISTS sp_AddSkill;

-- View 1: Display all project details with client name
CREATE VIEW vw_ProjectDetails AS
SELECT
    p.project_id,
    p.title,
    COALESCE(c.organization_name, u.full_name) AS client_name,
    p.budget,
    p.deadline,
    p.project_status
FROM projects p
JOIN clients c ON p.client_id = c.client_id
JOIN users u ON c.client_id = u.user_id;

-- View 2: Display projects with budget >= 1500.00
CREATE VIEW vw_HighBudgetProjects AS
SELECT
    project_id,
    title,
    budget,
    deadline,
    project_status
FROM projects
WHERE budget >= 1500.00;

DELIMITER $$

-- Stored Procedure 1: Display all projects
CREATE PROCEDURE sp_DisplayAllProjects()
BEGIN
    SELECT
        p.project_id,
        p.title,
        COALESCE(c.organization_name, u.full_name) AS client_name,
        p.budget,
        p.deadline,
        p.project_status
    FROM projects p
    JOIN clients c ON p.client_id = c.client_id
    JOIN users u ON c.client_id = u.user_id;
END$$

-- Stored Procedure 2: Get projects where budget >= p_min_budget
CREATE PROCEDURE sp_GetProjectsByMinBudget(
    IN p_min_budget DECIMAL(10, 2)
)
BEGIN
    SELECT
        project_id,
        title,
        budget,
        deadline,
        project_status
    FROM projects
    WHERE budget >= p_min_budget;
END$$

-- Stored Procedure 3: Add a new AI skill
CREATE PROCEDURE sp_AddSkill(
    IN p_skill_name VARCHAR(100),
    IN p_ai_domain VARCHAR(50),
    IN p_description VARCHAR(255)
)
BEGIN
    INSERT INTO ai_skills (skill_name, ai_domain, description)
    VALUES (p_skill_name, p_ai_domain, p_description);
END$$

DELIMITER ;