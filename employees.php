<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success_text_message = '';
$error_text_message = '';

if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_text_message = 'Operation completed successfully.';
}

if (isset($_GET['delete_id'])) {
    $delete_employee_id = (int)$_GET['delete_id'];
    
    $select_pic_query = "SELECT profile_pic FROM employees WHERE id = $delete_employee_id LIMIT 1";
    $select_pic_result = mysqli_query($connection, $select_pic_query);
    if ($select_pic_result) {
        $employee_row = mysqli_fetch_assoc($select_pic_result);
        if ($employee_row && $employee_row['profile_pic'] && file_exists('uploads/' . $employee_row['profile_pic'])) {
            unlink('uploads/' . $employee_row['profile_pic']);
        }
    }

    $delete_query = "DELETE FROM employees WHERE id = $delete_employee_id";
    if (mysqli_query($connection, $delete_query)) {
        header("Location: employees.php?success=1");
        exit();
    } else {
        $error_text_message = 'Failed to delete employee: ' . mysqli_error($connection);
    }
}

$select_depts_result = mysqli_query($connection, "SELECT name FROM departments ORDER BY name ASC");
$departments_list = [];
if ($select_depts_result) {
    while ($row = mysqli_fetch_assoc($select_depts_result)) {
        $departments_list[] = $row['name'];
    }
}

$filter_department_name = isset($_GET['department']) ? trim($_GET['department']) : '';
$filter_status_name = isset($_GET['status']) ? trim($_GET['status']) : '';

$filter_conditions = [];
if ($filter_department_name !== '') {
    $filter_conditions[] = "department = '" . mysqli_real_escape_string($connection, $filter_department_name) . "'";
}
if ($filter_status_name !== '') {
    $filter_conditions[] = "status = '" . mysqli_real_escape_string($connection, $filter_status_name) . "'";
}

$filter_sql_string = '';
if (!empty($filter_conditions)) {
    $filter_sql_string = ' WHERE ' . implode(' AND ', $filter_conditions);
}

$count_result = mysqli_query($connection, "SELECT COUNT(*) as total_count FROM employees" . $filter_sql_string);
$total_matching_records = 0;
if ($count_result) {
    $count_row = mysqli_fetch_assoc($count_result);
    $total_matching_records = $count_row['total_count'];
}

$records_per_page = 5;
$total_pagination_pages = ceil($total_matching_records / $records_per_page);
if ($total_pagination_pages < 1) $total_pagination_pages = 1;

$current_page_number = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page_number < 1) $current_page_number = 1;
if ($current_page_number > $total_pagination_pages) $current_page_number = $total_pagination_pages;

$query_offset_value = ($current_page_number - 1) * $records_per_page;

$select_employees_query = "SELECT * FROM employees" . $filter_sql_string . " ORDER BY name ASC LIMIT $records_per_page OFFSET $query_offset_value";
$select_employees_result = mysqli_query($connection, $select_employees_query);

$employee_list_data = [];
if ($select_employees_result) {
    while ($row = mysqli_fetch_assoc($select_employees_result)) {
        $employee_list_data[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees Directory - StaffFlow</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-left">
            <span class="navbar-brand">StaffFlow</span>
            <ul class="navbar-nav">
                <li><a href="employees.php" class="active">Employees</a></li>
                <li><a href="add-employee.php">Add Employee</a></li>
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
                <h1>Employee Directory</h1>
                <p>Manage and organize registered staff members.</p>
            </div>
            <div>
                <a href="add-employee.php" class="btn btn-primary">Add Employee</a>
            </div>
        </div>

        <?php if (!empty($success_text_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_text_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_text_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_text_message); ?></div>
        <?php endif; ?>

        <form method="GET" action="employees.php" class="filters-bar">
            <select name="department" class="filter-select">
                <option value="">All Departments</option>
                <?php foreach ($departments_list as $single_department): ?>
                    <option value="<?php echo htmlspecialchars($single_department); ?>" <?php echo $filter_department_name === $single_department ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($single_department); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="Active" <?php echo $filter_status_name === 'Active' ? 'selected' : ''; ?>>Active</option>
                <option value="Leave" <?php echo $filter_status_name === 'Leave' ? 'selected' : ''; ?>>Leave</option>
                <option value="Inactive" <?php echo $filter_status_name === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>

            <button type="submit" class="btn btn-secondary">Filter</button>
            <?php if ($filter_department_name !== '' || $filter_status_name !== ''): ?>
                <a href="employees.php" class="btn btn-danger">Clear</a>
            <?php endif; ?>
        </form>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Salary</th>
                        <th>Status</th>
                        <th>Join Date</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employee_list_data)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #64748b; padding: 40px 0;">
                                No employees found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employee_list_data as $single_employee): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($single_employee['name']); ?></strong><br>
                                    <small style="color: #64748b;"><?php echo htmlspecialchars($single_employee['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($single_employee['department']); ?></td>
                                <td><?php echo htmlspecialchars($single_employee['position']); ?></td>
                                <td>₹<?php echo number_format($single_employee['salary'], 2); ?></td>
                                <td>
                                    <span class="badge <?php 
                                        echo $single_employee['status'] === 'Active' ? 'badge-active' : 
                                            ($single_employee['status'] === 'Leave' ? 'badge-leave' : 'badge-inactive'); 
                                    ?>">
                                        <?php echo htmlspecialchars($single_employee['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($single_employee['join_date']); ?></td>
                                <td style="text-align: right;">
                                    <a href="edit-employee.php?id=<?php echo $single_employee['id']; ?>" class="btn-icon">Edit</a>
                                    <a href="employees.php?delete_id=<?php echo $single_employee['id']; ?>" class="btn-icon danger" onclick="return confirm('Are you sure you want to delete this employee?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pagination_pages > 1): ?>
            <div class="pagination">
                <div class="pagination-info">
                    Showing page <?php echo $current_page_number; ?> of <?php echo $total_pagination_pages; ?>
                </div>
                <div class="pagination-buttons">
                    <a href="employees.php?page=<?php echo $current_page_number - 1; ?>&department=<?php echo urlencode($filter_department_name); ?>&status=<?php echo urlencode($filter_status_name); ?>" 
                       class="pagination-btn <?php echo $current_page_number <= 1 ? 'disabled' : ''; ?>">
                        Prev
                    </a>
                    <a href="employees.php?page=<?php echo $current_page_number + 1; ?>&department=<?php echo urlencode($filter_department_name); ?>&status=<?php echo urlencode($filter_status_name); ?>" 
                       class="pagination-btn <?php echo $current_page_number >= $total_pagination_pages ? 'disabled' : ''; ?>">
                        Next
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
