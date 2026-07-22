<?php
/**
 * video_utilities.php
 * Video platform detection and embed generation for YouTube, Facebook, TikTok
 */

/**
 * Detect video platform from URL and extract video ID
 * 
 * @param string $url Video URL
 * @return array|false ['platform' => string, 'video_id' => string] or false if invalid
 */
function detect_video_platform(string $url): array|false
{
    $url = trim($url);
    
    // YouTube detection and ID extraction
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        return [
            'platform'  => 'youtube',
            'video_id'  => $matches[1],
            'full_url'  => $url
        ];
    }
    
    // Facebook Video detection
    if (preg_match('/facebook\.com\/.*\/videos\/(\d+)/', $url, $matches) || 
        preg_match('/fb\.watch\/([a-zA-Z0-9]+)/', $url, $matches)) {
        return [
            'platform'  => 'facebook',
            'video_id'  => $matches[1] ?? $url,
            'full_url'  => $url
        ];
    }
    
    // TikTok detection
    if (preg_match('/(?:tiktok\.com\/@[^\/]+\/video\/|vm\.tiktok\.com\/|vt\.tiktok\.com\/)(\d+)/', $url, $matches)) {
        return [
            'platform'  => 'tiktok',
            'video_id'  => $matches[1],
            'full_url'  => $url
        ];
    }
    
    return false;
}

/**
 * Generate embed HTML for video
 * 
 * @param string $url Video URL or video ID
 * @param string $platform Platform name
 * @param array $attributes HTML attributes for iframe (width, height, etc.)
 * @return string HTML embed code
 */
function generate_video_embed(string $url, string $platform, array $attributes = []): string
{
    $detection = detect_video_platform($url);
    if ($detection) {
        $platform = $detection['platform'];
        $video_id = $detection['video_id'];
    } else {
        $video_id = $url; // Assume it's already a video ID
    }
    
    // Default attributes
    $width = $attributes['width'] ?? 560;
    $height = $attributes['height'] ?? 315;
    $class = $attributes['class'] ?? 'video-embed';
    
    $base_attrs = sprintf(
        'width="%d" height="%d" class="%s" frameborder="0" allowfullscreen',
        $width,
        $height,
        e($class)
    );
    
    switch ($platform) {
        case 'youtube':
            return sprintf(
                '<iframe src="https://www.youtube.com/embed/%s?autoplay=0" %s></iframe>',
                e($video_id),
                $base_attrs
            );
            
        case 'facebook':
            return sprintf(
                '<iframe src="https://www.facebook.com/plugins/video.php?href=https://www.facebook.com/video.php?v=%s&show_text=false" %s allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe>',
                e($video_id),
                $base_attrs
            );
            
        case 'tiktok':
            return sprintf(
                '<iframe src="https://www.tiktok.com/embed/v2/%s" %s></iframe>',
                e($video_id),
                $base_attrs
            );
            
        default:
            return '';
    }
}

/**
 * Validate video URL format
 * 
 * @param string $url Video URL
 * @return bool True if valid, false otherwise
 */
function is_valid_video_url(string $url): bool
{
    return detect_video_platform($url) !== false;
}

/**
 * Get platform display name
 * 
 * @param string $platform Platform key (youtube, facebook, tiktok)
 * @return string Display name
 */
function get_platform_name(string $platform): string
{
    $platforms = [
        'youtube'  => 'YouTube',
        'facebook' => 'Facebook Video',
        'tiktok'   => 'TikTok'
    ];
    
    return $platforms[$platform] ?? ucfirst($platform);
}

/**
 * Get platform icon class (for Bootstrap Icons)
 * 
 * @param string $platform Platform key
 * @return string Icon class
 */
function get_platform_icon(string $platform): string
{
    $icons = [
        'youtube'  => 'bi-youtube',
        'facebook' => 'bi-facebook',
        'tiktok'   => 'bi-tiktok'
    ];
    
    return $icons[$platform] ?? 'bi-play-circle';
}

/**
 * Sanitize and extract video URL for database storage
 * 
 * @param string $url Raw video URL
 * @return string|false Clean URL for storage or false if invalid
 */
function sanitize_video_url(string $url): string|false
{
    $detection = detect_video_platform($url);
    
    if (!$detection) {
        return false;
    }
    
    // Store the full original URL for flexibility
    return $detection['full_url'];
}

/**
 * Generate embed container HTML with responsive sizing
 * 
 * @param string $url Video URL
 * @param string $platform Platform
 * @param string $title Optional video title
 * @return string HTML container
 */
function generate_responsive_video_embed(string $url, string $platform, string $title = ''): string
{
    $embed = generate_video_embed($url, $platform, [
        'width'  => '100%',
        'height' => '100%',
        'class'  => 'w-100 h-100'
    ]);
    
    $platform_name = get_platform_name($platform);
    $title_html = $title ? sprintf('<p class="text-muted text-center small">%s</p>', e($title)) : '';
    
    return sprintf(
        '<div class="video-container" style="position: relative; padding-bottom: 56.25%%; height: 0; overflow: hidden; max-width: 100%%;">
            %s
            <div style="position: absolute; top: 0; left: 0; width: 100%%; height: 100%%; display: flex; align-items: center; justify-content: center;">
                %s
            </div>
        </div>
        %s',
        $embed,
        $embed,
        $title_html
    );
}
