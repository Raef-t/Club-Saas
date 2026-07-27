import ClubsClient from "./ClubsClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إدارة النوادي | TechnoGYM",
  description: "إدارة النوادي المسجلة وشعاراتها وحالة تشغيلها.",
};

/**
 * Loads the club workspace data on the server.
 */
export default async function ClubsPage() {
  const { token } = await verifySession();
  const clubs = await requestBackend("clubs", { token });

  return <ClubsClient initialData={{ clubs }} />;
}
