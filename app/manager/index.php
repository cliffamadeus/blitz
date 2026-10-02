<?php

require '../../config/config.php';

requireRole('manager');

requireCsrfToken();

// Determin current section
$section = $_GET['section'] ?? 'students';

// CRUD Operations
$action = $_GET['action'] ?? '';

//-----------------------------------------------------------
// Students
//-----------------------------------------------------------

// Fetch students
if($section === 'students'){
    $stmt = $pdo->query("
        SELECT * 
        FROM students
        ORDER by student_id DESC
    ");

    $students = $stmt->fetchAll();
}

// Create Student
if($section==='students' && $action==='create'){

    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $firstName = trim($_POST['student_first_name'] ?? '');
        $lastName = trim($_POST['student_last_name'] ?? '');
        $course = trim($_POST['student_course'] ?? '');

        if($firstName !== '' && $lastName !=='' && $course!==''){

            if (isset($_SESSION['user_id'])) {

                logActivity(
                    $pdo,
                    $_SESSION['user_id'],
                    $_SESSION['user_email'] ?? null,
                    'create-student',
                    'success'
                );
            }

            $sql=("
                INSERT INTO students (
                    student_first_name,
                    student_last_name,
                    student_course
                )
                VALUES (?,?,?)
            ");

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $firstName,
                $lastName,
                $course
            ]);

            //$_SESSION['alert'] = 'Student Saved Successfully';

            header("Location: index.php?section=students");
            exit;

        }
    }

}

// Update Student
if($section==='students'&& $action==='update'){
    
    $studentId = (int) ($_GET['id'] ?? 0);

    // Retrieve Student Information
    $stmt =  $pdo->prepare("
        SELECT *
        FROM students
        WHERE student_id =?
    ");

    $stmt->execute([$studentId]);

    $student = $stmt->fetch();

    // Update Student Info 
    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $firstName = trim($_POST['student_first_name'] ?? '');
        $lastName = trim($_POST['student_last_name'] ?? '');
        $course = trim($_POST['student_course'] ?? '');

        if($firstName !== '' && $lastName !=='' && $course!==''){

            if (isset($_SESSION['user_id'])) {

                logActivity(
                    $pdo,
                    $_SESSION['user_id'],
                    $_SESSION['user_email'] ?? null,
                    'update-student',
                    'success'
                );
            }

            $sql=("
                UPDATE students
                SET
                    student_first_name=?,
                    student_last_name=?,
                    student_course =?
                WHERE student_id=?
            ");

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $firstName,
                $lastName,
                $course,
                $studentId
            ]);

            $_SESSION['alert'] = 'Student Updated Successfully';

            header("Location: index.php?section=students");
            exit;

        }
    }

}


// Fetch books
if ($section === 'books') {

    $stmt = $pdo->query("
        SELECT *
        FROM books
        ORDER BY book_id DESC
    ");

    $books = $stmt->fetchAll();
}


// BOOKS CREATE
if ($section === 'books' && $action === 'create') {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $title    = trim($_POST['book_title'] ?? '');
        $author   = trim($_POST['book_author'] ?? '');
        $category = trim($_POST['book_category'] ?? '');

        if ($title !== '' && $author !== '' && $category !== '') {

            if (isset($_SESSION['user_id'])) {

                logActivity(
                    $pdo,
                    $_SESSION['user_id'],
                    $_SESSION['user_email'] ?? null,
                    'add-book',
                    'success'
                );
            }

            $stmt = $pdo->prepare("
                INSERT INTO books (
                    book_title,
                    book_author,
                    book_category
                )
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $title,
                $author,
                $category
            ]);

            header("Location: index.php?section=books");
            exit;
        }
    }
}

