import { redirect } from "next/navigation";
import { verifySession } from "@/lib/server/auth";

/**
 * Renders the public home route, redirecting to the dashboard if authenticated
 * or login if not authenticated.
 */
export default async function HomePage() {
  await verifySession();
  redirect("/management/");
}
