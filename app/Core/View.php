<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    /**
     * Render a view template, echoing the result directly.
     *
     * @param string $template  Dot-separated path relative to app/Views/ (e.g. "admin.index")
     * @param array  $data      Variables extracted into the template scope
     */
    public static function render(string $template, array $data = []): void
    {
        $file = self::path($template);
        if (!file_exists($file)) {
            throw new \RuntimeException("View bulunamadı: {$template}");
        }

        extract($data);
        ob_start();
        require $file;
        echo ob_get_clean();
    }

    /**
     * Render a view template and return the output as a string.
     *
     * @param string $template  Dot-separated path relative to app/Views/
     * @param array  $data      Variables extracted into the template scope
     */
    public static function partial(string $template, array $data = []): string
    {
        $file = self::path($template);
        if (!file_exists($file)) {
            throw new \RuntimeException("Partial bulunamadı: {$template}");
        }

        extract($data);
        ob_start();
        require $file;
        return ob_get_clean();
    }

    /**
     * Render a view wrapped inside a named layout.
     * The layout receives the rendered inner content as $_content.
     *
     * @param string $layout    Layout name (resolves to app/Views/layouts/{layout}.php)
     * @param string $template  Inner template (dot-separated)
     * @param array  $data      Variables passed to both inner template and layout
     */
    public static function layout(string $layout, string $template, array $data = []): void
    {
        $data['_content'] = self::partial($template, $data);
        self::render("layouts.{$layout}", $data);
    }

    /**
     * Resolve a dot-separated template name to an absolute file path.
     */
    private static function path(string $template): string
    {
        return dirname(__DIR__) . '/Views/' . str_replace('.', '/', $template) . '.php';
    }
}
