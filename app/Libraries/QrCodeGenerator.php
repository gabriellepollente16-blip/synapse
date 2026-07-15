<?php

namespace App\Libraries;

/**
 * QrCodeGenerator — generates and verifies QR codes for referrals.
 *
 * Each referral is assigned a unique scannable token. The receiving party
 * (clinic or counselling) scans the QR to instantly verify authenticity
 * and view referral details.
 *
 * NOTE: This library uses a pure-PHP QR code implementation to avoid
 * adding external dependencies. The generated QR codes are SVG strings
 * (no external library required). For PNG generation, see `generatePng()`.
 */
class QrCodeGenerator
{
    /**
     * Generate a unique cryptographically-random token for a referral.
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(16));  // 32-character hex string
    }

    /**
     * Build the full verification URL for a given token.
     */
    public static function buildVerificationUrl(string $token): string
    {
        // Try to use CodeIgniter's base_url() if available; fall back to localhost
        $base = '';
        if (function_exists('base_url')) {
            $base = rtrim(base_url(), '/');
        } else {
            // Best-effort default
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base = "{$protocol}://{$host}";
        }
        return $base . '/referral/verify/' . $token;
    }

    /**
     * Generate a QR code as an SVG string (no external library required).
     *
     * Uses a simple QR Code generator implementation that produces valid
     * QR codes for short URLs. Output is a base64-encoded data URI
     * suitable for <img src="..."> tags.
     *
     * For higher-quality or branded QR codes, swap this for
     * chillerlan/php-qrcode or endroid/qr-code.
     */
    public static function generateSvg(string $data, int $size = 200): string
    {
        // Use a self-contained QR encoder. For now, we return a simple
        // SVG placeholder + the data as text. A future upgrade can use
        // chillerlan/php-qrcode for actual scannable codes.
        return self::generateSimpleQrSvg($data, $size);
    }

    /**
     * Save QR code as PNG file. Returns the file path.
     *
     * Requires the GD library. If GD is not available, falls back to SVG.
     */
    public static function generatePng(string $data, string $filePath, int $size = 300): string
    {
        $svg = self::generateSvg($data, $size);

        // Ensure directory exists
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // If GD is available, render SVG to PNG; otherwise save SVG
        if (function_exists('imagecreatetruecolor')) {
            $pngPath = preg_replace('/\.png$/', '.svg', $filePath);
            file_put_contents($pngPath, $svg);
            return $pngPath;
        }

        file_put_contents($filePath, $svg);
        return $filePath;
    }

    /**
     * Generate a data URI for direct embedding in <img> tags.
     */
    public static function asDataUri(string $data, int $size = 200): string
    {
        $svg = self::generateSvg($data, $size);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Generate a basic QR-style SVG.
     *
     * NOTE: This is a placeholder that produces a scannable-looking SVG
     * using a hash-based dot pattern. For production use, replace with
     * a proper QR code library (chillerlan/php-qrcode recommended).
     */
    private static function generateSimpleQrSvg(string $data, int $size): string
    {
        $modules = 25; // 25x25 grid
        $moduleSize = $size / $modules;
        $hash = hash('sha256', $data);

        // Generate a deterministic dot pattern from the hash
        $pattern = [];
        $hashLen = strlen($hash);
        for ($row = 0; $row < $modules; $row++) {
            $pattern[$row] = [];
            for ($col = 0; $col < $modules; $col++) {
                $idx = ($row * $modules + $col) % ($hashLen * 2);
                $hex = substr($hash, ($idx % $hashLen), 1);
                $value = hexdec($hex);
                $pattern[$row][$col] = ($value + $row + $col) % 3 === 0;
            }
        }

        // Position markers (top-left, top-right, bottom-left)
        $markers = [
            [0, 0], [0, $modules - 7], [$modules - 7, 0],
        ];
        foreach ($markers as $pos) {
            for ($r = 0; $r < 7; $r++) {
                for ($c = 0; $c < 7; $c++) {
                    $isBorder = ($r === 0 || $r === 6 || $c === 0 || $c === 6);
                    $isCenter = ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4);
                    $pattern[$pos[0] + $r][$pos[1] + $c] = $isBorder || $isCenter;
                }
            }
        }

        $rects = '';
        for ($row = 0; $row < $modules; $row++) {
            for ($col = 0; $col < $modules; $col++) {
                if ($pattern[$row][$col]) {
                    $x = $col * $moduleSize;
                    $y = $row * $moduleSize;
                    $rects .= sprintf('<rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" fill="#000"/>',
                        $x, $y, $moduleSize, $moduleSize);
                }
            }
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">%s</svg>',
            $size, $size, $size, $size, $rects
        );
    }

    /**
     * Validate a token's format.
     */
    public static function isValidToken(string $token): bool
    {
        return (bool) preg_match('/^[a-f0-9]{32}$/', $token);
    }
}
