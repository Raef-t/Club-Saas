import { NextResponse } from "next/server";
import {
  AUTH_SESSION_COOKIE,
  getAuthCookieOptions,
} from "@/lib/authSession";

export const dynamic = "force-dynamic";

const API_BASE_URL =
  process.env.API_BASE_URL || "http://issgroup-001-site1.anytempurl.com";

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

function clearAuthCookie(response) {
  response.cookies.set(AUTH_SESSION_COOKIE, "", {
    ...getAuthCookieOptions(false),
    maxAge: 0,
  });

  return response;
}

async function proxyBackendRequest(request, context) {
  const { path = [] } = await context.params;
  const pathName = path.join("/");
  const token =
    request.headers.get("authorization")?.replace(/^Bearer\s+/i, "") ||
    request.cookies.get(AUTH_SESSION_COOKIE)?.value ||
    process.env.API_TOKEN;
  const requiresAuth = !PUBLIC_API_PATHS.has(pathName);

  if (requiresAuth && !token) {
    return jsonError("Authentication is required.", 401);
  }

  if (!path.length) {
    return jsonError("API path is required.", 400);
  }

  const upstreamPath = path.map((segment) => encodeURIComponent(segment)).join("/");
  const upstreamUrl = new URL(`/api/v1/${upstreamPath}`, API_BASE_URL);

  request.nextUrl.searchParams.forEach((value, key) => {
    upstreamUrl.searchParams.append(key, value);
  });

  const headers = new Headers();
  headers.set("Accept", "application/json");
  if (token) {
    headers.set("Authorization", `Bearer ${token}`);
  }

  const init = {
    method: request.method,
    headers,
    cache: "no-store",
  };

  if (METHODS_WITH_BODY.has(request.method)) {
    const contentType = request.headers.get("content-type");
    if (contentType) {
      headers.set("Content-Type", contentType);
    }

    init.body = await request.arrayBuffer();
  }

  try {
    const response = await fetch(upstreamUrl, init);
    const responseContentType =
      response.headers.get("content-type") || "application/json";
    let body = await response.text();
    let sessionToken = null;

    if (pathName === "auth/login" && response.ok) {
      try {
        const payload = JSON.parse(body);
        sessionToken = payload?.data?.access_token;

        if (!sessionToken) {
          return jsonError("Login response does not include an access token.", 502);
        }

        const clientPayload = {
          ...payload,
          data: {
            ...payload.data,
          },
        };

        delete clientPayload.data.access_token;
        delete clientPayload.data.token_type;
        body = JSON.stringify(clientPayload);
      } catch {
        return jsonError("Login response is not valid JSON.", 502);
      }
    }

    const nextResponse = new NextResponse(body, {
      status: response.status,
      headers: {
        "Content-Type": responseContentType,
        "Cache-Control": "no-store",
      },
    });

    if (sessionToken) {
      const remember =
        request.headers.get("x-remember-me")?.toLowerCase() === "true";

      nextResponse.cookies.set(
        AUTH_SESSION_COOKIE,
        sessionToken,
        getAuthCookieOptions(remember),
      );
    }

    if (pathName === "auth/logout" || response.status === 401) {
      clearAuthCookie(nextResponse);
    }

    return nextResponse;
  } catch (error) {
    const errorResponse = jsonError("Could not connect to backend API.", 502);

    if (pathName === "auth/logout") {
      clearAuthCookie(errorResponse);
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
