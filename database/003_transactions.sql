-- #1 students table
CREATE TABLE IF NOT EXISTS students (
    -- Primary key for the students table
    student_id INT PRIMARY KEY AUTO_INCREMENT,

    -- Student name
    student_first_name VARCHAR(50) NOT NULL,
    student_last_name VARCHAR(50) NOT NULL,

    -- Student course
    student_course VARCHAR(50) NOT NULL,

    -- Student created at timestamp
    student_created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;

-- Insert statement #1: Insert Students
INSERT INTO students (
    student_first_name,
    student_last_name,
    student_course
) VALUES
('CLIFF AMADEUS', 'EVANGELIO', 'BSIT'),
('JAN XAVIER', 'EVANGELIO', 'BSA-AGRONOMY'),
('RON EDMUND', 'EVANGELIO', 'BSEE'),
('RK', 'FERNANDEZ', 'BSIT');

-- #2 books table
CREATE TABLE IF NOT EXISTS books (
    -- Primary key for the books table
    book_id INT PRIMARY KEY AUTO_INCREMENT,

    -- Book details
    book_title VARCHAR(100) NOT NULL,
    book_author VARCHAR(100) NOT NULL,
    book_category VARCHAR(50) NOT NULL,

    -- Book created at timestamp
    book_created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;

-- Insert statement #2: Insert Books
INSERT INTO books (
    book_title,
    book_author,
    book_category
) VALUES
('Project Hail Mary', 'Andy Weir', 'Science Fiction'),
('Jurassic Park', 'Michael Crichton', 'Science Fiction'),
('1984', 'George Orwell', 'Science Fiction');

-- #3 borrow transactions table
CREATE TABLE IF NOT EXISTS borrow_transactions (
    -- Primary key for the borrow transactions table
    borrow_id INT AUTO_INCREMENT PRIMARY KEY,

    -- Foreign key references
    student_id INT NOT NULL,
    book_id INT NOT NULL,

    -- Borrow timestamp not null by default
    borrow_date TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    -- Borrow return timestamp null by default
    borrow_return_date TIMESTAMP NULL
        DEFAULT NULL,

    -- Borrow transactions table constraints and foreign keys
    CONSTRAINT fk_borrow_student
        FOREIGN KEY (student_id)
        REFERENCES students(student_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_borrow_book
        FOREIGN KEY (book_id)
        REFERENCES books(book_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- Borrow Due Date
ALTER TABLE borrow_transactions
ADD COLUMN borrow_due_date DATE NULL
AFTER borrow_date;

-- Sample Due Date 
INSERT INTO borrow_transactions (
    student_id,
    book_id,
    borrow_date,
    borrow_due_date
) VALUES
(1, 1, '2026-09-15', '2026-09-20'),
(2, 2, '2026-09-16', '2026-09-20'),
(3, 3, '2026-09-17', '2026-09-20');