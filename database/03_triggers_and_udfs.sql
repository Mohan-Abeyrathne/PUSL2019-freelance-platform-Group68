USE ai_freelance_platform;

DROP TRIGGER IF EXISTS trg_AfterInsert_Assignment;
DROP TRIGGER IF EXISTS trg_BeforeInsert_Payment;
DROP FUNCTION IF EXISTS fn_CalculateNetPayout;
DROP FUNCTION IF EXISTS fn_FreelancerAvgRating;

DELIMITER $$

-- Trigger 1: After inserting an assignment, 
--            set project -> 'In Progress' 
--                       and
--            accepted proposal -> 'Accepted'
CREATE TRIGGER trg_AfterInsert_Assignment
AFTER INSERT ON project_assignments
FOR EACH ROW
BEGIN
    UPDATE projects
    SET project_status = 'In Progress'
    WHERE project_id = NEW.project_id;

    UPDATE proposals
    SET proposal_status = 'Accepted'
    WHERE proposal_id = NEW.proposal_id;
END$$

-- Trigger 2: Before inserting a payment, set 10% platform_fee if 0.00 or NULL
CREATE TRIGGER trg_BeforeInsert_Payment
BEFORE INSERT ON payments
FOR EACH ROW
BEGIN
    IF NEW.platform_fee IS NULL OR NEW.platform_fee = 0.00 THEN
        SET NEW.platform_fee = ROUND(NEW.amount * 0.10, 2);
    END IF;
END$$

-- UDF 1: Calculate net payout after platform fee
CREATE FUNCTION fn_CalculateNetPayout(
    p_amount DECIMAL(10, 2),
    p_fee DECIMAL(10, 2)
)
RETURNS DECIMAL(10, 2)
DETERMINISTIC
BEGIN
    DECLARE v_net DECIMAL(10, 2);
    SET v_net = IFNULL(p_amount, 0.00) - IFNULL(p_fee, 0.00);
    IF v_net < 0.00 THEN
        SET v_net = 0.00;
    END IF;
    RETURN v_net;
END$$

-- UDF2: Calculate average rating for a freelancer
CREATE FUNCTION fn_FreelancerAvgRating(
    p_freelancer_id INT
)
RETURNS DECIMAL(3, 2)
READS SQL DATA
BEGIN
    DECLARE v_avg DECIMAL(3, 2);

    SELECT IFNULL(ROUND(AVG(rating), 2), 0.00)
    INTO v_avg
    FROM reviews
    WHERE reviewee_id = p_freelancer_id;

    RETURN v_avg;
END$$