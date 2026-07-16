import { clientsClaim } from "workbox-core";
import { ExpirationPlugin } from "workbox-expiration";
import { precacheAndRoute, createHandlerBoundToURL } from "workbox-precaching";
import { registerRoute } from "workbox-routing";
import { StaleWhileRevalidate } from "workbox-strategies";
import type { PrecacheEntry } from "workbox-precaching/_types";

declare const __WB_MANIFEST: PrecacheEntry[];

const fileExtensionRegexp = new RegExp("/[^/?]+\\.[^/]+$");

clientsClaim();

precacheAndRoute(__WB_MANIFEST);

registerRoute(
  ({ request, url }) => {
    if (request.mode !== "navigate") {
      return false;
    }

    if (url.pathname.startsWith("/_")) {
      return false;
    }

    if (url.pathname.match(fileExtensionRegexp)) {
      return false;
    }

    return true;
  },
  createHandlerBoundToURL(import.meta.env.BASE_URL + "/index.html"),
);

registerRoute(
  ({ url }) =>
    url.origin === self.location.origin && url.pathname.endsWith(".png"),
  new StaleWhileRevalidate({
    cacheName: "images",
    plugins: [new ExpirationPlugin({ maxEntries: 50 })],
  }),
);

const sw = self as unknown as ServiceWorkerGlobalScope;

self.addEventListener("message", (event) => {
  if (event.data && event.data.type === "SKIP_WAITING") {
    sw.skipWaiting();
  }
});
