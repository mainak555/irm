<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function render_page(array $page_data): void
{
    $layout_pack = basename((string)cfg('public.layout', ''));
    $layout_id   = preg_replace('/[^a-z0-9-]/', '', (string)($page_data['layout_id'] ?? ''));

    $page_file = ($layout_pack !== '' && $layout_id !== '')
        ? __DIR__ . '/../assets/css/layouts/' . $layout_pack . '/pages/' . $layout_id . '.php'
        : '';

    $slots = is_array($page_data['slots'] ?? null) ? $page_data['slots'] : [];

    if ($page_file !== '' && is_file($page_file)) {
        $render_slot = fn(string $slot_id) => _render_col($slots[$slot_id] ?? []);
        require $page_file;
    } else {
        render_page_layout($page_data['rows'] ?? []);
    }
}

function render_page_layout(array $rows): void
{
    $layout_pack  = basename((string)cfg('public.layout', ''));
    $sections_dir = $layout_pack !== ''
        ? __DIR__ . '/../assets/css/layouts/' . $layout_pack . '/sections/'
        : '';

    foreach ($rows as $row) {
        $cols = $row['cols'] ?? [];
        if (empty($cols)) {
            continue;
        }
        $variant = preg_replace('/[^a-z0-9-]/', '', (string)($row['variant'] ?? ''));

        $section_file = ($variant !== '' && $sections_dir !== '')
            ? $sections_dir . $variant . '.php'
            : '';

        if ($section_file !== '' && is_file($section_file)) {
            // Layout template handles all HTML structure for this section.
            // $cols and $render_col are available inside the template.
            $render_col = fn(array $col) => _render_col($col);
            require $section_file;
        } else {
            // Fallback: plain Bootstrap row/col (no layout pack or unknown variant).
            $row_class    = 'row irm-row' . ($variant !== '' ? ' irm-row--' . $variant : '');
            $variant_attr = $variant !== '' ? ' data-variant="' . h($variant) . '"' : '';
            echo '<div class="' . $row_class . '"' . $variant_attr . '>';
            foreach ($cols as $col) {
                echo '<div class="col">';
                _render_col($col);
                echo '</div>';
            }
            echo '</div>';
        }
    }
}

function _render_col(array $col): void
{
    $type = $col['type'] ?? '';
    match ($type) {
        'html'      => _render_html($col),
        'embed'     => _render_embed($col),
        'component' => _render_component($col),
        default     => null,
    };
}

function _render_html(array $col): void
{
    echo $col['html'] ?? '';
}

function _render_embed(array $col): void
{
    $url     = h((string)($col['url'] ?? ''));
    $title   = h((string)($col['title'] ?? 'Embedded content'));
    $subtype = $col['subtype'] ?? '';

    if ($url === '') {
        return;
    }

    match ($subtype) {
        'youtube', 'vimeo' => print(
            '<div class="ratio ratio-16x9">'
            . '<iframe src="' . $url . '" title="' . $title . '"'
            . ' allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"'
            . ' allowfullscreen loading="lazy"></iframe>'
            . '</div>'
        ),
        'pdf' => print(
            '<object data="' . $url . '" type="application/pdf"'
            . ' style="width:100%;min-height:600px">'
            . '<p>PDF: <a href="' . $url . '">' . $title . '</a></p>'
            . '</object>'
        ),
        'mp4' => print(
            '<video controls style="width:100%">'
            . '<source src="' . $url . '" type="video/mp4">'
            . '</video>'
        ),
        'website' => print(
            '<iframe src="' . $url . '" title="' . $title . '"'
            . ' style="width:100%;min-height:500px;border:0" loading="lazy"></iframe>'
        ),
        default => null,
    };
}

function _render_component(array $col): void
{
    $raw  = (string)($col['name'] ?? '');
    $name = preg_replace('/[^a-z0-9_-]/', '', $raw);
    if ($name === '') {
        return;
    }
    $path = __DIR__ . '/../components/' . $name . '.php';
    if (is_file($path)) {
        require $path;
    } else {
        echo '<div class="irm-component-missing">[' . h($name) . ']</div>';
    }
}
