import { fetchBaseQuery } from "@reduxjs/toolkit/query/react";

/**
 * Shared RTK Query transport. Authentication is supplied by the HttpOnly
 * session cookie and is never exposed to browser JavaScript.
 */
export const backendBaseQuery = fetchBaseQuery({
  baseUrl: "/api/backend",
  credentials: "same-origin",
});
