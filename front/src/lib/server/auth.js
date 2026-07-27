import "server-only";

import { cache } from "react";
import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { AUTH_SESSION_COOKIE } from "@/lib/authSession";
import { requestBackend } from "@/lib/server/backend";

/**
 * Verifies the current session with the backend and returns its safe profile.
 *
 * @returns {Promise<{token: string, user: object}>} Verified session data.
 */
export const verifySession = cache(async () => {
  const cookieStore = await cookies();
  const token = cookieStore.get(AUTH_SESSION_COOKIE)?.value;

  if (!token) {
    redirect("/login");
  }

  try {
    const profile = await requestBackend("auth/me", { token });

    return {
      token,
      user: profile?.data || profile,
    };
  } catch (error) {
    if (error?.status === 401 || error?.status === 403) {
      redirect("/login");
    }

    throw error;
  }
});
