CREATE PROCEDURE get_employee_performance_report(IN start_date DATE, IN end_date DATE)
BEGIN

    SET end_date = DATE_ADD(end_date, INTERVAL 1 DAY);

    SELECT CONCAT(e.last_name, ' ', e.first_name, ' ', e.middle_name) AS "ФИО Врача",
           s.name                                                     AS "Специальность",
           COUNT(v.id)                                                AS "Кол-во визитов",
           SUM(ser.price)                                             AS "Суммарная стоимость услуг, руб."
    FROM employees e
             INNER JOIN visits v ON e.id = v.employee_id
             INNER JOIN specialties s ON e.speciality_id = s.id
             INNER JOIN visit_services vs ON v.id = vs.visit_id
             INNER JOIN services ser ON vs.service_id = ser.id
    WHERE v.visit_date_and_time >= start_date
      AND v.visit_date_and_time < end_date
      AND v.status = 'COMPLETED'
    GROUP BY e.id, e.last_name, e.first_name, e.middle_name, s.name
    ORDER BY `Суммарная стоимость услуг, руб.` DESC;
END