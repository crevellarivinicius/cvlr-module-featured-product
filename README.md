# Crevellari_FeaturedProduct

[![CI](https://github.com/crevellarivinicius/magento2-module-featured-product/actions/workflows/ci.yml/badge.svg)](https://github.com/crevellarivinicius/magento2-module-featured-product/actions/workflows/ci.yml)
![Magento](https://img.shields.io/badge/Magento-2.4.6%20Open%20Source-ee672f)
![PHP](https://img.shields.io/badge/PHP-8.1%20%7C%208.2-777bb4)
![License](https://img.shields.io/badge/license-OSL--3.0-2ea44f)

**English** · [Português (Brasil)](README.pt-BR.md)

A Magento 2 module that renders a **featured product box on the homepage** with the **quantity available for sale updated in near real time** — no page reloads.

![Featured product box on the Luma homepage](docs/home-desktop.png)

| Low stock | Out of stock | Mobile |
|---|---|---|
| ![Low stock](docs/home-lowstock.png) | ![Out of stock](docs/home-outofstock.png) | ![Mobile](docs/home-mobile.png) |

## Features

- Featured product box as the **first element of the homepage main content**, full width, with title, price (native pricing render pipeline), base image and a link to the product page;
- **Quantity available for sale in real time** — the MSI *salable quantity* (physical stock minus reservations), polled by a Knockout component on a configurable interval;
- **Cheap polling**: the endpoint emits an `ETag` and answers an empty `304 Not Modified` while nothing changes; polling pauses when the browser tab is hidden;
- Product selected **by SKU in the admin panel** (validated on save), with refresh interval and low-stock threshold — all store-view scoped;
- Low stock ("Last units!"), out of stock and network error states, styled with the Luma theme's own message colors;
- Homepage **full page cache invalidated automatically** when the featured product changes (`IdentityInterface`);
- The same service contract feeds the storefront, a **REST endpoint** and a **GraphQL query**;
- **Hyvä-ready**: an Alpine.js/Tailwind template variant ships with the module;
- `en_US` and `pt_BR` translations, unit and integration tests, CI with the Magento coding standard.

No theme files are modified: everything lives inside the module.

## Installation

### Composer

```bash
composer require crevellari/module-featured-product
bin/magento module:enable Crevellari_FeaturedProduct
bin/magento setup:upgrade
bin/magento setup:di:compile   # production mode
bin/magento cache:flush
```

From a local clone (path repository):

```bash
composer config repositories.crevellari path extensions/crevellari/module-featured-product
composer require crevellari/module-featured-product:@dev
```

### app/code

```bash
mkdir -p app/code/Crevellari/FeaturedProduct
# copy the module contents into the folder above
bin/magento module:enable Crevellari_FeaturedProduct
bin/magento setup:upgrade
bin/magento cache:flush
```

## Configuration

**Stores → Configuration → Catalog → Featured Product**

| Field | Description | Default |
|---|---|---|
| Enabled | Toggles the box on the homepage | Yes |
| Product SKU | SKU of the featured product — validated against the catalog on save | `24-MB01` |
| Stock Refresh Interval (seconds) | Polling interval (5–300s, also enforced server-side) | 10 |
| Low Stock Threshold | At or below this quantity the box shows the "Last units!" highlight (0 disables) | 5 |

All fields are **store-view scoped** and restorable to system values (`canRestore`).

## How the real-time update works

1. The Knockout component (`view/frontend/web/js/view/stock.js`) fetches once on load and schedules a `setInterval` with the configured interval;
2. Each cycle calls `GET /featuredproduct/stock/get` — a thin controller (`HttpGetActionInterface`) that only delegates to the service contract and formats the JSON;
3. The service (`Api\StockInformationInterface` → `Model\StockInformation`) reads the MSI salable quantity (`GetProductSalableQtyInterface`) resolved for the current website, falling back to the legacy stock item for product types without source item management;
4. The response updates the observables and the Knockout template re-renders only the stock indicator.

Cost control details:

- The displayed number is the **salable quantity** (physical − reservations): it drops the moment an order is placed, with no reindex involved;
- Responses carry an **`ETag`**; the component sends `If-None-Match`, so unchanged polls come back as an empty **304** ([ADR 0001](docs/adr/0001-polling-with-etag-over-push.md) documents why this beats SSE/WebSocket here);
- Polling **pauses while the tab is hidden** (Page Visibility API) and refreshes immediately on return;
- On network failures the last known value stays on screen with a discreet notice, and the component keeps retrying;
- The SKU never travels in the request — the endpoint reads it from configuration server-side.

## APIs

The same service contract is exposed over REST:

```
GET /rest/V1/featured-product/stock
```

And over GraphQL:

```graphql
{
    featuredProductStock {
        sku
        qty
        is_salable
        updated_at
    }
}
```

The GraphQL query is marked non-cacheable, matching the real-time nature of the data.

## Hyvä

An Alpine.js + Tailwind CSS variant of the box ships at
`view/frontend/templates/hyva/product.phtml` (same view model, `fetch` with
`If-None-Match`, visibility-aware polling). Point the block template at it from
your Hyvä theme, or map it via
[hyva-themes/module-compat-module-fallback](https://gitlab.hyva.io/hyva-themes/magento2-compat-module-fallback).

## Architecture

| Layer | Files | Role |
|---|---|---|
| Service contract | `Api/StockInformationInterface` + `Model/StockInformation` | Single owner of the stock business logic, consumed by the controller, REST, GraphQL and the server-side render |
| DTO | `Api/Data/StockInterface` + `Model/Data/Stock` | Typed payload |
| Controller | `Controller/Stock/Get.php` | Thin: HTTP → service → JSON, plus the ETag/304 handshake |
| GraphQL | `etc/schema.graphqls` + `Model/Resolver/FeaturedProductStock` | Query resolver delegating to the same service |
| ViewModel | `ViewModel/FeaturedProduct.php` | Product data for the template (no fat blocks) |
| Block | `Block/FeaturedProduct.php` | Merges runtime config into the declared `jsLayout` (checkout pattern) and exposes cache identities for FPC invalidation |
| Layout | `view/frontend/layout/cms_index_index.xml` | Reference container/block, block arguments, CSS loaded only on the homepage |
| JS | `view/frontend/web/js/view/stock.js` | Knockout `uiComponent` loaded via RequireJS only where used |
| Admin config | `etc/adminhtml/system.xml` + `Model/Config/Backend/Sku` | Dedicated section, ACL, defaults and SKU validation on save |

Native layout mechanisms demonstrated: block declared through `referenceContainer`/`referenceBlock`, configured with **block arguments** (view model, title, `jsLayout`), the jsLayout structure declared in XML and enriched at runtime by `Block::getJsLayout()`, and a **Knockout** component with its own KO template.

### Design aligned with Luma

No invented visual vocabulary: the stock states reuse the theme's message
colors (success `#e5efe5/#006400`, warning `#fdf0d5/#6f4400`, error
`#fae5e5/#e02b27`), the CTA mirrors the Luma primary button (`#1979c3`, hover
`#006bb4`, 3px radius), the heading uses the theme's `font-weight: 300` and
borders/secondary text use the standard grays. The box reads as a native part
of the theme.

### Other care taken

- `declare(strict_types=1)` and full type coverage; constructor DI only, no direct ObjectManager, no logic in templates or controllers;
- Accessibility: `aria-live` limited to the quantity strip, visible focus, `prefers-reduced-motion` support, catalog-driven image alt;
- A missing or disabled SKU never breaks the homepage — the box simply does not render (with a log notice);
- Graceful visual states: initial server-side rendered value (no empty flash), update pulse, low stock, out of stock, network error.

## Tests

Unit tests (from a Magento installation):

```bash
cd dev/tests/unit
../../../vendor/bin/phpunit ../../../app/code/Crevellari/FeaturedProduct/Test/Unit
```

Integration tests (require the [integration test framework](https://developer.adobe.com/commerce/testing/guide/integration/)):

```bash
cd dev/tests/integration
../../../vendor/bin/phpunit ../../../app/code/Crevellari/FeaturedProduct/Test/Integration
```

CI runs a PHP 8.1/8.2 syntax check and `phpcs` with the `Magento2` ruleset on every push.

## Compatibility

- Magento 2.4.6 Open Source (PHP 8.1/8.2), Luma theme; Hyvä template variant included.

## License

[OSL-3.0](COPYING.txt) — the same license used by Magento Open Source.
See [CHANGELOG.md](CHANGELOG.md) for release notes.
