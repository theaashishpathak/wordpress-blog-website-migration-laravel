# Rupantrix — Complete Project & Architecture Guide
### A comprehensive study document and reference manual covering architecture, workflows, completed milestones, and roadmap

*Prepared for Aashish Pathak — last updated September 2026*

---

## How to Use This Document

This document serves as your single source of truth for the **Rupantrix** platform. Read it top to bottom to gain a holistic view of the system, and reference specific sections during development, maintenance, and deployments. Key concepts and architectural conventions are explained in detail so that any team member can follow along without friction.

---

## 1. What Rupantrix Is — The Big Picture

Rupantrix is an **AI-powered, multi-role news and magazine CMS** engineered on modern Laravel and Livewire. It functions as an enterprise digital newsroom suite that allows journalists, editors, authors, managers, and subscribers to collaborate within dedicated environments.

### Project Origins & Evolution
The platform originated from a legacy WordPress editorial site that utilized:
- **Elementor** for page layout construction
- **Yoast SEO / RankMath** for search engine metadata and schema definitions
- Legacy WordPress attachment structures for media storage

The migration to the Rupantrix Laravel platform was undertaken to achieve:
1. **Uncompromised Performance & Clean Data Architecture:** Eliminating bloated third-party plugin queries in favor of optimized relational schemas and caching.
2. **Dedicated Role-Specific Dashboards:** Providing distinct portals (Admin, Author, and Visitor) with granular permissions that WordPress cannot deliver natively out of the box.
3. **Multi-Language Foundation:** Structuring posts, categories, and tags with a normalized localization architecture (`posts` + `post_translations`).
4. **Custom AI & Editorial Workflows:** Enabling automated content workflows, prompt-driven editorial assistance, and high-concurrency background processing.

---

## 2. Core Laravel Architectural Concepts

Laravel follows the **Model-View-Controller (MVC)** architectural pattern:

* **Model (`app/Models/`):** Eloquent PHP classes representing database entities (e.g., `Post.php`, `User.php`, `Media.php`). Models handle business rules, scopes, and database relationships.
* **View (`resources/views/`):** Blade templates combining semantic HTML with reactive directives.
* **Controller (`app/Http/Controllers/`):** Coordinates incoming requests, invokes service/model layers, and returns responses.
* **Livewire Components (`app/Livewire/`):** Full-stack reactive components pairing PHP state with Blade templates without requiring manual REST endpoints.

### Directory Structure Guide

| Directory | Responsibility |
|---|---|
| `app/Models/` | Eloquent entity definitions and relationship mappings |
| `app/Livewire/` | Reactive UI controllers (Admin, Author, Visitor, and Frontend) |
| `app/Services/` | Core business logic (Settings, Migration, Content Transformation) |
| `app/Console/Commands/` | Custom Artisan CLI tooling (e.g., `wp:download-media`) |
| `routes/` | HTTP entry points (`web.php`, `admin.php`, `settings.php`, `console.php`) |
| `resources/views/` | Blade templates and layouts |
| `database/migrations/` | Version-controlled database schema definitions |
| `database/seeders/` | Database population scripts (e.g., `RupantrixDemoSeeder.php`) |
| `public/` | The public web root — the only folder directly exposed by the web server |
| `storage/` | Framework logs, file caches, and user-uploaded media (`storage/app/public`) |

---

## 3. Technology Stack & Key Dependencies

| Technology | Role | Purpose |
|---|---|---|
| **Laravel 13** | Core Framework | Application lifecycle, routing, Eloquent ORM, middleware, service container |
| **PHP 8.3+** | Runtime | Modern syntax, typed properties, strict typing, performance enhancements |
| **Livewire 4** | Reactive Frontend | Server-driven real-time interfaces without writing custom JavaScript |
| **TailwindCSS v4 / Vite** | Styling & Bundling | Modern styling system with pre-compiled production assets in `public/build` |
| **Alpine.js** | Micro-Interactivity | Local DOM manipulation, dropdowns, and TipTap editor bridging |
| **Spatie Permission** | RBAC Engine | 10 distinct staff and visitor roles with granular permission checks |
| **TipTap Editor** | Rich Text Engine | Headless rich-text editor for authors and editorial staff |
| **Laravel Fortify** | Authentication | Headless auth handling registration, login throttling, and password resets |

---

## 4. Platform Architecture — The Three Portals

Rupantrix implements three segregated operational portals:

```
/dashboard/my           → Super Admin & Executive Command Centre
/dashboard/author       → Editorial & Creator Studio
/visitor/dashboard      → Reader, Bookmarks & Personalized Hub
/login                  → Unified Secure Authentication Gateway
/admin                  → Automatic Redirect to /dashboard
```

