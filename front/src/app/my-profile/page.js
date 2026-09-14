import { notFound, redirect } from "next/navigation";
import PlayerProfileClient from "./PlayerProfileClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend, safeRequestBackend } from "@/lib/server/backend";
import { getBranchesArray } from "@/lib/utils";
import { getMemberProfileRecord } from "@/app/management/members/memberProfileUtils";

export const metadata = {
  title: "الملف الشخصي للاعب | TechnoGYM",
  description: "عرض بطاقة العضوية، باركود الدخول، تفاصيل الاشتراكات وسجل الحضور.",
};

export default async function MyProfilePage() {
  const { token, user } = await verifySession();
  const memberId = user?.member_id || user?.member?.id;

  if (!memberId) {
    // If the user has no member ID linked to their account, redirect to dashboard
    redirect("/");
  }

  let memberResponse;
  try {
    memberResponse = await requestBackend(`members/${memberId}`, { token });
  } catch (error) {
    if (error?.status === 404) notFound();
    throw error;
  }

  const branchesResponse = await safeRequestBackend(
    "branches",
    { token, params: { per_page: "all" } },
    [],
  );

  const member = getMemberProfileRecord(memberResponse);
  if (!member?.id) notFound();

  const allBranches = getBranchesArray(branchesResponse);
  const playerBranchId = member.branch_id || user?.branch_id;
  const initialBranches = playerBranchId
    ? allBranches.filter((b) => String(b.id) === String(playerBranchId))
    : allBranches;

  return (
    <PlayerProfileClient
      memberId={memberId}
      initialMember={member}
      initialBranches={initialBranches}
      currentUser={user}
    />
  );
}
