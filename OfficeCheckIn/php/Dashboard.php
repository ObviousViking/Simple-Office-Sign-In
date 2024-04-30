<?php
include '../includes/DB_Connect.php';

date_default_timezone_set('Europe/London');

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['name'], $_POST['organization'])) {
    // Convert name and organization to uppercase
    $name = strtoupper($db->escapeString($_POST['name']));
    $organization = strtoupper($db->escapeString($_POST['organization']));
    $signInTime = date('Y-m-d H:i:s'); // Capture the current time as sign-in time

    // Insert the visitor into the database
    $sql = "INSERT INTO visitors (name, organization, signInTime) VALUES ('$name', '$organization', '$signInTime')";
    if ($db->exec($sql)) {
        // Successfully inserted
    } else {
        echo "<p>Error: " . $sql . "<br>" . $db->lastErrorMsg() . "</p>";
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch currently signed-in visitors
$sql = "SELECT id, name, organization FROM visitors WHERE signOutTime IS NULL";
$result = $db->query($sql);

$visitors = [];
if ($result) {
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $visitors[] = $row;
    }
}

$num_rows = count($visitors);  // To use instead of $result->num_rows


$signOutMessage = ''; // Initialize a variable to hold sign-out messages

// Check for sign-out action
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['signOutId'])) {
    $id = $db->escapeString($_GET['signOutId']);  // Correct method to sanitize the input
    $signOutTime = date('Y-m-d H:i:s');

    $sql = "UPDATE visitors SET signOutTime = '$signOutTime' WHERE id = $id AND signOutTime IS NULL";
    if ($db->exec($sql)) {  // Use exec() for non-query SQL statements
        $signOutMessage = "Visitor signed out successfully.";
    } else {
        $signOutMessage = "Error signing out: " . $db->lastErrorMsg();  // Use lastErrorMsg() to get the error message
    }
    header('Location: Dashboard.php'); // Redirect back to the dashboard
    exit();
}


// Calculate paper savings
// Fetch the total number of entries in the database
$sql = "SELECT COUNT(*) AS total_entries FROM visitors";
$result = $db->query($sql);
$totalEntries = 0;
if ($result) {
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $totalEntries = $row['total_entries'];
}

$entriesPerSheet = 28;
$sheetsSaved = intdiv($totalEntries, $entriesPerSheet);

// Analytics
// Initialize $busiestDay with default values
$busiestDay = ['day' => 'No visitor data available', 'visits' => 0];

// Check the total number of entries first
$totalCountQuery = "SELECT COUNT(*) AS total FROM visitors";
$totalCountResult = $db->query($totalCountQuery);
$totalCountRow = $totalCountResult->fetchArray(SQLITE3_ASSOC);

if ($totalCountRow['total'] > 0) {
    // Only run the busiest day query if there are entries in the database
    $busiestDayQuery = "SELECT strftime('%w', signInTime) AS day, COUNT(*) AS visits 
                        FROM visitors 
                        GROUP BY strftime('%w', signInTime) 
                        ORDER BY visits DESC LIMIT 1";
    $busiestDayResult = $db->query($busiestDayQuery);
    if ($busiestDayResult) {
        $row = $busiestDayResult->fetchArray(SQLITE3_ASSOC);
        if ($row) {
            $dayNames = ['Sundays', 'Mondays', 'Tuesdays', 'Wednesdays', 'Thursdays', 'Fridays', 'Saturdays'];
            $busiestDay['day'] = $dayNames[$row['day']];
            $busiestDay['visits'] = $row['visits'];
        }
    }
}


// Works out the total number of vistors
$totalVisitorsQuery = "SELECT COUNT(*) AS total_visitors FROM visitors";
$totalVisitorsResult = $db->query($totalVisitorsQuery);
$totalVisitors = [];
if ($totalVisitorsResult) {
    $totalVisitors = $totalVisitorsResult->fetchArray(SQLITE3_ASSOC);
}
// Works out the average length a vistor stays
$averageDurationMinutes = 0;
if ($totalCountRow['total'] > 1) {
    $averageDurationQuery = "SELECT AVG((julianday(signOutTime) - julianday(signInTime)) * 1440) AS average_duration FROM visitors WHERE signOutTime IS NOT NULL";
    $averageDurationResult = $db->query($averageDurationQuery);
    $averageDuration = [];
    if ($averageDurationResult) {
        $averageDuration = $averageDurationResult->fetchArray(SQLITE3_ASSOC);
        $averageDuration['average_duration'] = round($averageDuration['average_duration']);
    }

    // Format duration to more readable format if needed
    $averageDurationMinutes = round($averageDuration['average_duration']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Check-In</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <header>
        <h1>Check-In</h1>
    </header>

    <div id="dashboard">

        <div class="main-content">
            <section id="sign-in-form">

                <form action="" method="POST" id="signInForm">
                    <div class="form-row">
                        <div class="inputs-container">
                            <div class="input-group">
                                <label for="name">Name:</label>
                                <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                            </div>
                            <div class="input-group">
                                <label for="organization">Organization:</label>
                                <input type="text" id="organization" name="organization"
                                    placeholder="Enter your organization" required>
                            </div>
                        </div>
                        <button type="submit" class="button-style">
                            <img src="../images/sign-in-icon.png" alt="Sign In">
                        </button>
                    </div>
                </form>

            </section>
            <section id="current-visitors">
                <h2>Current Vistors</h2>
                <div class="visitor-grid">
                    <?php if (!empty($visitors)): ?>
                        <?php foreach ($visitors as $visitor): ?>
                            <div class="visitor-entry">
                                <div class="visitor-info">
                                    <span class="visitor-name"><?php echo htmlspecialchars($visitor['name']); ?></span>
                                    <span
                                        class="visitor-organization"><?php echo htmlspecialchars($visitor['organization']); ?></span>
                                </div>
                                <button class="sign-out-btn"
                                    onclick="location.href='?signOutId=<?php echo $visitor['id']; ?>'">Sign
                                    Out</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No visitors currently signed in.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="side-content">
            <div id="environmentalImpact">
                <h2>Environmental Impact</h2>
                <p>We've saved approximately <strong><?php echo htmlspecialchars($sheetsSaved); ?></strong> sheets of
                    paper
                    by using
                    this digital sign-in system!</p>
            </div>
            <div id="visitorAnalytics">
                <h3>Visitor Analytics</h3>
                <p><strong>Busiest Day:</strong> <?php echo $busiestDay['day']; ?></p>
                <p><strong>Total Visitors:</strong> <?php echo $totalVisitors['total_visitors']; ?></p>
                <p><strong>Average Visit Length:</strong> <?php echo $averageDurationMinutes; ?> minutes</p>
            </div>
            <div class="history-link">
                <button onclick="location.href='history.php'">View History</button>
            </div>
        </div>
    </div>

</body>

</html>
