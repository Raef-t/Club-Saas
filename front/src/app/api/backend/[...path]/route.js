export const dynamic = "force-dynamic";

const API_BASE_URL =
  process.env.API_BASE_URL || "http://issgroup-001-site1.anytempurl.com";

const METHODS_WITH_BODY = new Set(["POST", "PUT", "PATCH", "DELETE"]);

async function proxyBackendRequest(request, context) {
  const { path = [] } = await context.params;
  const pathName = path.join("/");
  const token =
    request.headers.get("authorization")?.replace(/^Bearer\s+/i, "") ||
    process.env.API_TOKEN;
  const requiresAuth = pathName !== "auth/login";

  if (requiresAuth && !token) {
    return Response.json(
      {
        status: "error",
        message: "API token is not configured.",
      },
      { status: 500 },
    );
  }

  if (!path.length) {
    return Response.json(
      {
        status: "error",
        message: "API path is required.",
      },
      { status: 400 },
    );
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
    const body = await response.text();

    return new Response(body, {
      status: response.status,
      headers: {
        "Content-Type": responseContentType,
      },
    });
  } catch (error) {
    return Response.json(
      {
        status: "error",
        message: "Could not connect to backend API.",
      },
      { status: 502 },
    );
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
