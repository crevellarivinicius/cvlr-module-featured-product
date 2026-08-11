# ADR 0001: Polling with ETag/304 instead of push (SSE/WebSocket)

## Status

Accepted

## Context

The featured product box displays the salable quantity in near real time.
The frontend needs a transport to keep that number fresh. The candidates:

1. **Plain polling** — `setInterval` + JSON endpoint;
2. **Polling with conditional requests** — same, but the endpoint emits an
   `ETag` and answers `304 Not Modified` when nothing changed;
3. **Server-Sent Events (SSE)** — the browser holds an open HTTP connection
   and the server streams updates;
4. **WebSocket** — full duplex connection through a dedicated server.

Constraints that matter here:

- Magento runs on **PHP-FPM**, where each open connection occupies a worker.
  An SSE endpoint with N concurrent visitors holds N workers busy doing
  nothing most of the time — it competes directly with the workers that render
  pages and process checkouts;
- A WebSocket server is a **separate piece of infrastructure** (Node, Ratchet,
  a managed service) with its own deploy, monitoring and auth story — a heavy
  dependency for a single number on the homepage;
- Reverse proxies and FPC layers commonly found in front of Magento (Varnish,
  Fastly) need special configuration to pass streaming responses through;
- The data changes **infrequently** relative to the polling window (stock
  moves on order placement), so most polls would return the same value.

## Decision

Use **polling with conditional requests** (option 2):

- The endpoint computes an `ETag` from the payload (`sku|qty|is_salable`) and
  compares it with `If-None-Match`;
- When nothing changed, the response is an empty **304** — no body, no JSON
  encoding, minimal bandwidth;
- The Knockout component sends the header automatically (`ifModified: true`)
  and treats `notmodified` as "still fresh";
- Polling pauses while the browser tab is hidden (Page Visibility API), which
  removes the cost of idle tabs entirely.

## Consequences

- Worst-case latency of one polling interval (configurable, default 10s) is
  accepted as adequate for a marketing element;
- Server cost per idle poll is one cheap MSI index read; no extra
  infrastructure, no long-lived connections, works behind any proxy;
- If a future requirement demands sub-second updates at high concurrency,
  the service contract stays the same and only the transport needs replacing
  (e.g. a WebSocket fan-out fed by consumer events) — that migration path is
  the reason the stock logic lives behind `StockInformationInterface`.
