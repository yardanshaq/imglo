<?php
session_start();

// Ensure gallery session exists
if(!isset($_SESSION['gallery'])) { $_SESSION['gallery'] = []; }

// ===== CloudImages - Secure Image Hosting Platform =====
$title = 'imglo';
$subtitle = 'Secure image hosting platform';
$uploadDir = 'lo';
$maxFileSize = 10 * 1024 * 1024; // 10 MB
$allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
$allowedMimeTypes = ['image/png', 'image/jpeg', 'image/pjpeg', 'image/gif', 'image/webp'];

function formatDate($timestamp) {
    $now = time();
    $diff = $now - $timestamp;
    $days = floor($diff / (60 * 60 * 24));
    
    if ($days === 0) {
        return 'Today';
    } elseif ($days === 1) {
        return 'Yesterday';
    } elseif ($days <= 7) {
        return $days . ' days ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($title); ?> | 404 Not Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='64' height='64' viewBox='0 0 24 24' fill='none' stroke='%234f46e5'%3E%3Cpath d='M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z'/%3E%3C/svg%3E">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: #0f0f23; }
        .gradient-bg { background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e1b4b 100%); }
        .cloud-logo {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #3b82f6 100%);
            box-shadow: 0 4px 20px rgba(79, 70, 229, 0.3);
        }
        .cloud-logo:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 25px rgba(79, 70, 229, 0.4);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col text-white gradient-bg">
    
    <!-- Header (tetap di atas) -->
    <header class="w-full max-w-7xl mx-auto px-4 pt-6">
        <div class="flex items-center gap-4">
            <a href="/" class="cloud-logo h-12 w-12 rounded-2xl flex items-center justify-center transition-all duration-300 cursor-pointer hover:scale-105">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($title); ?></h1>
                <p class="text-sm text-gray-300"><?php echo htmlspecialchars($subtitle); ?></p>
            </div>
        </div>
    </header>

    <!-- Konten 404 -->
    <main class="flex-grow flex items-center justify-center">
        <div class="text-center">
            <div class="flex justify-center mb-6">
                <div class="cloud-logo h-20 w-20 rounded-3xl flex items-center justify-center transition-all duration-500 hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                    </svg>
                </div>
            </div>
            <h2 class="text-5xl font-bold text-white mb-4">404</h2>
            <p class="text-lg text-gray-400 mb-6">Oops! The page you are looking for does not exist.</p>
            <a href="/" class="px-6 py-3 bg-[#7B4DFF] rounded-xl shadow-md hover:bg-[#6a3de8] transition">
                ← Back to Home
            </a>
        </div>
    </main>

    <!-- Footer -->
    <footer class="mt-auto pb-6 text-center">
        <div class="flex items-center justify-center gap-2 mb-2">
            <div class="cloud-logo h-6 w-6 rounded-lg flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                </svg>
            </div>
            <span class="text-gray-400 font-medium"><?php echo htmlspecialchars($title); ?></span>
        </div>
        <p class="text-gray-500 text-sm">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($title); ?>. All rights reserved.</p>
    </footer>
</body>
</html>