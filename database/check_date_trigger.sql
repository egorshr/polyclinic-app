

CREATE TRIGGER before_visit_insert_check_date
    BEFORE INSERT ON visits
    FOR EACH ROW
BEGIN
    IF NEW.visit_date_and_time < NOW() THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Ошибка: Нельзя создать запись на визит на прошедшую дату.';
END IF;
END