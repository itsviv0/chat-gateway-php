<?php

declare(strict_types=1);

namespace App\Utils;

use Valitron\Validator as ValitronValidator;

/**
 * Custom validator wrapper for input validation and sanitization
 */
class Validator
{
    /**
     * Validate and sanitize login credentials
     *
     * @param array<string,mixed> $data
     * @return array{valid: bool, errors: array<string,mixed>, sanitized: array<string,string>}
     */
    public static function validateLogin(array $data): array
    {
        $v = new ValitronValidator($data);

        $v->rule('required', ['email', 'password']);
        $v->rule('email', 'email');
        $v->rule('lengthMin', 'password', 1);

        $valid = $v->validate();

        // Sanitize data
        $sanitized = [
            'email' => isset($data['email']) ? self::sanitizeEmail((string) $data['email']) : '',
            'password' => isset($data['password']) ? (string) $data['password'] : '', // Don't sanitize password
        ];

        return [
            'valid' => $valid,
            'errors' => (array) $v->errors(),
            'sanitized' => $sanitized,
        ];
    }

    /**
     * Validate and sanitize group creation data
     *
     * @param array<string,mixed> $data
     * @return array{valid: bool, errors: array<string,mixed>, sanitized: array<string,mixed>}
     */
    public static function validateGroupCreation(array $data): array
    {
        $v = new ValitronValidator($data);

        $v->rule('required', 'name');
        $v->rule('lengthMax', 'name', 100);
        $v->rule('lengthMin', 'name', 1);
        $v->rule('optional', 'description');
        $v->rule('optional', 'is_private');

        $valid = $v->validate();

        // Sanitize data
        $sanitized = [
            'name' => isset($data['name']) ? self::sanitizeString((string) $data['name']) : '',
            'description' => isset($data['description']) ? self::sanitizeString((string) $data['description']) : null,
            'is_private' => isset($data['is_private']) ? filter_var($data['is_private'], FILTER_VALIDATE_BOOL) : false,
        ];

        return [
            'valid' => $valid,
            'errors' => (array) $v->errors(),
            'sanitized' => $sanitized,
        ];
    }

    /**
     * Validate and sanitize invitation data
     *
     * @param array<string,mixed> $data
     * @return array{valid: bool, errors: array<string,mixed>, sanitized: array<string,mixed>}
     */
    public static function validateInvitation(array $data): array
    {
        $v = new ValitronValidator($data);

        $v->rule('required', 'email');
        $v->rule('email', 'email');
        $v->rule('optional', 'expires_in_hours');
        $v->rule('integer', 'expires_in_hours');
        $v->rule('min', 'expires_in_hours', 1);
        $v->rule('max', 'expires_in_hours', 720);

        $valid = $v->validate();

        // Sanitize data
        $sanitized = [
            'email' => isset($data['email']) ? self::sanitizeEmail((string) $data['email']) : '',
            'expires_in_hours' => isset($data['expires_in_hours']) ? (int) $data['expires_in_hours'] : 168,
        ];

        return [
            'valid' => $valid,
            'errors' => (array) $v->errors(),
            'sanitized' => $sanitized,
        ];
    }

    /**
     * Validate and sanitize message data
     *
     * @param array<string,mixed> $data
     * @return array{valid: bool, errors: array<string,mixed>, sanitized: array<string,string>}
     */
    public static function validateMessage(array $data): array
    {
        $v = new ValitronValidator($data);

        $v->rule('required', 'content');
        $v->rule('lengthMin', 'content', 1);
        $v->rule('lengthMax', 'content', 5000);

        $valid = $v->validate();

        // Sanitize data - preserve content but trim whitespace
        $sanitized = [
            'content' => isset($data['content']) ? self::sanitizeText((string) $data['content']) : '',
        ];

        return [
            'valid' => $valid,
            'errors' => (array) $v->errors(),
            'sanitized' => $sanitized,
        ];
    }

    /**
     * Sanitize email address
     */
    private static function sanitizeEmail(string $email): string
    {
        $email = trim($email);
        $sanitized = filter_var($email, FILTER_SANITIZE_EMAIL);
        return $sanitized !== false ? $sanitized : '';
    }

    /**
     * Sanitize general string (strip tags, trim whitespace)
     */
    private static function sanitizeString(string $str): string
    {
        return trim(strip_tags($str));
    }

    /**
     * Sanitize text content (preserve some formatting but remove dangerous HTML)
     */
    private static function sanitizeText(string $text): string
    {
        // Trim whitespace but preserve the content
        return trim($text);
    }

    /**
     * Format validation errors into a readable string
     *
     * @param array<string,string[]> $errors
     */
    public static function formatErrors(array $errors): string
    {
        if (empty($errors)) {
            return '';
        }

        $messages = [];
        foreach ($errors as $field => $fieldErrors) {
            $messages[] = implode(', ', $fieldErrors);
        }

        return implode('; ', $messages);
    }
}
