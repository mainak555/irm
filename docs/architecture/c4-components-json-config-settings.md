# C4 Component Diagram — JSON Config Settings

Shows how `config/config.json` flows through the system after the `json-config-settings` change: from the new admin editor through the cfg() helper to both the admin shell and the public view.

```mermaid
C4Component
  title Component Diagram — JSON Config Settings (IRM)

  Person(sa, "Super Admin (sa)", "Edits school identity and theme pack")
  Person(visitor, "Site Visitor", "Views public-facing pages")

  Container_Boundary(admin, "IRM Admin Panel (PHP SSR)") {
    Component(layout, "admin/_layout.php", "PHP", "Renders sidebar with Settings → General link")
    Component(config_general, "admin/config_general.php", "PHP / sa only", "GET: renders identity form + theme dropdown. POST: merges fields, writes config.json")
    Component(cfg, "config.php · cfg()", "PHP / static cache", "Dot-notation reader for config.json, cached per request")
    Component(header_inc, "includes/header.php", "PHP", "Emits inline CSS custom properties from cfg('colors')")
  }

  Container_Boundary(public, "IRM Public View (PHP SSR)") {
    Component(pub_view, "index.php / page.php", "PHP", "Loads active theme pack CSS via cfg('public.theme')")
  }

  ComponentDb(config_json, "config/config.json", "JSON file", "general.*, colors.*, footer.*, public.theme slug")
  ComponentDb(themes_dir, "public/css/themes/*.css", "CSS files", "Filesystem-discovered theme packs")

  Rel(sa, layout, "Navigates to General", "HTTP")
  Rel(layout, config_general, "Links to", "HTML anchor")
  Rel(sa, config_general, "Submits form", "HTTP POST")

  Rel(config_general, config_json, "Reads on GET / writes on POST", "file_get_contents · file_put_contents LOCK_EX")
  Rel(config_general, themes_dir, "Scans for available packs", "glob()")

  Rel(cfg, config_json, "Reads once per request", "file_get_contents · static cache")
  Rel(config_general, cfg, "Uses for current values")
  Rel(header_inc, cfg, "cfg('colors') → CSS vars")
  Rel(pub_view, cfg, "cfg('public.theme') → pack slug")
  Rel(pub_view, themes_dir, "Loads active pack", "HTML link rel=stylesheet")

  Rel(visitor, pub_view, "Views page", "HTTP")

  UpdateLayoutConfig($c4ShapeInRow="3", $c4BoundaryInRow="1")
```

## Key flows

| Flow | Path |
|---|---|
| Admin edits school identity | SA → `config_general.php` POST → `config.json` (LOCK_EX + backup) |
| Admin selects theme pack | SA → `config_general.php` POST → `config.json → public.theme` (slug from glob scan) |
| Public view reads identity | `pub_view` → `cfg()` → `config.json → general.*` |
| Public view loads theme CSS | `pub_view` → `cfg('public.theme')` → `public/css/themes/{slug}.css` |
| Admin chrome reads branding | `_layout.php` → `cfg('general.title')`, `cfg('general.logoUrl')` |
| CSS custom properties | `header.php` → `cfg('colors')` → inline `<style>:root{…}` |
