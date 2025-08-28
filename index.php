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

// Helper: build base URL
function getBaseUrl() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return $host . $basePath;
}

// Create upload directory if it doesn't exist
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// Initialize session gallery if not exists
if (!isset($_SESSION['gallery'])) {
    $_SESSION['gallery'] = [];
}

// Helper functions
function cleanFilename($name) {
    $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
    return preg_replace('/_{2,}/', '_', $name);
}

function generateRandomFilename($originalName) {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    try {
        return bin2hex(random_bytes(8)) . '.' . $ext;
    } catch (Exception $e) {
        return uniqid() . '.' . $ext;
    }
}

function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round(($bytes / pow($k, $i)), 1) . ' ' . $sizes[$i];
}

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

function getTotalStorageUsed($gallery) {
    $total = 0;
    foreach ($gallery as $image) {
        $total += $image['size'] ?? 0;
    }
    return $total;
}

function getThisMonthUploads($gallery) {
    $thisMonth = 0;
    $currentMonth = date('Y-m');
    foreach ($gallery as $image) {
        if (isset($image['uploaded_at']) && date('Y-m', $image['uploaded_at']) === $currentMonth) {
            $thisMonth++;
        }
    }
    return $thisMonth;
}

// Helper: render gallery HTML (same markup used in page)
function renderGalleryHtml($gallery, $uploadDir) {
    ob_start();
    if (empty($gallery)) {
        ?>
        <div class="card-dark rounded-2xl p-12 text-center">
            <div class="cloud-logo h-16 w-16 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-white mb-2">Your cloud is empty</h3>
            <p class="text-gray-400 mb-4">Upload your first image to get started</p>
            <a href="?view=upload" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                </svg>
                Upload Images
            </a>
        </div>
        <?php
    } else {
        ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            <?php foreach ($gallery as $image): ?>
            <div class="gallery-item group relative rounded-xl overflow-hidden bg-gray-800/50 border border-gray-700/50 hover:border-indigo-500/50 transition-all cursor-pointer">
                <!-- Image -->
                <div class="aspect-square bg-gray-800">
                    <img src="<?php echo htmlspecialchars($uploadDir . '/' . $image['name']); ?>" 
                         alt="<?php echo htmlspecialchars($image['original_name'] ?? $image['name']); ?>" 
                         class="w-full h-full object-cover"
                         loading="lazy">
                </div>
                
                <!-- Overlay -->
                <div class="gallery-overlay absolute inset-0 bg-black/70 backdrop-blur-sm flex flex-col justify-center items-center gap-3">
                    <a href="<?php echo htmlspecialchars($image['url']); ?>" target="_blank" rel="noopener noreferrer" 
                       class="flex items-center gap-2 px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                            <polyline points="15,3 21,3 21,9"/>
                            <line x1="10" y1="14" x2="21" y2="3"/>
                        </svg>
                        View
                    </a>
                    <button data-copy="<?php echo htmlspecialchars($image['url']); ?>" 
                            class="copy-url flex items-center gap-2 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                        </svg>
                        Copy
                    </button>
                </div>
                
                <!-- Delete Button (keep href for non-JS fallback) -->
                <a href="?delete=<?php echo urlencode($image['id']); ?>&view=gallery" 
                   data-id="<?php echo htmlspecialchars($image['id']); ?>"
                   class="ajax-delete absolute top-2 right-2 h-8 w-8 bg-red-500/80 hover:bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all z-10"
                   title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </a>
                
                <!-- Image Info -->
                <div class="absolute bottom-0 left-0 right-0 p-3 bg-gradient-to-t from-black/80 to-transparent">
                    <p class="text-white text-sm font-medium truncate"><?php echo htmlspecialchars($image['name']); ?></p>
                    <div class="flex justify-between items-center mt-1 text-xs text-gray-300">
                        <span><?php echo formatFileSize($image['size'] ?? 0); ?></span>
                        <span><?php echo formatDate($image['uploaded_at'] ?? time()); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    return ob_get_clean();
}

// Handle upload/delete AJAX (server side endpoints)
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $messages = [];

    if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
        $files = $_FILES['images'];
        if (!is_array($files['name'])) {
            $files = [
                'name' => [$files['name']],
                'type' => [$files['type']],
                'tmp_name' => [$files['tmp_name']],
                'error' => [$files['error']],
                'size' => [$files['size']]
            ];
        }
        for ($i = 0; $i < count($files['name']); $i++) {
            $name = $files['name'][$i];
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $messages[] = ['type'=>'error','text'=>'Upload error for: '.htmlspecialchars($name)];
                continue;
            }
            if ($files['size'][$i] > $maxFileSize) {
                $messages[] = ['type'=>'error','text'=>'File too large: '.htmlspecialchars($name)];
                continue;
            }
            $originalName = cleanFilename($name);
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions)) {
                $messages[] = ['type'=>'error','text'=>'Invalid file type: '.htmlspecialchars($originalName)];
                continue;
            }
            $imageInfo = @getimagesize($files['tmp_name'][$i]);
            if ($imageInfo === false || !in_array($imageInfo['mime'], $allowedMimeTypes)) {
                $messages[] = ['type'=>'error','text'=>'Invalid image file: '.htmlspecialchars($originalName)];
                continue;
            }
            $randomFilename = generateRandomFilename($originalName);
            $targetPath = $uploadDir . '/' . $randomFilename;
            if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                $fullUrl = $scheme . $_SERVER['HTTP_HOST'] . '/' . $uploadDir . '/' . $randomFilename;

                $imageData = [
                    'id'            => uniqid(),
                    'name'          => $randomFilename,
                    'original_name' => $originalName,
                    'size'          => $files['size'][$i],
                    'uploaded_at'   => time(),
                    'url'           => $fullUrl
                ];

                array_unshift($_SESSION['gallery'], $imageData);

                $messages[] = [
                    'type' => 'success',
                    'text' => 'Successfully uploaded: ' . $randomFilename,
                    'url'  => $imageData['url']
                ];
            } else {
                $messages[] = [
                    'type' => 'error',
                    'text' => 'Failed to save: ' . htmlspecialchars($originalName)
                ];
            }
        }
    }

    if ($action === 'delete' && isset($_GET['id'])) {
        $deleteId = (string)$_GET['id'];
        foreach ($_SESSION['gallery'] as $key => $image) {
            if (isset($image['id']) && $image['id'] === $deleteId) {
                $filePath = $uploadDir . '/' . $image['name'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                unset($_SESSION['gallery'][$key]);
                $_SESSION['gallery'] = array_values($_SESSION['gallery']);
                $messages[] = ['type'=>'success','text'=>'Deleted image'];
                break;
            }
        }
    }

    // prepare response
    $galleryHtml = renderGalleryHtml($_SESSION['gallery'], $uploadDir);
    $totalImages = count($_SESSION['gallery']);
    $totalStorage = getTotalStorageUsed($_SESSION['gallery']);
    $thisMonthUploads = getThisMonthUploads($_SESSION['gallery']);
    $storageLimit = 5 * 1024 * 1024 * 1024; // 5GB
    $storagePercentage = $storageLimit > 0 ? ($totalStorage / $storageLimit) * 100 : 0;

    header('Content-Type: application/json');
    echo json_encode([
        'messages' => $messages,
        'galleryHtml' => $galleryHtml,
        'stats' => [
            'totalImages' => $totalImages,
            'storageUsed' => formatFileSize($totalStorage),
            'thisMonth' => $thisMonthUploads,
            'percentage' => number_format($storagePercentage, 1)
        ]
    ]);
    exit;
}

