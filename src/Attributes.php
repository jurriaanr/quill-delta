<?php
/**
 * User: Jurriaan Ruitenberg
 * Date: 24-7-2018
 * Time: 11:13
 */

namespace Oberon\Quill\Delta;

class Attributes
{
    public static function compose(array $a, array $b, $keepNull = false)
    {
        $attributes = $a + $b;

        if (!$keepNull) {
            $attributes = array_reduce(array_keys($attributes), function ($copy, $key) use ($attributes) {
                if ($attributes[$key] !== null) {
                    $copy[$key] = $attributes[$key];
                }
                return $copy;
            }, []);
        }

        foreach ($a as $key => $value) {
            if (isset($a[$key]) && isset($b[$key])) {
                $attributes[$key] = $a[$key];
            }
        }

        return count(array_keys($attributes)) > 0 ? $attributes : null;
    }
}