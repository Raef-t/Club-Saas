import { NextResponse } from "next/server";
import { AUTH_SESSION_COOKIE, AUTH_SETUP_COOKIE } from "@/lib/authSession";

export function proxy(request) {
  const sessionToken = request.cookies.get(AUTH_SESSION_COOKIE)?.value;
  const requiresAccountSetup = request.cookies.get(AUTH_SETUP_COOKIE)?.value === "required";
  const isAccountSetupRoute = request.nextUrl.pathname === "/account-setup";

  if (!sessionToken) {
    const loginUrl = new URL("/login", request.url);
    loginUrl.searchParams.set("next", `${request.nextUrl.pathname}${request.nextUrl.search}`);

    const response = NextResponse.redirect(loginUrl);
    response.headers.set("Cache-Control", "no-store, max-age=0");
    return response;
  }

  if (requiresAccountSetup && !isAccountSetupRoute) {
    const setupUrl = new URL("/account-setup", request.url);
    const response = NextResponse.redirect(setupUrl);
    response.headers.set("Cache-Control", "private, no-store, max-age=0");
    return response;
  }

  if (!requiresAccountSetup && isAccountSetupRoute) {
    const response = NextResponse.redirect(new URL("/", request.url));
    response.headers.set("Cache-Control", "private, no-store, max-age=0");
    return response;
  }

  const response = NextResponse.next();
  response.headers.set("Cache-Control", "private, no-store, max-age=0");
  return response;
}

export const config = {
  matcher: ["/account-setup", "/management/:path*", "/accounting/:path*", "/reports/:path*"],
};
