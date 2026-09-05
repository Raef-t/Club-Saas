import { Suspense } from "react";
import CoachesClient from "@/app/management/coaches/CoachesClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend, safeRequestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إدارة المدربين | TechnoGYM",
};

export default async function CoachesPage() {
  const { token } = await verifyPageAccess("/management/coaches");
  const [coaches, branches, activities] = await Promise.all([
    requestBackend("coaches", { token }),
    safeRequestBackend("branches", { token, params: { per_page: "all" } }, []),
    safeRequestBackend("activities", { token, params: { per_page: "all" } }, []),
  ]);

  return (
    <Suspense fallback={null}>
      <CoachesClient initialData={{ coaches, branches, activities }} />
    </Suspense>
  );
}
