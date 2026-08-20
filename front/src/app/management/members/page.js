import { Suspense } from "react";
import MembersClient from "@/app/management/members/MembersClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إدارة الأعضاء واللاعبين | TechnoGYM",
};

export default async function MembersPage() {
  const { token } = await verifySession();
  const [members, branches, plans] = await Promise.all([
    requestBackend("members", { token }),
    requestBackend("branches", { token }),
    requestBackend("subscription-plans", { token }),
  ]);

  return (
    <Suspense fallback={null}>
      <MembersClient initialData={{ members, branches, plans }} />
    </Suspense>
  );
}
