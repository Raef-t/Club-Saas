import { Suspense } from "react";
import CoachesClient from "@/app/management/coaches/CoachesClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إدارة المدربين | TechnoGYM",
};

export default async function CoachesPage() {
  const { token } = await verifySession();
  const [coaches, branches, activities] = await Promise.all([
    requestBackend("coaches", { token }),
    requestBackend("branches", { token }),
    requestBackend("activities", { token }),
  ]);

  return (
    <Suspense fallback={null}>
      <CoachesClient initialData={{ coaches, branches, activities }} />
    </Suspense>
  );
}
