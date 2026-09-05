import { NextResponse } from "next/server";
import {
  AUTH_SESSION_COOKIE,
  AUTH_SETUP_COOKIE,
  AUTH_USER_META_COOKIE,
  getAuthCookieOptions,
} from "@/lib/authSession";
import { MANAGEMENT_BRANCH_COOKIE } from "@/lib/managementBranchUtils";
import { getBackendBaseUrl } from "@/lib/server/backendUrl";

export const dynamic = "force-dynamic";

const METHODS_WITH_BODY = new Set(["POST", "PUT", "PATCH", "DELETE"]);
const PUBLIC_API_PATHS = new Set(["auth/login", "auth/forgot-password"]);

function jsonError(message, status) {
  return NextResponse.json(
    {
      status: "error",
      message,
    },
    {
      status,
      headers: {
        "Cache-Control": "no-store",
      },
    },
  );
}

function isSecureRequest(request) {
  const forwardedProtocol = request.headers
    .get("x-forwarded-proto")
    ?.split(",", 1)[0]
    ?.trim()
    ?.toLowerCase();

  return forwardedProtocol ? forwardedProtocol === "https" : request.nextUrl.protocol === "https:";
}

function clearAuthCookie(response, secure = false) {
  response.cookies.set(AUTH_SESSION_COOKIE, "", {
    ...getAuthCookieOptions(false, secure),
    maxAge: 0,
  });
  response.cookies.set(AUTH_SETUP_COOKIE, "", {
    ...getAuthCookieOptions(false, secure),
    maxAge: 0,
  });
  response.cookies.set(AUTH_USER_META_COOKIE, "", {
    ...getAuthCookieOptions(false, secure),
    maxAge: 0,
  });

  return response;
}

function getFirstHeaderValue(request, name) {
  return request.headers.get(name)?.split(",", 1)[0]?.trim();
}

function isAccountSetupPasswordChange(body) {
  if (!body) return false;

  try {
    const payload = JSON.parse(new TextDecoder().decode(body));
    return Boolean(payload?.user_id && payload?.new_password);
  } catch {
    return false;
  }
}

/**
 * Rejects cross-origin mutations while allowing trusted server requests that
 * legitimately omit the Origin header. Behind a reverse proxy, nextUrl may
 * contain the internal host, so also compare against the public forwarded/Host
 * headers supplied by the proxy.
 */
function hasTrustedOrigin(request) {
  const origin = request.headers.get("origin");
  if (!origin) {
    return true;
  }

  const publicHost =
    getFirstHeaderValue(request, "x-forwarded-host") || getFirstHeaderValue(request, "host");
  const publicProtocol =
    getFirstHeaderValue(request, "x-forwarded-proto") || request.nextUrl.protocol.replace(/:$/, "");
  const allowedOrigins = new Set([request.nextUrl.origin]);

  if (publicHost && publicProtocol) {
    try {
      allowedOrigins.add(new URL(`${publicProtocol}://${publicHost}`).origin);
    } catch {
      return false;
    }
  }

  try {
    return allowedOrigins.has(new URL(origin).origin);
  } catch {
    return false;
  }
}

