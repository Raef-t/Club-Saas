import { Suspense } from "react";
import MembersCreateClient from "./MembersCreateClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend, safeRequestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إضافة عضو | TechnoGYM",
};

export default async function CreateMemberPage() {
  const { token } = await verifyPageAccess("/management/members/create");
  const [members, plans, activityTypes, activities, coaches] = await Promise.all([
    requestBackend("members", { token, params: { per_page: "all" } }),
    safeRequestBackend(
      "subscription-plans",
      { token, params: { available: true, per_page: 15, page: 1 } },
      [],
    ),
    safeRequestBackend("activity-types", { token, params: { per_page: "all" } }, []),
    safeRequestBackend("activities", { token, params: { per_page: "all" } }, []),
    safeRequestBackend("coaches", { token, params: { per_page: "all" } }, []),
  ]);

  return (
    <Suspense fallback={null}>
      <MembersCreateClient
        initialSubscriptionData={{
          members,
          plans,
          activityTypes,
          activities,
          coaches,
        }}
      />
    </Suspense>
  );
}
