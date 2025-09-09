CREATE PROCEDURE create_visit(
    IN p_patient_id INT,
    IN p_employee_id INT,
    IN p_visit_datetime DATETIME,
    IN p_service_id INT
)
BEGIN
    DECLARE new_visit_id INT;

INSERT INTO visits (patient_id, employee_id, visit_date_and_time, status)
VALUES (p_patient_id, p_employee_id, p_visit_datetime, 'PLANNED');


SET new_visit_id = LAST_INSERT_ID();

    IF p_service_id IS NOT NULL THEN
        INSERT INTO visit_services (visit_id, service_id)
        VALUES (new_visit_id, p_service_id);
END IF;

SELECT new_visit_id AS 'new_visit_id';

END