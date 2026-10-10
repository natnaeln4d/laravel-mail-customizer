<?php

namespace Natnaeln4d\MailCustomizer\Rendering;

/**
 * Small helpers for building inline styles and safe HTML attribute values.
 */
class Css
{
    /**
     * Turn ['color' => '#333'] into 'color:#333;'. Empty values and values that
     * could break out of the attribute or inject markup are dropped.
     */
    public static function inline(array $styles): string
    {
        $out = '';

        foreach ($styles as $property => $value) {
            $property = strtolower(trim((string) $property));
            $value = trim((string) $value);

            if ($value === '' || ! preg_match('/^-?[a-z-]+$/', $property)) {
                continue;
            }
            if (preg_match('/[<>"{};]|expression\s*\(|javascript:|url\s*\(\s*[\'"]?\s*data:/i', $value)) {
                continue;
            }

            $out .= $property . ':' . $value . ';';
        }

        return $out;
    }

    /**
     * "12", "12px" -> 12 (email clients want unitless width/height attributes).
     */
    public static function px($value, int $default = 0): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        return preg_match('/^\s*(\d+(?:\.\d+)?)\s*px\s*$/i', (string) $value, $m) ? (int) $m[1] : $default;
    }

    public static function color($value, string $default): string
    {
        $value = trim((string) $value);

        return preg_match('/^(#[0-9a-f]{3,8}|rgba?\([\d\s.,%]+\)|[a-z]+)$/i', $value) ? $value : $default;
    }

    /**
     * Allow http(s), mailto, tel, anchors, relative paths and {{ placeholders }};
     * anything else (javascript:, data:, ...) becomes '#'.
     */
    public static function url($value): string
    {
        $value = trim((string) $value);

        if ($value === '' || preg_match('/^(https?:|mailto:|tel:|#|\/|\{\{)/i', $value)) {
            return $value;
        }

        return '#';
    }

    public static function align($value, string $default = 'left'): string
    {
        $value = strtolower((string) $value);

        return in_array($value, ['left', 'center', 'right'], true) ? $value : $default;
    }
}
