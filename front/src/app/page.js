import { redirect } from "next/navigation";
import { verifySession } from "@/lib/server/auth";
import { getFirstAccessiblePath } from "@/lib/permissions";

/**
 * Renders the public home route, redirecting to the dashboard if authenticated
 * or login if not authenticated.
 */
export default async function HomePage() {
  const { user } = await verifySession();
  const isPlayer =
    user?.roles?.some((r) => (typeof r === "string" ? r : r?.name) === "player") ||
    user?.role === "player";

  if (isPlayer) {
    redirect("/my-profile");
  }

  redirect(getFirstAccessiblePath(user) || "/forbidden");
}