### Routing & Portal Resolution
Every account contains a `portal_type` attribute (`admin`, `author`, `visitor`):
* Upon login through Fortify, `routes/web.php` and custom login responses inspect `portal_type` and user roles to route them to their specialized workspace.
* Staff members without administrative privileges navigate directly to `/dashboard/author`.
* Public subscribers land in `/visitor/dashboard`.
* Direct access to `/admin` automatically forwards to `/dashboard` (prompting unauthenticated visitors to `/login`).

---

## 5. Roles & Permissions (RBAC)

The system features **10 hierarchical roles**:

1. **Super Admin:** Unrestricted global system control and infrastructure management.
2. **Admin:** Operational site administration, user management, and configuration.
3. **Manager:** Editorial oversight, categories, and staff workflows.
4. **Editor:** Article review, approval, publishing, and scheduling.
5. **Author:** Post drafting, submission, and profile management.
6. **Contributor:** Article pitching and draft submissions requiring review.
7. **Employee:** Internal organizational operations.
8. **Ad Manager:** Advertising zones, banners, and click analytics.
9. **SEO Manager:** Metadata overrides, canonical tagging, and sitemap management.
10. **Subscriber:** Reading history, bookmarks, reactions, and newsletter subscriptions.

### Implementation Pattern
Permissions are enforced at the route level via middleware (`permission:posts.create`) and within Blade templates via directives:

```blade
@can('subscribers.promote')
    <button wire:click="promoteToAuthor({{ $user->id }})">Promote to Author</button>
@endcan
```

---

## 6. Database Architecture

### 6.1 Multi-Language Entity Separation (`posts` vs `post_translations`)

To support multi-language scalability without schema restructuring, all translatable models follow a decoupled schema:

```
posts (Structural Metadata)          post_translations (Localized Content)
---------------------------          --------------------------------------
id (Primary Key)                     id (Primary Key)
author_id                            post_id (Foreign Key referencing posts.id)
category_id                          language_id (Foreign Key referencing languages.id)
featured_image_id                    title
status (published, draft, etc.)      slug
published_at                         excerpt
is_featured / is_trending            content (HTML article body)
source_url                           meta_title, meta_description
```

> **Key Rule:** Structural properties (status, author, timestamps) live on `Post`. Textual and SEO attributes (title, slug, content) live on `PostTranslation`. Queries accessing content must always eager-load `translations` or utilize `$post->translation()`.

### 6.2 The `portal_type` Trap
When provisioning users programmatically, `portal_type` must be explicitly specified (`visitor`, `author`, or `admin`) to prevent defaulting to incorrect dashboard privileges.

---

## 7. Media Pipeline & WordPress Importer

### High-Concurrency Asset Retrieval
Rather than requiring manual multi-gigabyte zip uploads over FTP, Rupantrix includes a high-performance streaming downloader:

```bash
php artisan wp:download-media --concurrency=15
```

#### Pipeline Capabilities:
* **Concurrent HTTP Pool:** Downloads batches of 15–20 images simultaneously via server-to-server connections.
* **Fault-Tolerant Response Handling:** Validates that incoming responses are genuine instances of `\Illuminate\Http\Client\Response`, smoothly bypassing dead external links or timeouts without terminating the batch.
* **Idempotent Execution:** Verifies whether a file already exists locally in `storage/app/public/media` before downloading, allowing safe resumption.
* **Local Storage Integration:** Persists assets into date-partitioned storage directories (`storage/app/public/media/YYYY/MM/`) and records metadata directly into the `media` database table.

---

## 8. Production Deployment Guide (Hostinger)

Rupantrix is actively deployed and configured for production on **Hostinger Shared/Cloud Hosting**.

* **Live Staging Domain:** `whitesmoke-panther-461035.hostingersite.com`
* **Target Runtime:** PHP 8.3 CLI (`/opt/alt/php83/usr/bin/php`)
* **Web Server Document Root:** Routed via root `.htaccess` rewriting into `public/`

### Automated Deployment Script (`deploy.sh`)

A production deployment runner (`deploy.sh`) is maintained in the project root to automate deployments via SSH:

```bash
./deploy.sh
```

