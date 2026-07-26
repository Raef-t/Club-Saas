export const AUTH_SESSION_COOKIE = "techno_gym_session";

const REMEMBERED_SESSION_MAX_AGE = 60 * 60 * 24 * 7;

export function getAuthCookieOptions(remember = false, secure = false) {
  return {
    httpOnly: true,
    secure,
    sameSite: "lax",
    path: "/",
    ...(remember ? { maxAge: REMEMBERED_SESSION_MAX_AGE } : {}),
  };
}
