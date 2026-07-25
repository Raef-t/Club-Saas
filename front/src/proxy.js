import { NextResponse } from "next/server";
import { AUTH_SESSION_COOKIE } from "@/lib/authSession";

export function proxy(request) {
  const sessionToken = request.cookies.get(AUTH_SESSION_COOKIE)?.value;

  if (!sessionToken) {
    const loginUrl = new URL("/login", request.url);
    loginUrl.searchParams.set(
      "next",
      `${request.nextUrl.pathname}${request.nextUrl.search}`,
    );

    const response = NextResponse.redirect(loginUrl);
    response.headers.set("Cache-Control", "no-store, max-age=0");
    return response;
  }

  const response = NextResponse.next();
  response.headers.set("Cache-Control", "private, no-store, max-age=0");
  return response;
}

export const config = {
  matcher: [
    "/management/:path*",
    "/accounting/:path*",
    "/reports/:path*",
  ],
};
