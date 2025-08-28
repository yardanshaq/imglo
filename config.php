<?php
// ===== Img Drop Configuration =====

return [
    // Basic Settings
    'title' => 'Img Drop',
    'github_url' => 'https://github.com/yardanshaq',
    
    // Upload Settings
    'upload_dir' => 'uploads',
    'max_file_size' => 10 * 1024 * 1024, // 10 MB in bytes
    'allowed_extensions' => ['png', 'jpg', 'jpeg', 'gif', 'webp'],
    'allowed_mime_types' => [
        'image/png',
        'image/jpeg', 
        'image/pjpeg',
        'image/gif',
        'image/webp'
    ],
    
    // Security Settings
    'max_uploads_per_session' => 100,
    'cleanup_old_files' => true,
    'cleanup_days' => 7,
    'rate_limit_enabled' => true,
    'max_uploads_per_hour' => 50,
    
    // UI Settings
    'dark_mode_default' => false,
    'show_file_info' => true,
    'enable_direct_links' => true,
    
    // Advanced Settings
    'image_quality' => 85, // For JPEG compression if implemented
    'generate_thumbnails' => false,
    'thumbnail_size' => 200,
];
?>