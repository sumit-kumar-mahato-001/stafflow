<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error_text_message = '';

$select_depts_result = mysqli_query($connection, "SELECT name FROM departments ORDER BY name ASC");
$departments_list = [];
if ($select_depts_result) {
    while ($row = mysqli_fetch_assoc($select_depts_result)) {
        $departments_list[] = $row['name'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $salary = trim($_POST['salary'] ?? '');
    $join_date = trim($_POST['join_date'] ?? '');
    $status = trim($_POST['status'] ?? '');

    if (empty($name) || empty($email) || empty($department) || empty($position) || empty($salary) || empty($join_date) || empty($status)) {
        $error_text_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_text_message = 'Please enter a valid email address.';
    } elseif (!is_numeric($salary) || $salary < 0) {
        $error_text_message = 'Please enter a valid salary amount.';
    } else {
        $safe_email = mysqli_real_escape_string($connection, $email);
        $check_result = mysqli_query($connection, "SELECT id FROM employees WHERE email = '$safe_email' LIMIT 1");
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $error_text_message = 'An employee with this email address already exists.';
        } else {
            $safe_name = mysqli_real_escape_string($connection, $name);
            $safe_phone = empty($phone) ? "NULL" : "'" . mysqli_real_escape_string($connection, $phone) . "'";
            $safe_dept = mysqli_real_escape_string($connection, $department);
            $safe_position = mysqli_real_escape_string($connection, $position);
            $safe_salary = (float)$salary;
            $safe_join_date = mysqli_real_escape_string($connection, $join_date);
            $safe_status = mysqli_real_escape_string($connection, $status);

            $insert_query = "INSERT INTO employees (name, email, phone, department, position, salary, join_date, status, profile_pic) 
                             VALUES ('$safe_name', '$safe_email', $safe_phone, '$safe_dept', '$safe_position', $safe_salary, '$safe_join_date', '$safe_status', NULL)";
            
            if (mysqli_query($connection, $insert_query)) {
                header("Location: employees.php?success=1");
                exit();
            } else {
                $error_text_message = 'Failed to add employee: ' . mysqli_error($connection);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee - StaffFlow</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-left">
            <span class="navbar-brand">StaffFlow</span>
            <ul class="navbar-nav">
                <li><a href="employees.php">Employees</a></li>
                <li><a href="add-employee.php" class="active">Add Employee</a></li>
                <li><a href="departments.php">Departments</a></li>
            </ul>
        </div>
        <div>
            <a href="logout.php" class="navbar-logout-btn">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="top-bar">
            <div class="top-bar-title">
                <h1>Add New Employee</h1>
                <p>Create a profile for a new staff member.</p>
            </div>
            <div>
                <a href="employees.php" class="btn btn-secondary">Back to Directory</a>
            </div>
        </div>

        <?php if (!empty($error_text_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_text_message); ?></div>
        <?php endif; ?>

        <div class="card">
            <form action="add-employee.php" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" name="name" id="name" class="form-control" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" name="email" id="email" class="form-control" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" name="phone" id="phone" class="form-control" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="department">Department *</label>
                        <select name="department" id="department" class="form-control" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments_list as $single_department): ?>
                                <option value="<?php echo htmlspecialchars($single_department); ?>" <?php echo (isset($_POST['department']) && $_POST['department'] === $single_department) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($single_department); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="position">Position / Role *</label>
                        <input type="text" name="position" id="position" class="form-control" required value="<?php echo isset($_POST['position']) ? htmlspecialchars($_POST['position']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="salary">Annual Salary (INR) *</label>
                        <input type="number" step="0.01" name="salary" id="salary" class="form-control" required value="<?php echo isset($_POST['salary']) ? htmlspecialchars($_POST['salary']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="join_date">Join Date *</label>
                        <input type="date" name="join_date" id="join_date" class="form-control" required value="<?php echo isset($_POST['join_date']) ? htmlspecialchars($_POST['join_date']) : date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="status">Employment Status *</label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="Active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Leave" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Leave') ? 'selected' : ''; ?>>Leave</option>
                            <option value="Inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="employees.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Employee</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