// Get messages from session (non-AJAX flow)
$messages = $_SESSION['messages'] ?? [];
unset($_SESSION['messages']);

$gallery = $_SESSION['gallery'];
$currentView = isset($_GET['view']) ? $_GET['view'] : 'upload';

// Calculate statistics
$totalImages = count($gallery);
$totalStorage = getTotalStorageUsed($gallery);
$thisMonthUploads = getThisMonthUploads($gallery);
$storageLimit = 5 * 1024 * 1024 * 1024; // 5GB limit for display
$storagePercentage = $storageLimit > 0 ? ($totalStorage / $storageLimit) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($title); ?> | Cloud Image Hosting</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='64' height='64' viewBox='0 0 24 24' fill='none' stroke='%234f46e5'%3E%3Cpath d='M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z'/%3E%3C/svg%3E">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        body { 
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #0f0f23;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e1b4b 100%);
        }
        
        .card-dark {
            background: rgba(30, 27, 75, 0.4);
            border: 1px solid rgba(79, 70, 229, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .stats-card {
            background: rgba(15, 15, 35, 0.6);
            border: 1px solid rgba(79, 70, 229, 0.3);
            backdrop-filter: blur(8px);
        }
        
        .gallery-item {
            transition: all 0.2s ease;
        }
        
        .gallery-overlay {
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
        }
        
        .gallery-item:hover .gallery-overlay {
            opacity: 1;
            pointer-events: auto;
        }
        
        .drag-over {
            border-color: #4f46e5 !important;
            background-color: rgba(79, 70, 229, 0.1) !important;
        }
        
        .progress-bar {
            background: linear-gradient(90deg, #4f46e5 0%, #7c3aed 100%);
        }
        
        .cloud-logo {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #3b82f6 100%);
            box-shadow: 0 4px 20px rgba(79, 70, 229, 0.3);
        }
        
        .cloud-logo:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 25px rgba(79, 70, 229, 0.4);
        }
        
        @media (max-width: 768px) {
            .gallery-item.show .gallery-overlay {
                opacity: 1;
                pointer-events: auto;
            }
        }
    </style>
</head>
<body class="min-h-screen text-white gradient-bg">
    <div class="max-w-7xl mx-auto px-4 py-6">
        
        <!-- Header -->
        <header class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <!-- Enhanced Cloud Logo -->
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
            
            <div class="flex items-center gap-4">
                <a 
                    href="https://github.com/yardanshaq" 
                    class="p-2 text-gray-400 hover:text-white transition-colors rounded-lg hover:bg-white/10"
                    target="_blank" 
                    rel="noopener noreferrer"
                    title="GitHub"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 .5C5.65.5.5 5.65.5 12c0 5.1 3.29 9.41 7.86 10.94.58.11.79-.25.79-.56 0-.28-.01-1.02-.02-2-3.2.7-3.87-1.54-3.87-1.54-.53-1.34-1.3-1.7-1.3-1.7-1.06-.72.08-.7.08-.7 1.17.08 1.78 1.2 1.78 1.2 1.04 1.78 2.73 1.27 3.4.97.1-.75.41-1.27.75-1.56-2.55-.29-5.23-1.28-5.23-5.71 0-1.26.45-2.28 1.19-3.09-.12-.29-.52-1.47.11-3.06 0 0 .97-.31 3.18 1.18.92-.26 1.9-.39 2.88-.39.98 0 1.96.13 2.88.39 2.21-1.49 3.18-1.18 3.18-1.18.63 1.59.23 2.77.11 3.06.74.81 1.19 1.83 1.19 3.09 0 4.44-2.68 5.42-5.24 5.7.42.36.8 1.08.8 2.18 0 1.58-.01 2.85-.01 3.24 0 .31.21.67.8.56A10.52 10.52 0 0 0 23.5 12C23.5 5.65 18.35.5 12 .5Z"/>
                    </svg>
                </a>
            </div>
        </header>

        <!-- Welcome Section with Enhanced Cloud Branding -->
        <div class="text-center mb-12">
            <div class="flex justify-center mb-6">
                <div class="cloud-logo h-20 w-20 rounded-3xl flex items-center justify-center transition-all duration-500 hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                    </svg>
                </div>
            </div>
            <h2 class="text-4xl font-bold text-white mb-4">Welcome to <?php echo htmlspecialchars($title); ?></h2>
            <p class="text-lg text-gray-300 max-w-2xl mx-auto">
                Upload, manage, and share your images with lightning-fast performance and reliable cloud storage.
            </p>
        </div>

        <!-- Statistics Cards -->
        <div id="statsSection" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Images -->
            <div class="stats-card rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Images</p>
                        <p id="stat-total" class="text-3xl font-bold text-white"><?php echo $totalImages; ?></p>
                        <p class="text-gray-500 text-xs mt-1">Images uploaded</p>
                    </div>
                    <div class="h-12 w-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <circle cx="9" cy="9" r="2"/>
                            <path d="M21 15l-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Storage Used -->
            <div class="stats-card rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Storage Used</p>
                        <p id="stat-storage" class="text-3xl font-bold text-white"><?php echo formatFileSize($totalStorage); ?></p>
                        <p class="text-gray-500 text-xs mt-1">5 GB remaining</p>
                    </div>
                    <div class="h-12 w-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Plan -->
            <div class="stats-card rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Access</p>
                        <p class="text-3xl font-bold text-white">Free</p>
                        <p class="text-gray-500 text-xs mt-1">5GB storage limit</p>
                    </div>
                    <div class="h-12 w-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <polygon points="13,2 3,14 12,14 11,22 21,10 12,10 13,2"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- This Month -->
            <div class="stats-card rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">This Month</p>
                        <p id="stat-month" class="text-3xl font-bold text-white"><?php echo $thisMonthUploads; ?></p>
                        <p class="text-gray-500 text-xs mt-1">Images uploaded</p>
                    </div>
                    <div class="h-12 w-12 bg-orange-500/20 rounded-xl flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12,6 12,12 16,14"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Storage Usage Bar -->
        <div class="card-dark rounded-2xl p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                    </svg>
                    Cloud Storage Usage
                </h3>
                <span class="text-sm text-gray-400">Limit: 5 GB</span>
            </div>
            
            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span id="stat-used" class="text-gray-300">Used: <?php echo formatFileSize($totalStorage); ?></span>
                    <span id="stat-perc" class="text-gray-400"><?php echo number_format($storagePercentage, 1); ?>% used</span>
                </div>
                
                <div class="w-full bg-gray-700 rounded-full h-2">
                    <div id="stat-bar" class="progress-bar h-2 rounded-full transition-all duration-300" style="width: <?php echo min($storagePercentage, 100); ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <div id="messagesContainer">
        <?php if (!empty($messages)): ?>
        <div class="space-y-3 mb-8">
            <?php foreach ($messages as $message): ?>
            <div class="rounded-xl px-4 py-3 border <?php echo $message['type'] === 'success' ? 'bg-green-500/10 border-green-500/30 text-green-300' : 'bg-red-500/10 border-red-500/30 text-red-300'; ?>">
                <div class="flex flex-col gap-1">
                    <p class="text-sm"><?php echo htmlspecialchars($message['text']); ?></p>
                    <?php if (!empty($message['url'])): ?>
                    <a href="<?php echo htmlspecialchars($message['url']); ?>" target="_blank" rel="noopener noreferrer" class="text-sm underline break-all hover:no-underline opacity-80">
                        <?php echo htmlspecialchars($message['url']); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex gap-4 mb-8">
            <a href="?view=upload" class="px-6 py-3 rounded-xl font-medium transition-all flex items-center gap-2 <?php echo $currentView === 'upload' ? 'bg-indigo-600 text-white' : 'bg-gray-800/50 text-gray-300 hover:bg-gray-700/50'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                </svg>
                Upload Images
            </a>
            <a href="?view=gallery" class="px-6 py-3 rounded-xl font-medium transition-all flex items-center gap-2 <?php echo $currentView === 'gallery' ? 'bg-indigo-600 text-white' : 'bg-gray-800/50 text-gray-300 hover:bg-gray-700/50'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <circle cx="9" cy="9" r="2"/>
                    <path d="M21 15l-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                </svg>
                Gallery
            </a>
        </div>

        <!-- Content Area -->
        <?php if ($currentView === 'upload'): ?>
        <!-- Upload Section -->
        <section>
            <div class="card-dark rounded-2xl p-8">
                <form id="uploadForm" action="" method="post" enctype="multipart/form-data" class="space-y-6">
                    
                    <!-- Preview Container -->
                    <div id="previewContainer" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-4 mb-6 hidden"></div>
                    
                    <!-- Drop Zone with Cloud Branding -->
                    <div id="dropzone" class="relative rounded-2xl border-2 border-dashed border-gray-600 p-12 flex flex-col items-center justify-center text-center transition-all cursor-pointer hover:border-indigo-500 hover:bg-indigo-500/5">
                        <input type="file" name="images[]" id="fileInput" accept="image/*" multiple class="absolute inset-0 opacity-0 cursor-pointer">
                        
                        <div class="cloud-logo h-20 w-20 rounded-2xl flex items-center justify-center mb-6 hover:scale-105 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                            </svg>
                        </div>
                        
                        <h3 class="text-2xl font-semibold text-white mb-2">Upload to Cloud</h3>
                        <p class="text-gray-300 mb-1">Drag & drop images here or <span class="text-indigo-400 underline">browse files</span></p>
                        <p class="text-sm text-gray-500">Max 10MB per file • Supports: <?php echo implode(', ', $allowedExtensions); ?></p>
                    </div>
                    
                    <!-- Upload Controls -->
                    <div class="flex gap-4">
                        <button type="submit" id="uploadBtn" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                            </svg>
                            Upload to Cloud
                        </button>
                        <button type="button" id="clearBtn" class="px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-xl font-medium transition-all hidden">
                            Clear All
                        </button>
                    </div>
                </form>
            </div>
        </section>
        
        <?php else: ?>
        <!-- Gallery Section -->
        <section>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-semibold text-white flex items-center gap-3">
                    <div class="cloud-logo h-8 w-8 rounded-lg flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                        </svg>
                    </div>
                    Cloud Gallery
                </h2>
                <span class="text-sm text-gray-400 bg-gray-800/50 px-3 py-1 rounded-lg">
                    <?php echo count($gallery); ?> image<?php echo count($gallery) !== 1 ? 's' : ''; ?>
                </span>
            </div>

            <div id="galleryContainer">
                <?php echo renderGalleryHtml($gallery, $uploadDir); ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Footer -->
        <footer class="mt-16 pt-8 border-t border-gray-800 text-center">
            <div class="flex items-center justify-center gap-2 mb-2">
                <div class="cloud-logo h-6 w-6 rounded-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                    </svg>
                </div>
                <span class="text-gray-500 font-medium"><?php echo htmlspecialchars($title); ?></span>
            </div>
            <p class="text-gray-500 text-sm">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($title); ?>. All rights reserved.</p>
        </footer>
    </div>

    <script>
        const fileInput = document.getElementById('fileInput');
        const previewContainer = document.getElementById('previewContainer');
        const uploadBtn = document.getElementById('uploadBtn');
        const clearBtn = document.getElementById('clearBtn');
        const dropzone = document.getElementById('dropzone');
        let selectedFiles = [];

        // File input change handler
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                Array.from(this.files).forEach(file => {
                    if (file.type.startsWith('image/')) {
                        selectedFiles.push(file);
                    }
                });
                updatePreviews();
            });
        }

        // Drag and drop handlers
        if (dropzone) {
            dropzone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });

            dropzone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
            });

            dropzone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
                
                const files = Array.from(e.dataTransfer.files).filter(file => 
                    file.type.startsWith('image/')
                );
                
                selectedFiles.push(...files);
                updatePreviews();
            });
        }

        // Update preview display
        function updatePreviews() {
            if (!previewContainer || !uploadBtn || !clearBtn) return;
            
            previewContainer.innerHTML = '';
            
            if (selectedFiles.length === 0) {
                previewContainer.classList.add('hidden');
                uploadBtn.disabled = true;
                clearBtn.classList.add('hidden');
                return;
            }
            
            previewContainer.classList.remove('hidden');
            uploadBtn.disabled = false;
            clearBtn.classList.remove('hidden');
            
            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative group';
                    div.innerHTML = `
                        <div class="aspect-square rounded-xl overflow-hidden border border-gray-600">
                            <img src="${e.target.result}" alt="${file.name}" class="w-full h-full object-cover">
                        </div>
                        <button type="button" onclick="removeFile(${index})" class="absolute -top-2 -right-2 h-6 w-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    `;
                    previewContainer.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }

        // Remove file from selection
        function removeFile(index) {
            selectedFiles.splice(index, 1);
            updatePreviews();
        }

        // Clear all files
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                selectedFiles = [];
                if (fileInput) fileInput.value = '';
                updatePreviews();
            });
        }

        // Form submission handler (AJAX)
        const uploadForm = document.getElementById('uploadForm');
        if (uploadForm) {
            uploadForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                if (selectedFiles.length > 0 && fileInput) {
                    const dt = new DataTransfer();
                    selectedFiles.forEach(file => dt.items.add(file));
                    fileInput.files = dt.files;
                }
                const fd = new FormData(uploadForm);
                // send to AJAX endpoint
                try {
                    const res = await fetch('?action=upload', { method: 'POST', body: fd });
                    const data = await res.json();
                    handleAjaxResponse(data);
                    // reset selection
                    selectedFiles = [];
                    fileInput.value = '';
                    updatePreviews();
                } catch (err) {
                    showToast('Upload failed', 'error');
                }
            });
        }

        // handle delete clicks (delegation)
        document.addEventListener('click', function(e) {
            const el = e.target.closest('.ajax-delete');
            if (!el) return;
            e.preventDefault();
            const id = el.getAttribute('data-id');
            if (!id) return;
            if (!confirm('Are you sure you want to delete this image?')) return;
            fetch('?action=delete&id=' + encodeURIComponent(id))
                .then(r => r.json())
                .then(data => handleAjaxResponse(data))
                .catch(() => showToast('Delete failed', 'error'));
        });

        // Copy to clipboard (delegation)
        document.addEventListener('click', function(e) {
            const el = e.target.closest('.copy-url');
            if (!el) return;
            const url = el.getAttribute('data-copy');
            if (!url) return;
            copyToClipboard(url);
        });

        // Handle generic AJAX response
