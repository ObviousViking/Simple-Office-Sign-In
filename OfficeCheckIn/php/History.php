<?php
include '../includes/DB_Connect.php';

// Check for CSV download request
if (isset($_POST['download_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="visitor_history.csv"');
    $output = fopen("php://output", "w");
    fputcsv($output, array('Name', 'Organization', 'Sign In Time', 'Sign Out Time'));

    $query = "SELECT name, organization, signInTime, signOutTime FROM visitors ORDER BY signInTime ASC";
    $result = $db->query($query);
    if ($result) {
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
    exit;
}


// Check if a date filter has been applied
$filterDate = isset($_POST['filterDate']) ? $_POST['filterDate'] : '';

if (!empty($filterDate)) {
    $sql = "SELECT id, name, organization, signInTime, signOutTime FROM visitors WHERE DATE(signInTime) = ? ORDER BY signInTime ASC";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(1, $filterDate, SQLITE3_TEXT);
    $result = $stmt->execute(); 
} else {
    $sql = "SELECT id, name, organization, signInTime, signOutTime FROM visitors ORDER BY signInTime ASC";
    $result = $db->query($sql);
}

$logs = [];
if ($result) {
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $logs[] = $row;
    }
}

if (isset($stmt)) {
    unset($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Visitor Log History</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #333;
            /* Dark background */
            color: #fff;
            /* Light text */
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            /* Light grey line for separation */
        }

        tr:hover {
            background-color: #666;
        }

        /* Darker row background on hover */
    </style>
</head>

<body>
    <h1>Visitor Log History</h1>
    <form action="" method="POST">
        <input type="date" name="filterDate" value="<?php echo htmlspecialchars($filterDate); ?>">
        <button type="submit">Filter by Date</button>

        <button type="submit" name="download_csv">Download CSV</button>

        <button type="button" onclick="history.back();" class="button-style">Go Back</button>

    </form>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Organization</th>
                <th>Sign In Time</th>
                <th>Sign Out Time</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['name']); ?></td>
                    <td><?php echo htmlspecialchars($log['organization']); ?></td>
                    <td><?php echo htmlspecialchars($log['signInTime']); ?></td>
                    <td><?php echo $log['signOutTime'] ? htmlspecialchars($log['signOutTime']) : "Visitor not yet signed out"; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <br><br><br>







</body>

</html>
