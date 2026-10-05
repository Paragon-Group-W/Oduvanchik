-- Модуль 2: создание БД "Гостиница Alpha" по ER-диаграмме из 01_ER-diagram.md
-- Выполнять во вкладке SQL новой БД в phpMyAdmin (OpenServer -> MySQL).

CREATE DATABASE IF NOT EXISTS alpha_hotel
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE alpha_hotel;

-- Роли пользователей системы (Администратор / Пользователь)
CREATE TABLE Roles (
    RoleID   INT AUTO_INCREMENT PRIMARY KEY,
    RoleName VARCHAR(50) NOT NULL UNIQUE
);

-- Сотрудники, которые входят в систему (для Модуля 3)
CREATE TABLE Users (
    UserID          INT AUTO_INCREMENT PRIMARY KEY,
    UserName        VARCHAR(50) NOT NULL UNIQUE,
    RoleID          INT NOT NULL,
    PasswordHash    VARCHAR(255) NOT NULL,
    LastLoginDate   DATETIME NULL,
    Blocked         BOOLEAN NOT NULL DEFAULT FALSE,
    PasswordChanged BOOLEAN NOT NULL DEFAULT FALSE,
    FailedAttempts  INT NOT NULL DEFAULT 0,
    FOREIGN KEY (RoleID) REFERENCES Roles(RoleID)
);

-- Гости гостиницы
CREATE TABLE Clients (
    ClientID INT AUTO_INCREMENT PRIMARY KEY,
    FullName VARCHAR(150) NOT NULL,
    Passport VARCHAR(50)  NOT NULL,
    Address  VARCHAR(200)
);

-- Справочник категорий номеров (взято из Номерной фонд.xlsx)
CREATE TABLE RoomCategories (
    CategoryID   INT AUTO_INCREMENT PRIMARY KEY,
    CategoryName VARCHAR(150) NOT NULL UNIQUE,
    BasePrice    DECIMAL(10,2) NOT NULL DEFAULT 0
);

-- Справочник статусов номера (взято из отчёта о состоянии номерного фонда)
CREATE TABLE RoomStatuses (
    StatusID   INT AUTO_INCREMENT PRIMARY KEY,
    StatusName VARCHAR(50) NOT NULL UNIQUE
);

-- Сами номера. RoomID — суррогатный INT-ключ (в исходнике репозитория была ошибка:
-- TEXT не может быть AUTO_INCREMENT в MySQL), RoomNumber — "человеческий" номер (101, 205...)
CREATE TABLE Rooms (
    RoomID     INT AUTO_INCREMENT PRIMARY KEY,
    Floor      INT NOT NULL,
    RoomNumber INT NOT NULL UNIQUE,
    CategoryID INT NOT NULL,
    StatusID   INT NOT NULL,
    FOREIGN KEY (CategoryID) REFERENCES RoomCategories(CategoryID),
    FOREIGN KEY (StatusID)   REFERENCES RoomStatuses(StatusID)
);

-- Карты доступа к номеру
CREATE TABLE Cards (
    CardID      INT AUTO_INCREMENT PRIMARY KEY,
    RoomID      INT NOT NULL,
    CreatedDate DATE NOT NULL,
    DestroyDate DATE NULL,
    FOREIGN KEY (RoomID) REFERENCES Rooms(RoomID)
);

-- Бронирования/проживания — связывает клиента, номер, сотрудника и карту
CREATE TABLE Orders (
    OrderID  INT AUTO_INCREMENT PRIMARY KEY,
    ClientID INT NOT NULL,
    RoomID   INT NOT NULL,
    UserID   INT NOT NULL,
    CardID   INT NULL,
    CheckIn  DATETIME NOT NULL,
    CheckOut DATETIME NULL,
    FOREIGN KEY (ClientID) REFERENCES Clients(ClientID),
    FOREIGN KEY (RoomID)   REFERENCES Rooms(RoomID),
    FOREIGN KEY (UserID)   REFERENCES Users(UserID),
    FOREIGN KEY (CardID)   REFERENCES Cards(CardID)
);

-- Справочники: заполняем реальными значениями из документов заказчика
INSERT INTO RoomStatuses (StatusName) VALUES
    ('Свободен'), ('Занят'), ('Грязный'), ('Назначен к уборке'), ('Чистый');

INSERT INTO RoomCategories (CategoryName) VALUES
    ('Одноместный стандарт'),
    ('Одноместный эконом'),
    ('Стандарт двухместный с 2 раздельными кроватями'),
    ('Эконом двухместный с 2 раздельными кроватями'),
    ('3-местный бюджет'),
    ('Бизнес с 1 или 2 кроватями'),
    ('Двухкомнатный двухместный стандарт с 1 или 2 кроватями'),
    ('Студия'),
    ('Люкс с 2 двуспальными кроватями');

-- Роли
INSERT INTO Roles (RoleName) VALUES ('Администратор'), ('Пользователь');

-- Первого администратора НЕ создаём здесь через SQL: пароль должен быть захэширован
-- функцией PHP password_hash(), а не SQL-функцией (иначе login.php не сможет его
-- проверить). Откройте в браузере 03_auth_module/setup_admin.php один раз — он
-- создаст пользователя admin/admin123 с корректным хэшем и PasswordChanged = 0
-- (значит при первом входе система сразу попросит сменить пароль).