// BOOKS UPDATE
if ($section === 'books' && $action === 'update') {

    $bookId = (int) ($_GET['id'] ?? 0);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $title    = trim($_POST['book_title'] ?? '');
        $author   = trim($_POST['book_author'] ?? '');
        $category = trim($_POST['book_category'] ?? '');

        if (isset($_SESSION['user_id'])) {

            logActivity(
                $pdo,
                $_SESSION['user_id'],
                $_SESSION['user_email'] ?? null,
                'update-book',
                'success'
            );
        }

        $stmt = $pdo->prepare("
            UPDATE books
            SET
                book_title = ?,
                book_author = ?,
                book_category = ?
            WHERE book_id = ?
        ");

        $stmt->execute([
            $title,
            $author,
            $category,
            $bookId
        ]);

        header("Location: index.php?section=books");
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM books
        WHERE book_id = ?
    ");

    $stmt->execute([$bookId]);

    $book = $stmt->fetch();

    if (!$book) {
        die("Book not found.");
    }
}

// Fetch Borrow Records
if ($section === 'borrow') {

    // Fetch students for borrow form
    $stmt = $pdo->query("
        SELECT
            student_id,
            student_first_name,
            student_last_name
        FROM students
        ORDER BY student_last_name, student_first_name
    ");

    $students = $stmt->fetchAll();


    // Fetch books for borrow form
    $stmt = $pdo->query("
        SELECT
            book_id,
            book_title,
            book_author
        FROM books
        ORDER BY book_title
    ");

    $books = $stmt->fetchAll();


    // Fetch borrow records
    $stmt = $pdo->query("
        SELECT
            borrow_transactions.borrow_id,
            borrow_transactions.borrow_date,
            borrow_transactions.borrow_due_date,
            borrow_transactions.borrow_return_date,

            students.student_first_name,
            students.student_last_name,

            books.book_title,
            books.book_author

        FROM borrow_transactions

        INNER JOIN students
            ON borrow_transactions.student_id = students.student_id

        INNER JOIN books
            ON borrow_transactions.book_id = books.book_id

        ORDER BY borrow_transactions.borrow_id DESC
    ");

    $borrowRecords = $stmt->fetchAll();
}

// Borrow a book
if ($section === 'borrow' && $action === 'create') {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $studentId = (int) ($_POST['student_id'] ?? 0);
        $bookId = (int) ($_POST['book_id'] ?? 0);
        $dueDate = $_POST['borrow_due_date'] ?? '';

        if ($studentId > 0 && $bookId > 0 && $dueDate !== '') {

            // Check if student has an unreturned book
            $stmt = $pdo->prepare("
                SELECT borrow_id
                FROM borrow_transactions
                WHERE student_id = ?
                    AND borrow_return_date IS NULL
                LIMIT 1
            ");

            $stmt->execute([$studentId]);

            $studentBorrow = $stmt->fetch();

            if ($studentBorrow) {

                $_SESSION['alert'] =
                    'This student cannot borrow another book because a previous book has not been returned.';

            } else {

                // Check if book is currently borrowed
                $stmt = $pdo->prepare("
                    SELECT borrow_id
                    FROM borrow_transactions
                    WHERE book_id = ?
                        AND borrow_return_date IS NULL
                    LIMIT 1
                ");

                $stmt->execute([$bookId]);

                $bookBorrow = $stmt->fetch();

                if ($bookBorrow) {

                    $_SESSION['alert'] =
                        'This book cannot be borrowed because it has not been returned.';

                } else {

                    // Create borrow transaction
                    $stmt = $pdo->prepare("
                        INSERT INTO borrow_transactions (
                            student_id,
                            book_id,
                            borrow_due_date
                        )
                        VALUES (?, ?, ?)
                    ");

                    $stmt->execute([
                        $studentId,
                        $bookId,
                        $dueDate
                    ]);

                    // Log activity
                    if (isset($_SESSION['user_id'])) {

                        logActivity(
                            $pdo,
                            $_SESSION['user_id'],
                            $_SESSION['user_email'] ?? null,
                            'borrow-book',
                            'success'
                        );
                    }

                    $_SESSION['alert'] =
                        'Book borrowed successfully.';
                }
            }

            header("Location: index.php?section=borrow");
            exit;
        }

        $_SESSION['alert'] = 'Please select a student, book, and due date.';
    }
}

// BORROW RETURN
if ($section === 'borrow' && $action === 'return') {

    $borrowId = (int) ($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("
        UPDATE borrow_transactions
        SET borrow_return_date = CURRENT_TIMESTAMP
        WHERE borrow_id = ?
    ");

    $stmt->execute([$borrowId]);

    
    $_SESSION['alert'] = 'Book returned successfully.';

    header("Location: index.php?section=borrow");
    exit;
}

// BORROW DELETE
if ($section === 'borrow' && $action === 'delete') {

    $borrowId = (int) ($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("
        DELETE FROM borrow_transactions
        WHERE borrow_id = ?
    ");

    $stmt->execute([$borrowId]);

    header("Location: index.php?section=borrow");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form method="POST" action="../../auth/signout.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>" >
        <button type="submit"> Sign Out </button>
    </form>
    <h1>Simple Library System</h1>
    <nav>
        <a href="index.php?section=students">Students</a>
        <a href="index.php?section=books">Books</a>
        <a href="index.php?section=borrow">Borrow</a>
    </nav>
    <hr>
    <!--STUDENTS SECTION-->
    <?php if($section==='students'): ?>
        <h1>Students</h1>
        <p>
            <a href="index.php?section=students&action=create">
                Add Student
            </a>
        </p>
        <?php if($action==='create'): ?>

            <h2>Add student</h2>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>" >
                <p>
                    <label>First Name</label>
                    <br>
                    <input type="text" name="student_first_name" required />
                </p>
                <p>
                    <label>Last Name</label>
                    <br>
                    <input type="text" name="student_last_name" required/>
                </p>
                
                <p>
                    <label>Course</label>
                    <br>
                    <input type="text" name="student_course"required/>
                </p>

                <button type="submit">
                    Save
                </button>

                <a href="index.php?section=students">
                    Cancel
                </a>
            </form>

        <?php elseif($action==='update'): ?>

            <h2>Update Student</h2>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>" >
                <p>
                    <label>First Name</label>
                    <br>
                    <input type="text" name="student_first_name" value="<?=  htmlspecialchars($student['student_first_name'])?>"required/>
                </p>
                <p>
                    <label>Last Name</label>
                    <br>
                    <input type="text" name="student_last_name" value="<?=  htmlspecialchars($student['student_last_name'])?>" required/>
                </p>
                
                <p>
                    <label>Course</label>
                    <br>
                    <input type="text" name="student_course" value="<?=  htmlspecialchars($student['student_course'])?>" required/>
                </p>

                <button type="submit">
                    Update
                </button>

                <a href="index.php?section=students">
                    Cancel
                </a>
            </form>

        <?php else: ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Course</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $student): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($student['student_id']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($student['student_first_name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($student['student_last_name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($student['student_course']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($student['student_created_at']) ?>
                            </td>
                            <td>
                                <a href="index.php?section=students&action=update&id=<?= $student['student_id'] ?>">Edit</a>
                                |
                                <a>Delete</a>
                            </td>

                        </tr>
                    <?php endforeach?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>

    <!--BOOKS SECTION-->
    <?php if($section==='books'): ?>

            <h2>Books</h2>

            <p>
                <a href="index.php?section=books&action=create">
                    Add Book
                </a>
            </p>

            <?php if ($action === 'create'): ?>

                <h3>Add Book</h3>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>" >
                    <p>
                        <label>Title:</label><br>
                        <input type="text" name="book_title" required>
                    </p>

                    <p>
                        <label>Author:</label><br>
                        <input type="text" name="book_author" required >
                    </p>

                    <p>
                        <label>Category:</label><br>
                        <input type="text" name="book_category"required>
                    </p>

                    <button type="submit">
                        Save
                    </button>

                    <a href="index.php?section=books">
                        Cancel
                    </a>

                </form>

            <?php elseif ($action === 'update'): ?>

                <h3>Update Book</h3>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>" >
                    <p>
                        <label>Title:</label><br>
                        <input type="text" name="book_title" value="<?= htmlspecialchars($book['book_title']) ?>" required>
                    </p>

                    <p>
                        <label>Author:</label><br>
                        <input
                            type="text"
                            name="book_author"
                            value="<?= htmlspecialchars($book['book_author']) ?>"
                            required
                        >
                    </p>

                    <p>
                        <label>Category:</label><br>
                        <input
                            type="text"
                            name="book_category"
                            value="<?= htmlspecialchars($book['book_category']) ?>"
                            required
                        >
                    </p>

                    <button type="submit">
                        Update
                    </button>

                    <a href="index.php?section=books">
                        Cancel
                    </a>

                </form>

            <?php else: ?>
                <table border="1" cellpadding="8">

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($books as $book): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($book['book_id']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($book['book_title']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($book['book_author']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($book['book_category']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($book['book_created_at']) ?>
                                </td>

                                <td>

                                    <a href="index.php?section=books&action=update&id=<?= $book['book_id'] ?>">
                                        Edit
                                    </a>

                                    |

                                    <a
                                        href="index.php?section=books&action=delete&id=<?= $book['book_id'] ?>"
                                        onclick="return confirm('Delete this book?');"
                                    >
                                        Delete
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

    <?php endif; ?>
    

    <!--BORROW SECTION-->
    <?php if($section==='borrow'): ?>
        <h1>Borrow</h1>
        <p>
            <a href="index.php?section=borrow&action=create">
            Borrow a Book  
            </a>
        </p>

        <?php if($action==='create'):?>
            <h3>Borrow a Book</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>" >
                <p>
                    <label>Student: </label>
                    <br>
                    <select name="student_id" required>
                        <option value="">
                            -- Select Student --
                        </option>

                        <?php foreach($students as $student): ?>

                                <option value=" <?=  $student['student_id'] ?> ">
                                    <?= htmlspecialchars(
                                        $student['student_first_name']
                                        .' '.
                                        $student['student_last_name']
                                    )?>
                                </option>

                        <?php endforeach;?>

                    </select>
                </p>

                <p>
                    <label>Book: </label>
                    <br>
                    <select name="book_id" required>
                        <option value="">
                            -- Select Book --
                        </option>

                        <?php foreach($books as $book): ?>

                                <option value=" <?=  $book['book_id'] ?> ">
                                    <?= htmlspecialchars(
                                        $book['book_title']
                                        .' - '.
                                        $book['book_author']
                                    )?>
                                </option>

                        <?php endforeach;?>

                    </select>
                </p>
                <p>
                    <label>Due Date:</label>
                    <br>

                    <input
                        type="date"
                        name="borrow_due_date"
                        min="<?= date('Y-m-d') ?>"
                        required
                    >
                </p>
                <button type="submit">
                    Borrow
                </button>
                <a href="index.php?section=borrow">
                    Cancel
                </a>
            </form>

        <?php else: ?>
            <table border="1" cellpadding="8">

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Book</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($borrowRecords as $borrow): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($borrow['borrow_id']) ?>
                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    $borrow['student_first_name']
                                    . ' '
                                    . $borrow['student_last_name']
                                ) ?>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    $borrow['book_title']
                                ) ?>

                                -
                                <?= htmlspecialchars(
                                    $borrow['book_author']
                                ) ?>

                            </td>

                            <td>
                                <?= htmlspecialchars($borrow['borrow_date']) ?>
                            </td>

                            <td>
                                <?php
                                $today = new DateTime();
                                $dueDate = new DateTime($borrow['borrow_due_date']);

                                if (
                                    empty($borrow['borrow_return_date']) &&
                                    $dueDate < $today
                                ) {
                                    $daysOverdue = $today->diff($dueDate)->days;

                                    echo '<strong style="color: red;">Overdue by '
                                        . $daysOverdue
                                        . ' day(s)</strong>';
                                } else {
                                    echo htmlspecialchars($borrow['borrow_due_date']);
                                }
                                ?>
                            </td>

                            <td>

                                <?php if ($borrow['borrow_return_date']): ?>

                                    <?= htmlspecialchars(
                                        $borrow['borrow_return_date']
                                    ) ?>

                                <?php else: ?>

                                    Not returned

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($borrow['borrow_return_date']): ?>

                                    Returned

                                <?php else: ?>

                                    Borrowed

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if (!$borrow['borrow_return_date']): ?>

                                    <a
                                        href="index.php?section=borrow&action=return&id=<?= $borrow['borrow_id'] ?>"
                                        onclick="return confirm('Mark this book as returned?');"
                                    >
                                        Return
                                    </a>

                                    |

                                <?php endif; ?>


                                <a
                                    href="index.php?section=borrow&action=delete&id=<?= $borrow['borrow_id'] ?>"
                                    onclick="return confirm('Delete this borrow record?');"
                                >
                                    Delete
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>
        <?php endif;?>
        
    <?php endif; ?>
</body>


<?php if (isset($_SESSION['alert'])): ?>

    <script>
        alert(<?=json_encode($_SESSION['alert'])?>);
    </script>

    <?php unset($_SESSION['alert']); ?>

<?php endif; ?>

</html>