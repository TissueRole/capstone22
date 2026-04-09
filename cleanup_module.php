<?php
// Direct connection with InfinityFree credentials
$conn = new mysqli("sql103.infinityfree.com", "if0_41202562", "zDjx0iTnF2EyZ", "if0_41202562_capstone");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

$keyword = 'pogi reese';

// Find the module first
$stmt = $conn->prepare("SELECT module_id, title FROM modules WHERE title LIKE ?");
$search = '%' . $keyword . '%';
$stmt->bind_param("s", $search);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<h2>❌ Module not found</h2>";
    echo "<p>No module title containing '<strong>$keyword</strong>' was found.</p>";
    $stmt->close();
    exit;
}

echo "<h2>🔍 Found module(s):</h2><ul>";
$found = [];
while ($row = $result->fetch_assoc()) {
    echo "<li>ID: <strong>{$row['module_id']}</strong> — {$row['title']}</li>";
    $found[] = $row['module_id'];
}
echo "</ul>";
$stmt->close();

foreach ($found as $module_id) {
    // Delete related lessons
    $del_lessons = $conn->prepare("DELETE FROM lessons WHERE module_id = ?");
    $del_lessons->bind_param("i", $module_id);
    $del_lessons->execute();
    $lcount = $del_lessons->affected_rows;
    $del_lessons->close();

    // Delete related certificates
    $del_certs = $conn->prepare("DELETE FROM certificates WHERE module_id = ?");
    $del_certs->bind_param("i", $module_id);
    $del_certs->execute();
    $ccount = $del_certs->affected_rows;
    $del_certs->close();

    // Delete the module itself
    $del_mod = $conn->prepare("DELETE FROM modules WHERE module_id = ?");
    $del_mod->bind_param("i", $module_id);
    $del_mod->execute();
    $del_mod->close();

    echo "<p>✅ Deleted module ID <strong>$module_id</strong> — {$lcount} lesson(s) and {$ccount} certificate(s) removed.</p>";
}

$conn->close();
?>
