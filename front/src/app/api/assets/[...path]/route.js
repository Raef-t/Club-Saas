import { AUTH_SESSION_COOKIE } from "@/lib/authSession";
import { getBackendBaseUrl } from "@/lib/server/backendUrl";

export const dynamic = "force-dynamic";

/**
 * Streams a backend-hosted club asset through the authenticated app origin.
 */
export async function GET(request, context) {
  const { path = [] } = await context.params;
  if (!path.length || path.some((segment) => segment === "." || segment === "..")) {
    return Response.json({ message: "Asset path is required." }, { status: 400 });
  }

  let backendBaseUrl;
  try {
    backendBaseUrl = getBackendBaseUrl();
  } catch {
    return Response.json({ message: "Backend asset origin is unavailable." }, { status: 503 });
  }

  const assetPath = path.map((segment) => encodeURIComponent(segment)).join("/");
  const upstreamUrl = new URL(`/${assetPath}`, backendBaseUrl);
  request.nextUrl.searchParams.forEach((value, key) => {
    if (key !== "v") upstreamUrl.searchParams.append(key, value);
  });

  const headers = new Headers({ Accept: "image/*" });
  const token = request.cookies.get(AUTH_SESSION_COOKIE)?.value;
  if (token) headers.set("Authorization", `Bearer ${token}`);

  try {
    const response = await fetch(upstreamUrl, {
      headers,
      cache: "no-store",
      signal: request.signal,
    });
    const contentType = response.headers.get("content-type") || "";

    if (!response.ok) {
      return Response.json({ message: "Could not load club logo." }, { status: response.status });
    }
    if (!contentType.toLowerCase().startsWith("image/")) {
      return Response.json({ message: "The requested asset is not an image." }, { status: 415 });
    }

    return new Response(response.body, {
      status: 200,
      headers: {
        "Content-Type": contentType,
        "Cache-Control": "private, no-store, max-age=0",
        "X-Content-Type-Options": "nosniff",
      },
    });
  } catch {
    return Response.json({ message: "Could not connect to the asset server." }, { status: 502 });
  }
}