async function proxyBackendRequest(request, context) {
  const secureCookie = isSecureRequest(request);
  const { path = [] } = await context.params;
  const pathName = path.join("/");
  const token =
    request.headers.get("authorization")?.replace(/^Bearer\s+/i, "") ||
    request.cookies.get(AUTH_SESSION_COOKIE)?.value;
  const requiresAuth = !PUBLIC_API_PATHS.has(pathName);

  if (METHODS_WITH_BODY.has(request.method) && !hasTrustedOrigin(request)) {
    return jsonError("Cross-origin request is not allowed.", 403);
  }

  if (requiresAuth && !token) {
    return jsonError("Authentication is required.", 401);
  }

  if (!path.length) {
    return jsonError("API path is required.", 400);
  }

  let backendBaseUrl;
  try {
    backendBaseUrl = getBackendBaseUrl();
  } catch {
    return jsonError("Backend API is not securely configured.", 503);
  }

  const upstreamPath = path.map((segment) => encodeURIComponent(segment)).join("/");
  const upstreamUrl = new URL(`/api/v1/${upstreamPath}`, backendBaseUrl);

  request.nextUrl.searchParams.forEach((value, key) => {
    upstreamUrl.searchParams.append(key, value);
  });

  const headers = new Headers();
  const accept = request.headers.get("accept") || "application/json";
  const acceptsEventStream = accept.toLowerCase().includes("text/event-stream");
  headers.set("Accept", accept);

  if (acceptsEventStream) {
    headers.set("Accept-Encoding", "identity");
    headers.set("Cache-Control", "no-cache");

    const lastEventId = request.headers.get("last-event-id");
    if (lastEventId) {
      headers.set("Last-Event-ID", lastEventId);
    }
  }

  if (token) {
    headers.set("Authorization", `Bearer ${token}`);
  }

  const init = {
    method: request.method,
    headers,
    cache: "no-store",
    redirect: "manual",
    signal: request.signal,
  };
  let requestBody = null;

  if (METHODS_WITH_BODY.has(request.method)) {
    const contentType = request.headers.get("content-type");
    if (contentType) {
      headers.set("Content-Type", contentType);
    }

    requestBody = await request.arrayBuffer();
    init.body = requestBody;
  }

  try {
    const response = await fetch(upstreamUrl, init);

    if (response.status >= 300 && response.status < 400) {
      return jsonError("Backend API URL must use its final HTTPS address.", 502);
    }

    const responseContentType = response.headers.get("content-type") || "application/json";
    const normalizedResponseContentType = responseContentType.toLowerCase();

    if (normalizedResponseContentType.includes("text/event-stream") && response.body) {
      return new Response(response.body, {
        status: response.status,
        headers: {
          "Content-Type": responseContentType,
          "Cache-Control": "no-cache, no-store, no-transform, must-revalidate",
          Connection: "keep-alive",
          "X-Accel-Buffering": "no",
          "X-Content-Type-Options": "nosniff",
        },
      });
    }

    const isBinaryResponse =
      normalizedResponseContentType.includes("zip") ||
      normalizedResponseContentType.includes("octet-stream") ||
      normalizedResponseContentType.includes("pdf");
    let body = isBinaryResponse ? await response.arrayBuffer() : await response.text();
    let sessionToken = null;
    let requiresAccountSetup = false;
    let userMeta = null;

    if (pathName === "auth/login" && response.ok) {
      try {
        const payload = JSON.parse(body);
        // Backend could return the token as `token` or `access_token`
        sessionToken = payload?.data?.token || payload?.data?.access_token;

        if (!sessionToken) {
          return jsonError("Login response does not include an access token.", 502);
        }

        const user = payload?.data?.user;
        requiresAccountSetup = Boolean(user?.must_change_password || !user?.custom_username);

        if (user) {
          userMeta = {
            branch_id: user?.branch_id ?? null,
            staff_id: user?.staff_id ?? null,
            custom_username: user?.custom_username ?? null,
            full_name: user?.full_name ?? null,
          };
        }

        const clientPayload = {
          ...payload,
          data: {
            ...payload.data,
            requires_account_setup: requiresAccountSetup,
          },
        };

        // Remove tokens from the client response to enforce security
        delete clientPayload.data.token;
        delete clientPayload.data.access_token;
        delete clientPayload.data.token_type;
        body = JSON.stringify(clientPayload);
      } catch {
        return jsonError("Login response is not valid JSON.", 502);
      }
    }

    const responseHeaders = {
      "Content-Type": responseContentType,
      "Cache-Control": "no-store",
    };
    const contentDisposition = response.headers.get("content-disposition");
    if (contentDisposition) {
      responseHeaders["Content-Disposition"] = contentDisposition;
    }

    const nextResponse = new NextResponse(body, {
      status: response.status,
      headers: responseHeaders,
    });

    if (sessionToken) {
      const remember = request.headers.get("x-remember-me")?.toLowerCase() === "true";
      const cookieOptions = getAuthCookieOptions(remember, secureCookie);

      nextResponse.cookies.set(AUTH_SESSION_COOKIE, sessionToken, cookieOptions);

      if (userMeta) {
        nextResponse.cookies.set(AUTH_USER_META_COOKIE, JSON.stringify(userMeta), cookieOptions);
        if (userMeta.branch_id) {
          nextResponse.cookies.set(MANAGEMENT_BRANCH_COOKIE, String(userMeta.branch_id), {
            path: "/",
            sameSite: "lax",
            secure: secureCookie,
            maxAge: 31536000,
          });
        }
      }

      if (requiresAccountSetup) {
        nextResponse.cookies.set(AUTH_SETUP_COOKIE, "required", cookieOptions);
      } else {
        nextResponse.cookies.set(AUTH_SETUP_COOKIE, "", {
          ...getAuthCookieOptions(false, secureCookie),
          maxAge: 0,
        });
      }
    }

    if (
      pathName === "auth/change-password" &&
      response.ok &&
      isAccountSetupPasswordChange(requestBody)
    ) {
      nextResponse.cookies.set(AUTH_SETUP_COOKIE, "", {
        ...getAuthCookieOptions(false, secureCookie),
        maxAge: 0,
      });
    }

    if (pathName === "auth/logout" || response.status === 401) {
      clearAuthCookie(nextResponse, secureCookie);
    }

    return nextResponse;
  } catch (error) {
    const errorResponse = jsonError("Could not connect to backend API.", 502);

    if (pathName === "auth/logout") {
      clearAuthCookie(errorResponse, secureCookie);
    }

    return errorResponse;
  }
}

export async function GET(request, context) {
  return proxyBackendRequest(request, context);
}

export async function POST(request, context) {
  return proxyBackendRequest(request, context);
}

export async function PUT(request, context) {
  return proxyBackendRequest(request, context);
}

export async function PATCH(request, context) {
  return proxyBackendRequest(request, context);
}

export async function DELETE(request, context) {
  return proxyBackendRequest(request, context);
}
