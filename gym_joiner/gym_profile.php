<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'gym_member') {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';
require_once 'ReviewController.php';

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$gym_id = $_GET['gym_id'] ?? 0;
if (!$gym_id) {
    header("Location: user_dashboard.php");
    exit();
}

// Fetch gym details
$stmt = $conn->prepare("SELECT * FROM gyms WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $gym_id);
$stmt->execute();
$gym = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$gym) {
    header("Location: user_dashboard.php");
    exit();
}

// Fetch gym images
$images = [];
$stmt = $conn->prepare("SELECT image_path FROM gym_images WHERE gym_id = ? ORDER BY uploaded_at DESC");
if ($stmt) {
    $stmt->bind_param("i", $gym_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $images[] = $row['image_path'];
    }
    $stmt->close();
}

// Fetch offers
$offers = [];
$stmt = $conn->prepare("SELECT * FROM offers WHERE gym_id = ? AND valid_until >= CURDATE() ORDER BY valid_until ASC");
if ($stmt) {
    $stmt->bind_param("i", $gym_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $offers[] = $row;
    }
    $stmt->close();
}

// Fetch trainers
$trainers = [];
$stmt = $conn->prepare("SELECT * FROM trainers WHERE gym_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $gym_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $trainers[] = $row;
    }
    $stmt->close();
}

