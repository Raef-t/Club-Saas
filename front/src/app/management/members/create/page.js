import { Suspense } from "react";
import MembersCreateClient from "./MembersCreateClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إضافة عضو | TechnoGYM",
};

export default async function CreateMemberPage() {
  const { token } = await verifySession();
  const [members, plans, activities, coaches] = await Promise.all([
    requestBackend("members", { token }),
    requestBackend("subscription-plans", { token }),
    requestBackend("activities", { token }),
    requestBackend("coaches", { token }),
  ]);

  return (
    <Suspense fallback={null}>
      <MembersCreateClient
        initialSubscriptionData={{
          members,
          plans,
          activities,
          coaches,
        }}
      />
    </Suspense>
  );
}
