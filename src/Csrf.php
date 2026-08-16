<?php

declare(strict_types=1);

namespace Luxid\Nova;

/**
 * CSRF token issuing and verification for Nova action calls.
 *
 * Component actions mutate server-side state, so the endpoint that dispatches
 * them is a state-changing POST and needs the same protection as any form. The
 * client already sends a `_token` field; nothing was checking it.
 *
 * @package Luxid\Nova
 */
final class Csrf
{
    /**
     * Session key holding the per-session token.
     */
    private const SESSION_KEY = 'nova_csrf_token';

    /**
     * Get the token for this session, minting one on first use.
     */
    public static function token(): string
    {
        self::bootSession();

        if (!isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Check a submitted token against the session's.
     *
     * Compared in constant time so a timing oracle cannot recover the token.
     *
     * @param string|null $candidate Token supplied by the client
     */
    public static function verify(?string $candidate): bool
    {
        if (!is_string($candidate) || $candidate === '') {
            return false;
        }

        return hash_equals(self::token(), $candidate);
    }

    /**
     * Render the meta tag the client runtime reads the token from.
     *
     * Place this in the document head:
     *
     * ```php
     * @raw(\Luxid\Nova\Csrf::metaTag())
     * ```
     */
    public static function metaTag(): string
    {
        return '<meta name="csrf-token" content="'
            . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8')
            . '">';
    }

    /**
     * Render a hidden input carrying the token, for plain form posts.
     */
    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8')
            . '">';
    }

    /**
     * Discard the current token, forcing a new one to be minted.
     */
    public static function rotate(): void
    {
        self::bootSession();
        unset($_SESSION[self::SESSION_KEY]);
    }

    /**
     * Start the session if the SAPI allows it.
     */
    private static function bootSession(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }
}