// Fetch reviews
$reviews = ReviewController::getReviewsByGym($gym_id);

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = "Rating must be between 1 and 5.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND gym_id = ?");
        if (!$stmt) {
            $error = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("ii", $user_id, $gym_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $error = "You have already submitted a review for this gym.";
            } else {
                $stmt->close();
                $stmt = $conn->prepare("INSERT INTO reviews (gym_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
                if (!$stmt) {
                    $error = "Prepare failed: " . $conn->error;
                } else {
                    $stmt->bind_param("iiis", $gym_id, $user_id, $rating, $comment);
                    if ($stmt->execute()) {
                        $success = "Review submitted successfully.";
                        $reviews = ReviewController::getReviewsByGym($gym_id); // Refresh reviews
                    } else {
                        $error = "Error submitting review: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Website - <?php echo htmlspecialchars($gym['gym_name']); ?> Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #F5F5F5;
        }
        .navbar {
            background-color: #4B0082;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .navbar a {
            color: #FFFFFF;
            transition: color 0.3s;
        }
        .navbar a:hover {
            color: #FFC107;
        }
        .section {
            padding: 4rem 2rem;
        }
        .card {
            background-color: #FFFFFF;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .btn-primary {
            background-color: #FFC107;
            color: #4B0082;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #FFD700;
        }
        .image-preview {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 0.25rem;
            margin: 0.5rem;
        }
        .gender-symbol {
            font-size: 2rem;
            margin-left: 0.5rem;
        }
        .male { color: #0000FF; }
        .female { color: #FF69B4; }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar flex justify-between items-center px-6 py-4">
        <div class="text-2xl font-bold text-white">Gym Website</div>
        <div class="flex items-center space-x-4">
            <a href="user_dashboard.php" class="text-white hover:text-yellow-300">Dashboard</a>
            <a href="profile.php" class="text-white hover:text-yellow-300">Profile</a>
            <a href="logout.php" class="text-white hover:text-yellow-300">Logout</a>
        </div>
    </nav>

    <!-- Gym Profile Section -->
    <section class="section">
        <h2 class="text-3xl font-bold mb-6 text-center text-red-600"><?php echo htmlspecialchars($gym['gym_name']); ?> Profile</h2>
        <?php if (!empty($success)): ?>
            <p class="text-green-500 text-center mb-4"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <p class="text-red-500 text-center mb-4"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <div class="card">
            <h3 class="text-xl font-semibold mb-4">About the Gym</h3>
            <p><?php echo nl2br(htmlspecialchars($gym['description'] ?? 'No description available.')); ?></p>
            <p><strong>District:</strong> <?php echo htmlspecialchars($gym['district']); ?></p>
            <p><strong>Gender:</strong>
                <?php if ($gym['gender'] === 'male' || $gym['gender'] === 'both'): ?>
                    <span class="gender-symbol male">♂</span>
                <?php endif; ?>
                <?php if ($gym['gender'] === 'female' || $gym['gender'] === 'both'): ?>
                    <span class="gender-symbol female">♀</span>
                <?php endif; ?>
            </p>
            <p><strong>Facilities:</strong> <?php echo nl2br(htmlspecialchars($gym['facilities'] ?? 'No facilities listed.')); ?></p>
            <p><strong>Operating Hours:</strong> <?php echo htmlspecialchars($gym['operating_hours'] ?? 'Not specified.'); ?></p>
            <p><strong>Average Rating:</strong> <?php echo number_format(ReviewController::getAverageRating($gym_id), 1); ?> / 5</p>
        </div>

        <div class="card">
            <h3 class="text-xl font-semibold mb-4">Gym Images</h3>
            <?php if (empty($images)): ?>
                <p class="text-gray-600">No images available.</p>
            <?php else: ?>
                <div class="flex flex-wrap">
                    <?php foreach ($images as $image): ?>
                        <img src="<?php echo htmlspecialchars($image); ?>" alt="Gym Image" class="image-preview">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 class="text-xl font-semibold mb-4">Trainers</h3>
            <?php if (empty($trainers)): ?>
                <p class="text-gray-600">No trainers available.</p>
            <?php else: ?>
                <ul class="list-disc ml-6">
                    <?php foreach ($trainers as $trainer): ?>
                        <li>
                            <?php echo htmlspecialchars($trainer['name']); ?>
                            <?php if ($trainer['specialization']): ?>
                                (<?php echo htmlspecialchars($trainer['specialization']); ?>)
                            <?php endif; ?>
                            <?php if ($trainer['contact']): ?>
                                - Contact: <?php echo htmlspecialchars($trainer['contact']); ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 class="text-xl font-semibold mb-4">Offers</h3>
            <?php if (empty($offers)): ?>
                <p class="text-gray-600">No offers available.</p>
            <?php else: ?>
                <ul class="list-disc ml-6">
                    <?php foreach ($offers as $offer): ?>
                        <li>
                            <?php echo htmlspecialchars($offer['title']); ?> (<?php echo htmlspecialchars($offer['discount_percentage']); ?>% off)
                            <br>Valid until: <?php echo htmlspecialchars($offer['valid_until']); ?>
                            <?php if ($offer['description']): ?>
                                <br><?php echo htmlspecialchars($offer['description']); ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 class="text-xl font-semibold mb-4">Reviews</h3>
            <?php if (empty($reviews)): ?>
                <p class="text-gray-600">No reviews yet.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($reviews as $review): ?>
                        <div>
                            <p><strong><?php echo htmlspecialchars($review['user_name'] ?? 'Anonymous'); ?>:</strong> <?php echo htmlspecialchars($review['rating']); ?>/5</p>
                            <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            <?php if ($review['response']): ?>
                                <p class="text-gray-600"><strong>Owner Response:</strong> <?php echo nl2br(htmlspecialchars($review['response'])); ?></p>
                            <?php endif; ?>
                            <p class="text-sm text-gray-500">Posted on <?php echo htmlspecialchars($review['created_at']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 class="text-xl font-semibold mb-4">Submit Your Review</h3>
            <form method="POST" action="">
                <div class="mb-4">
                    <label for="rating" class="block text-gray-700">Rating (1-5)</label>
                    <input type="number" name="rating" id="rating" class="w-full p-2 border rounded" min="1" max="5" required>
                </div>
                <div class="mb-4">
                    <label for="comment" class="block text-gray-700">Comment</label>
                    <textarea name="comment" id="comment" class="w-full p-2 border rounded" rows="4"></textarea>
                </div>
                <button type="submit" name="submit_review" class="btn-primary w-full">Submit Review</button>
            </form>
        </div>
    </section>

    <!-- Footer Section -->
    <section class="section bg-white text-center">
        <p class="text-gray-600">&copy; 2025 Gym Website. All rights reserved.</p>
    </section>
</body>
</html>