"use client";

import { useState } from "react";
import { resolveClubLogoUrl } from "@/lib/clubBranding";

/**
 * Renders a club logo with a text fallback when the image is unavailable.
 */
export function ClubLogo({ src, name, className = "size-10" }) {
  const [hasError, setHasError] = useState(false);

  const resolvedSrc = src ? resolveClubLogoUrl({ logo: src }) : "";

  if (!resolvedSrc || hasError) {
    const initial = name ? name.charAt(0).toUpperCase() : "?";

    return (
      <div
        className={`flex items-center justify-center rounded-lg border border-app-line bg-app-card-hover font-bold text-app-yellow ${className}`}
        aria-label={`شعار ${name || "النادي"}`}
      >
        {initial}
      </div>
    );
  }

  return (
    <img
      src={resolvedSrc}
      alt={`شعار ${name || "النادي"}`}
      onError={() => setHasError(true)}
      className={`rounded-lg border border-app-line bg-app-card-hover object-cover ${className}`}
    />
  );
}

/**
 * Renders a consistent badge for the current club status.
 */
export function ClubStatusBadge({ active }) {
  return (
    <span
      className={`inline-flex min-w-20 justify-center rounded-md px-3 py-1 text-xs font-medium ${
        active ? "status-success" : "status-danger"
      }`}
    >
      {active ? "نشط" : "غير نشط"}
    </span>
  );
}
