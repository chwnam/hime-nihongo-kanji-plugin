<?php

if (!function_exists('hnkp')) {
    function hnkp(): \Bojaghi\Continy\Continy
    {
        static $instance = null;

        if (is_null($instance)) {
            try {
                $instance = \Bojaghi\Continy\ContinyFactory::create(HNKP_SETTINGS . '/continy.php');
            } catch (\Bojaghi\Continy\ContinyException $e) {
                wp_die($e->getMessage());
            }
        }

        return $instance;
    }
}

if (!function_exists('hnkp_get')) {
    /**
     * @template T
     * @param class-string<T>|string $id
     *
     * @return T|mixed|object|string|null
     */
    function hnkp_get(string $id): mixed
    {
        try {
            $instance = hnkp()->get($id);
        } catch (\Bojaghi\Continy\ContinyException $e) {
            return null;
        }

        return $instance;
    }
}

if (!function_exists('hnkp_template')) {
    function hnkp_template(string $tmplName, array $data = []): string
    {
        return hnkp_get('bojaghi/template')?->template($tmplName, $data);
    }
}
