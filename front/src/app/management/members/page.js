import { Suspense } from "react";
import MembersClient from "@/app/management/members/MembersClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend, safeRequestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إدارة الأعضاء واللاعبين | TechnoGYM",
};

export default async function MembersPage() {
  const { token } = await verifyPageAccess("/management/members");
  const [members, branches, plans] = await Promise.all([
    requestBackend("members", { token }),
    safeRequestBackend("branches", { token, params: { per_page: "all" } }, []),
    safeRequestBackend("subscription-plans", { token, params: { per_page: "all" } }, []),
  ]);

  return (
    <Suspense fallback={null}>
      <MembersClient initialData={{ members, branches, plans }} />
    </Suspense>
  );
}