#### What `deploy.sh` executes:
1. **PHP 8.3 Auto-Detection:** Dynamically locates Hostinger's PHP 8.3 binary (`/opt/alt/php83/usr/bin/php` or `php8.3`).
2. **Maintenance Mode:** Engages `artisan down --retry=60` during asset swaps.
3. **Branch Sync:** Pulls latest updates from the tracked branch (`main`).
4. **Dependency Management:** Executes `composer install --no-dev --optimize-autoloader`.
5. **Application Key & Storage:** Ensures `APP_KEY` is present and runs `artisan storage:link`.
6. **Database Migration:** Executes pending migrations safely with `artisan migrate --force`.
7. **Cache Optimization:** Clears and re-warms configuration, route, and Blade view caches:
   - `artisan config:cache`
   - `artisan route:cache`
   - `artisan view:cache`
8. **Permission Hardening:** Enforces `chmod -R 775 storage bootstrap/cache`.
9. **Site Activation:** Brings the application out of maintenance mode (`artisan up`).

---

## 9. Completed Fixes & Engineering Milestones

The following issues were systematically investigated, patched, and verified in production:

1. **Hostinger CLI PHP Version Alignment:**
   - Identified that default CLI invoked PHP 8.2 while Laravel 13 required PHP 8.3. Configured scripts to target `/opt/alt/php83/usr/bin/php`.

2. **Composer Schema Compliance:**
   - Corrected package declaration in `composer.json` to lowercase `"name": "rupantrix-ai/app"`.

3. **Portal Layout Component Namespace:**
   - Resolved `ComponentTagCompiler` failure where `resources/views/layouts/portal.blade.php` referenced undefined `<x-client.*>` tags; normalized to `<x-visitor.*>`.

4. **Livewire Frontend Pagination (`gotoPage`):**
   - Fixed `MethodNotFoundException` on category and tag feeds by incorporating `use WithPagination;` inside the class declarations of `CategoryShow.php` and `TagShow.php`.

5. **Media Pipeline Robustness:**
   - Refactored `app/Console/Commands/WpDownloadMedia.php` to handle `RequestException` instances gracefully, completing the transfer of **2,593 live media assets**.

6. **Route Accessibility & Admin Redirects:**
   - Added `Route::redirect('/admin', '/dashboard')` in `routes/web.php` to prevent 404 slug fallbacks when accessing the administration area.

7. **Vite Asset Tracking:**
   - Pre-compiled production bundles (`app.css`, `app.js`, `manifest.json`) in `public/build` for zero-dependency shared hosting environments.

8. **Repository Sanitation:**
   - Removed all legacy branding artifacts from templates, seeders (`RupantrixDemoSeeder.php`), tests, package locks, and code comments.
   - Unified commit histories cleanly onto the primary `main` branch.

---

## 10. Roadmap & Upcoming Milestones

| Milestone | Component | Description | Status |
|---|---|---|---|
| **Phase 2** | Data Migration | Import WordPress users, categories, media metadata, and post archives | ✅ Complete |
| **Phase E** | Hostinger Deployment | Server provisioning, PHP 8.3 configuration, automated `deploy.sh` script | ✅ Complete |
| **Phase Media** | Asset Retrieval | High-speed server-side migration of 2,500+ physical image files | ✅ Complete |
| **Phase A.1** | Subscribers System | Dedicated `/admin/subscribers` dashboard with promotion & status toggles | ✅ Complete |
| **Phase A.2** | Public Registration | Fortify visitor registration with automatic role assignment and transactions | ✅ Complete |
| **Phase A.3** | Livewire Pagination | `WithPagination` integration across Category and Tag feeds | ✅ Complete |
| **Phase A.4** | TipTap Rich Text Editor | Validate `@js($value)` initialization and `:value` property binding | 🔶 Designed & Ready for Test |
| **Phase A.5** | Route Binding Verification | Verify legacy ID vs primary key resolution in single post route model binding | 🔴 In Progress |
| **Phase A.6** | Backlink Audit & Cleanup | Execute scanning and regex stripping of legacy spam links in `post_translations` | 🔴 Planned |
| **Phase C** | SEO & 301 Redirects | Verify canonical headers, Open Graph tags, sitemap.xml, and 301 URL redirects | ⬜ Next Phase |
| **Phase D** | Admin Panel Polish | Super Admin UI enhancements, activity log filters, and analytics widgets | ⬜ Planned |
| **Phase Multi-Lang**| Bengali Localization | Populate localized translations in `post_translations` via language selector | ⏸ Deferred |

---

## 11. Developer Troubleshooting Reference

### Storage Permission Issues
If uploaded images or cached views fail to load:
```bash
chmod -R 775 storage bootstrap/cache
php artisan storage:link
```

### Cache Invalidation
When modifying environment variables or configuration files:
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Testing Database Records via Tinker
To safely inspect records on the live server:
```bash
php artisan tinker
>>> \App\Models\Post::count();
>>> \App\Models\Media::count();
```
