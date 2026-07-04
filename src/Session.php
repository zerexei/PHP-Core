<?php

namespace Zeretei\PHPCore;

class Session
{
    /**
     * Session flash messages key
     * 
     * @var string
     */
    protected const FLASH_KEY = 'flash_bag';

    /**
     * Session error messages key
     * 
     * @var string
     */
    protected const ERROR_KEY = 'error_bag';

    public function __construct()
    {
        $this->toFlush();
    }

    /**
     * Helper to set a flash message in a specific bag
     */
    protected function setFlashMessage(string $bag, string $key, string $message): void
    {
        $message = strip_tags(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

        $_SESSION[$bag][$key] = [
            'value' => $message,
            'remove' => false
        ];
    }

    /**
     * Helper to retrieve a flash message from a specific bag
     */
    protected function getFlashMessage(string $bag, string $key): ?string
    {
        return $_SESSION[$bag][$key]['value'] ?? null;
    }

    /**
     * Helper to retrieve all messages from a specific bag
     */
    protected function getBag(string $bag): array
    {
        $messages = $_SESSION[$bag] ?? [];

        foreach ($messages as $key => $value) {
            $messages[$key] = $value['value'];
        }

        return $messages;
    }

    /**
     * Set a flash session
     */
    public function setFlash(string $key, string $message): void
    {
        $this->setFlashMessage(static::FLASH_KEY, $key, $message);
    }

    /**
     * Return a flash session
     */
    public function getFlash(string $key): ?string
    {
        return $this->getFlashMessage(static::FLASH_KEY, $key);
    }

    /**
     * Return all flash session - key & value only
     */
    public function flashBag(): array
    {
        return $this->getBag(static::FLASH_KEY);
    }

    /**
     * Set a error flash session
     */
    public function setErrorFlash(string $key, string $message): void
    {
        $this->setFlashMessage(static::ERROR_KEY, $key, $message);
    }

    /**
     * Return a error flash session
     */
    public function getErrorFlash(string $key): ?string
    {
        return $this->getFlashMessage(static::ERROR_KEY, $key);
    }

    /**
     * Return all error flash session - key & value only
     */
    public function errorBag(): array
    {
        return $this->getBag(static::ERROR_KEY);
    }

    /**
     * Set a session
     */
    public function set(string $key, mixed $value): void
    {
        if (is_string($value)) {
            $value = strip_tags(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
        }

        $_SESSION[$key] = $value;
    }

    /**
     * Return a session
     */
    public function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    /**
     * Return all session
     */
    public function all(): array
    {
        return $_SESSION ?? [];
    }

    /**
     * Convert all flash sessions to removable
     */
    protected function toFlush(): void
    {
        foreach ([static::FLASH_KEY, static::ERROR_KEY] as $bag) {
            if (isset($_SESSION[$bag])) {
                foreach ($_SESSION[$bag] as &$message) {
                    $message['remove'] = true;
                }
            }
        }
    }

    /**
     * Flush all removable flash sessions
     */
    protected function flush(): void
    {
        foreach ([static::FLASH_KEY, static::ERROR_KEY] as $bag) {
            if (isset($_SESSION[$bag])) {
                foreach ($_SESSION[$bag] as $key => $message) {
                    if ($message['remove']) {
                        unset($_SESSION[$bag][$key]);
                    }
                }
            }
        }
    }

    public function __destruct()
    {
        $this->flush();
    }
}
