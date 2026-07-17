<?php
require_once 'config.php';

// Fetch all resources from MySQL
$sql = "SELECT * FROM files ORDER BY uploaded_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classroom Resource Center</title>
    <style>
        /* Embedded CSS styling for index.php */
        :root {
            --primary: #4a6fa5;
            --primary-dark: #3b5a84;
            --bg: #f4f6f9;
            --card-bg: #ffffff;
            --text: #333333;
            --text-muted: #666666;
            --border-color: #dddddd;
            --success: #2ec4b6;
            --accent: #e07a5f;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            --shadow-hover: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
            padding: 40px 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            color: var(--primary-dark);
            margin-bottom: 30px;
            font-size: 2.2rem;
        }

        a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        a:hover {
            color: var(--primary-dark);
        }

        .search-box {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 15px;
            outline: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        input[type="text"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.15);
        }

        button {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            cursor: pointer;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        button:active {
            transform: translateY(0);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .card-item {
            transition: transform 0.2s ease;
        }

        .card-item:hover {
            transform: translateY(-4px);
        }

        .card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            border-left: 6px solid var(--primary);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }

        .card.past-question {
            border-left-color: var(--accent);
        }

        .card h3 {
            margin: 15px 0 8px 0;
            font-size: 1.25rem;
            color: #222222;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: fit-content;
        }

        .badge.slide {
            background: #e1ecf7;
            color: var(--primary-dark);
        }

        .badge.pq {
            background: #fdf0ed;
            color: var(--accent);
        }

        .badge.other {
            background: #e2e8f0;
            color: #4a5568;
        }

        .download-link {
            margin-top: auto;
        }

        .download-link button {
            width: 100%;
            margin-top: 15px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Diploma Information Technology level 200 Resource Center</h1>
    
    <div style="text-align: right; margin-bottom: 25px;">
        <a href="upload.php"><button>+ Add New Slide / PQ</button></a>
    </div>

    <!-- Search Input -->
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search by course code, title, or type (e.g., Slide, Past Question)...">
    </div>

    <!-- Resources Grid -->
    <div class="grid" id="resourceGrid">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <?php 
                    $cardClass = ($row['category'] == 'Past Question') ? 'card past-question' : 'card';
                    $badgeClass = ($row['category'] == 'Slide') ? 'badge slide' : (($row['category'] == 'Past Question') ? 'badge pq' : 'badge other');
                ?>
                <div class="card-item" data-search="<?php echo strtolower($row['title'] . ' ' . $row['course_code'] . ' ' . $row['category']); ?>">
                    <div class="<?php echo $cardClass; ?>">
                        <div>
                            <span class="<?php echo $badgeClass; ?>"><?php echo $row['category']; ?></span>
                            <span style="float: right; font-weight: bold; color: #555;"><?php echo htmlspecialchars($row['course_code']); ?></span>
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <p style="font-size: 12px; color: var(--text-muted);">Uploaded: <?php echo date('M d, Y', strtotime($row['uploaded_at'])); ?></p>
                        </div>
                        <a href="uploads/<?php echo $row['file_name']; ?>" download class="download-link">
                            <button>Download File</button>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; grid-column: 1/-1; color: var(--text-muted);">No resources found. Be the first to upload one!</p>
        <?php endif; ?>
    </div>
</div>

<script>
    // Embedded Live Filter JS
    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById("searchInput");
        const cards = document.querySelectorAll(".card-item");

        searchInput.addEventListener("input", function(e) {
            const query = e.target.value.toLowerCase().trim();

            cards.forEach(card => {
                const searchData = card.getAttribute("data-search");
                if (searchData.includes(query)) {
                    card.style.display = "block";
                } else {
                    card.style.display = "none";
                }
            });
        });
    });
</script>
</body>
</html>