-- Модуль 2: "процент загрузки номерного фонда" =
--           количество проданных ночей / общее количество номеров в отеле
-- (точная формулировка из текста задания, стр. 27 КОД 09.02.07-5-2025)

-- ВАЖНО: в исходном репозитории TOXAceleron запрос считал совсем другое —
-- процент номеров со статусом "Свободно", без учёта ночей и без таблицы Orders.
-- Это не "загрузка", а просто доля свободных номеров прямо сейчас. Если ваш
-- эксперт примет и такую трактовку — вариант "Б" ниже, но основной и точный
-- по формулировке — вариант "А".

-- ===== Вариант А (правильный, по формуле из задания) =====
-- Считает загрузку по ВСЕМ проданным ночам за всё время (CheckOut уже наступил).
-- Проверено на тестовых данных: в реальном отчёте заказчика встречаются "битые"
-- записи (например, дата выезда раньше даты заезда - опечатка оператора). Без
-- условия "CheckOut > CheckIn" такая строка даёт ОТРИЦАТЕЛЬНОЕ число ночей и молча
-- портит сумму по всем остальным броням - добавлено защитное условие ниже.
SELECT
    SUM(DATEDIFF(o.CheckOut, o.CheckIn))                 AS sold_nights,
    (SELECT COUNT(*) FROM Rooms)                         AS total_rooms,
    ROUND(
        SUM(DATEDIFF(o.CheckOut, o.CheckIn)) * 100.0
        / (SELECT COUNT(*) FROM Rooms)
    , 2) AS occupancy_percent
FROM Orders o
WHERE o.CheckOut IS NOT NULL
  AND o.CheckOut > o.CheckIn;

-- ===== Вариант А с периодом (как советует брифинг: "на определённую дату" /
-- за месяц) — поменяйте даты на нужный отчётный период =====
SET @period_start = '2025-03-01';
SET @period_end   = '2025-03-31';

SELECT
    ROUND(
        SUM(DATEDIFF(
            LEAST(o.CheckOut, @period_end),
            GREATEST(o.CheckIn, @period_start)
        )) * 100.0
        / ((SELECT COUNT(*) FROM Rooms) * (DATEDIFF(@period_end, @period_start) + 1))
    , 2) AS occupancy_percent_for_period
FROM Orders o
WHERE o.CheckIn <= @period_end
  AND (o.CheckOut IS NULL OR o.CheckOut >= @period_start)
  AND (o.CheckOut IS NULL OR o.CheckOut > o.CheckIn);

-- ===== Вариант Б (упрощённый, "по статусу номера сейчас", как в исходном репозитории) =====
-- Это НЕ отношение проданных ночей, а просто доля занятых номеров на текущий момент.
SELECT
    ROUND(
        COUNT(CASE WHEN st.StatusName = 'Занят' THEN 1 END) * 100.0 / COUNT(*)
    , 2) AS percent_occupied_now
FROM Rooms r
JOIN RoomStatuses st ON st.StatusID = r.StatusID;
