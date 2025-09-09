CREATE PROCEDURE complete_visit_status(
    IN p_visit_id INT
)
BEGIN
    IF EXISTS (SELECT 1 FROM visits WHERE id = p_visit_id AND status = 'PLANNED') THEN

        UPDATE visits
        SET status = 'COMPLETED'
        WHERE id = p_visit_id;

        SELECT 'Статус визита успешно изменен на "Завершен".' AS 'message';
    ELSE
        SELECT 'Ошибка: визит не найден или его статус не "Запланирован".' AS 'message';
    END IF;

END