<?php
require_once 'config.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = htmlspecialchars($_POST['title']);
    $category = $_POST['category'];
    $course = htmlspecialchars(strtoupper($_POST['course_code']));
    
    $file = $_FILES['resource_file'];
    
    // Simple verification
    if ($file['error'] === 0) {
        $allowed = ['pdf', 'docx', 'pptx', 'ppt', 'zip'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Give it a safe, unique filename to prevent overwriting
            $new_filename = uniqid('FILE_', true) . "." . $ext;
            $destination = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // Insert details into database
                $stmt = $conn->prepare("INSERT INTO files (title, category, course_code, file_name) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $title, $category, $course, $new_filename);
                
                if ($stmt->execute()) {
                    $message = "<p style='color:green;'>Success! Material uploaded.</p>";
                } else {
                    $message = "<p style='color:red;'>Database error occurred.</p>";
                }
                $stmt->close();
            } else {
                $message = "<p style='color:red;'>Failed to move file.</p>";
            }
        } else {
            $message = "<p style='color:red;'>Only PDF, PPT, PPTX, DOCX, & ZIP files are allowed.</p>";
        }
    } else {
        $message = "<p style='color:red;'>Error uploading file.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Academic Resources</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>Upload Class Material</h1>
    <a href="index.php">← Back to Resources</a>
    <br><br>
    <?php echo $message; ?>
    <form action="upload.php" method="POST" enctype="multipart/form-data">
        <div class="input-group">
            <input type="text" name="title" placeholder="e.g. Intro to Databases - Week 1" required style="flex: 2;">
            <input type="text" name="course_code" placeholder="e.g. IT 204" required style="flex: 1;">
            <select name="category" required>
                <option value="Slide">Slide</option>
                <option value="Past Question">Past Question</option>
                <option value="Other">Other</option>
            </select>
            <input type="file" name="resource_file" required>
        </div>
        <button type="submit">Publish Resource</button>
    </form>
</div>
</body>
</html>