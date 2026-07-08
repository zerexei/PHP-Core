<?php

namespace Zeretei\PHPCore;

/**
 * Session manager with two-pass flash bags.
 *
 * Flash messages live for exactly one request cycle:
 *   1. On construction, all existing flash entries are marked for removal.
 *   2. Any new entries added during this request are NOT marked.
 *   3. On destruction, only marked entries are deleted.
 *
 * Two bags are maintained: `flash_bag` (notifications) and `error_bag` (validation errors).
 */
class Session
{
    protected const FLASH_KEY = 'flash_bag';
    protected const ERROR_KEY = 'error_bag';

    public function __construct()
    {
        $this->markForFlush();
    }

    // -------------------------------------------------------------------------
    // Flash bag (notifications)
    // -------------------------------------------------------------------------

    /**
     * Store a flash notification under $key.
     */
    public function setFlash(string $key, string $message): void
    {
        $this->writeFlashEntry(static::FLASH_KEY, $key, $message);
    }

    /**
     * Retrieve a single flash notification, or null if not set.
     */
    public function getFlash(string $key): ?string
    {
        return $this->readFlashEntry(static::FLASH_KEY, $key);
    }

    /**
     * Return all flash notifications as a flat key→message array.
     *
     * @return array<string, string>
     */
    public function flashBag(): array
    {
        return $this->readBag(static::FLASH_KEY);
    }

    // -------------------------------------------------------------------------
    // Error bag (validation errors)
    // -------------------------------------------------------------------------

    /**
     * Store a validation error under $key.
     */
    public function setErrorFlash(string $key, string $message): void
    {
        $this->writeFlashEntry(static::ERROR_KEY, $key, $message);
    }

    /**
     * Retrieve a single validation error, or null if not set.
     */
    public function getErrorFlash(string $key): ?string
    {
        return $this->readFlashEntry(static::ERROR_KEY, $key);
    }

    /**
     * Return all validation errors as a flat key→message array.
     *
     * @return array<string, string>
     */
    public function errorBag(): array
    {
        return $this->readBag(static::ERROR_KEY);
    }

    // -------------------------------------------------------------------------
    // General session
    // -------------------------------------------------------------------------

    /**
     * Store a value in the session. String values are sanitized.
     */
    public function set(string $key, mixed $value): void
    {
        if (is_string($value)) {
            $value = strip_tags(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
        }

        $_SESSION[$key] = $value;
    }

    /**
     * Retrieve a session value, or null if the key is not set.
     */
    public function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    /**
     * Return the entire session as an array.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $_SESSION ?? [];
    }

    // -------------------------------------------------------------------------
    // Internal flash management
    // -------------------------------------------------------------------------

    /**
     * Write a sanitized flash entry to the given bag.
     */
    protected function writeFlashEntry(string $bag, string $key, string $message): void
    {
        $_SESSION[$bag][$key] = [
            'value'  => strip_tags(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')),
            'remove' => false,
        ];
    }

    /**
     * Read a single flash entry value, or null if absent.
     */
    protected function readFlashEntry(string $bag, string $key): ?string
    {
        return $_SESSION[$bag][$key]['value'] ?? null;
    }

    /**
     * Return all entries in a bag as a flat key→message array.
     *
     * @return array<string, string>
     */
    protected function readBag(string $bag): array
    {
        $entries = $_SESSION[$bag] ?? [];
        return array_map(fn (array $entry) => $entry['value'], $entries);
    }

    /**
     * Mark all existing flash entries for removal at end-of-request.
     * Called on construction so entries from the *previous* request are flushed.
     */
    protected function markForFlush(): void
    {
        foreach ([static::FLASH_KEY, static::ERROR_KEY] as $bag) {
            if (!isset($_SESSION[$bag])) {
                continue;
            }
            foreach ($_SESSION[$bag] as &$entry) {
                $entry['remove'] = true;
            }
            unset($entry);
        }
    }

    /**
     * Delete all flash entries that were marked for removal.
     */
    protected function flush(): void
    {
        foreach ([static::FLASH_KEY, static::ERROR_KEY] as $bag) {
            if (!isset($_SESSION[$bag])) {
                continue;
            }
            foreach ($_SESSION[$bag] as $key => $entry) {
                if ($entry['remove']) {
                    unset($_SESSION[$bag][$key]);
                }
            }
        }
    }

    public function __destruct()
    {
        $this->flush();
    }
}
