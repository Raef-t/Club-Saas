import { redirect } from "next/navigation";
import { verifySession } from "@/lib/server/auth";
import { getFirstAccessiblePath } from "@/lib/permissions";

/**
 * Renders the public home route, redirecting to the dashboard if authenticated
 * or login if not authenticated.
 */
export default async function HomePage() {
  const { user } = await verifySession();
  redirect(getFirstAccessiblePath(user) || "/forbidden");
}