function handleAjaxResponse(data) {
    if (!data) return;

    // messages
    if (data.messages && Array.isArray(data.messages)) {
        const messagesContainer = document.getElementById('messagesContainer');
        if (messagesContainer) {
            // hapus pesan lama
            messagesContainer.innerHTML = '';

            const wrapper = document.createElement('div');
            wrapper.className = 'space-y-3 mb-8';

            data.messages.forEach(msg => {
                const div = document.createElement('div');
                const displayUrl = msg.url.replace(/^https?:\/\//, ''); 
                div.className = `rounded-xl px-4 py-3 border ${
                    msg.type === 'success'
                        ? 'bg-green-500/10 border-green-500/30 text-green-300'
                        : 'bg-red-500/10 border-red-500/30 text-red-300'
                }`;
                div.innerHTML = `
                    <div class="flex flex-col gap-1">
                        <p class="text-sm">${msg.text}</p>
                        ${msg.url ? `<a href="${msg.url}" target="_blank" rel="noopener noreferrer" 
                            class="text-sm underline break-all hover:no-underline opacity-80">
                            ${displayUrl}</a>` : ''}
                    </div>
                `;
                wrapper.appendChild(div);
            });

            messagesContainer.appendChild(wrapper);
        }
    }

    // gallery HTML
    if (data.galleryHtml !== undefined) {
        const container = document.getElementById('galleryContainer');
        if (container) container.innerHTML = data.galleryHtml;
    }

    // stats
    if (data.stats) {
        const s = data.stats;
        document.getElementById('stat-total').textContent = s.totalImages;
        document.getElementById('stat-storage').textContent = s.storageUsed;
        document.getElementById('stat-month').textContent = s.thisMonth;
        document.getElementById('stat-used').textContent = 'Used: ' + s.storageUsed;
        document.getElementById('stat-perc').textContent = s.percentage + '% used';
        document.getElementById('stat-bar').style.width = Math.min(parseFloat(s.percentage), 100) + '%';
    }
}


        // Copy to clipboard function
        async function copyToClipboard(url) {
            try {
                await navigator.clipboard.writeText(url);
                showToast('URL copied to clipboard!', 'success');
            } catch (err) {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showToast('URL copied to clipboard!', 'success');
            }
        }

        // Show toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 px-6 py-3 rounded-xl shadow-lg z-50 transition-all transform translate-x-full ${
                type === 'success' ? 'bg-green-600 text-white' :
                type === 'error' ? 'bg-red-600 text-white' :
                'bg-blue-600 text-white'
            }`;
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            // Slide in
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);
            
            // Slide out and remove
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }

        // Mobile gallery overlay toggle
        document.querySelectorAll('.gallery-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (e.target.tagName.toLowerCase() === 'a' || e.target.tagName.toLowerCase() === 'button') {
                    return;
                }
                this.classList.toggle('show');
            });
        });
    </script>
</body>
</html>
