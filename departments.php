<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success_text_message = '';
$error_text_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        $error_text_message = 'Department name cannot be empty.';
    } else {
        $safe_name = mysqli_real_escape_string($connection, $name);
        $insert_query = "INSERT INTO departments (name) VALUES ('$safe_name')";
        if (mysqli_query($connection, $insert_query)) {
            $success_text_message = 'Department created successfully.';
        } else {
            if (mysqli_errno($connection) == 1062) {
                $error_text_message = 'A department with this name already exists.';
            } else {
                $error_text_message = 'Failed to create department: ' . mysqli_error($connection);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        $error_text_message = 'Department name cannot be empty.';
    } else {
        $safe_name = mysqli_real_escape_string($connection, $name);
        
        $select_query = mysqli_query($connection, "SELECT name FROM departments WHERE id = $id LIMIT 1");
        $old_row = mysqli_fetch_assoc($select_query);

        if ($old_row) {
            $old_name = $old_row['name'];
            $safe_old_name = mysqli_real_escape_string($connection, $old_name);

            if (mysqli_query($connection, "UPDATE departments SET name = '$safe_name' WHERE id = $id")) {
                mysqli_query($connection, "UPDATE employees SET department = '$safe_name' WHERE department = '$safe_old_name'");
                $success_text_message = 'Department updated successfully.';
            } else {
                if (mysqli_errno($connection) == 1062) {
                    $error_text_message = 'A department with this name already exists.';
                } else {
                    $error_text_message = 'Failed to update department: ' . mysqli_error($connection);
                }
            }
        } else {
            $error_text_message = 'Department not found.';
        }
    }
}

if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $select_query = mysqli_query($connection, "SELECT name FROM departments WHERE id = $delete_id LIMIT 1");
    $row = mysqli_fetch_assoc($select_query);

    if ($row) {
        $dept_name = $row['name'];
        $safe_dept_name = mysqli_real_escape_string($connection, $dept_name);

        if (mysqli_query($connection, "DELETE FROM departments WHERE id = $delete_id")) {
            mysqli_query($connection, "UPDATE employees SET department = 'Unassigned' WHERE department = '$safe_dept_name'");
            $success_text_message = 'Department deleted successfully.';
        } else {
            $error_text_message = 'Failed to delete department: ' . mysqli_error($connection);
        }
    }
}

$query = "SELECT d.id, d.name, COUNT(e.id) as employee_count 
          FROM departments d 
          LEFT JOIN employees e ON e.department = d.name 
          GROUP BY d.id, d.name 
          ORDER BY d.name ASC";
$result = mysqli_query($connection, $query);
$all_departments_list = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $all_departments_list[] = $row;
    }
}

$department_being_edited = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    foreach ($all_departments_list as $single_department) {
        if ($single_department['id'] == $edit_id) {
            $department_being_edited = $single_department;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments - StaffFlow</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-left">
            <span class="navbar-brand">StaffFlow</span>
            <ul class="navbar-nav">
                <li><a href="employees.php">Employees</a></li>
                <li><a href="add-employee.php">Add Employee</a></li>
                <li><a href="departments.php" class="active">Departments</a></li>
            </ul>
        </div>
        <div>
            <a href="logout.php" class="navbar-logout-btn">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="top-bar">
            <div class="top-bar-title">
                <h1>Department Management</h1>
                <p>Manage company departments and their employee assignments.</p>
            </div>
        </div>

        <?php if (!empty($success_text_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_text_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_text_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_text_message); ?></div>
        <?php endif; ?>

        <div class="grid-2">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Department Name</th>
                            <th>Employees</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_departments_list)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #64748b; padding: 20px 0;">
                                    No departments defined.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_departments_list as $single_department): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($single_department['name']); ?></strong></td>
                                    <td><?php echo $single_department['employee_count']; ?> employees</td>
                                    <td style="text-align: right;">
                                        <a href="departments.php?edit_id=<?php echo $single_department['id']; ?>" class="btn-icon">Edit</a>
                                        <a href="departments.php?delete_id=<?php echo $single_department['id']; ?>" class="btn-icon danger" onclick="return confirm('Are you sure you want to delete this department?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <?php if ($department_being_edited): ?>
                    <div class="card-title">Edit Department</div>
                    <form action="departments.php" method="POST">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?php echo $department_being_edited['id']; ?>">
                        
                        <div class="form-group">
                            <label for="name">Department Name</label>
                            <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($department_being_edited['name']); ?>" required>
                        </div>
                        
                        <div class="form-actions">
                            <a href="departments.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="card-title">Add Department</div>
                    <form action="departments.php" method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-group">
                            <label for="name">Department Name</label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Finance" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Add Department</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
