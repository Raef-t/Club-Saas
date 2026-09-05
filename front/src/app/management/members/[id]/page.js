import { notFound } from "next/navigation";
import MemberProfileClient from "./MemberProfileClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";
import { getBranchesArray } from "@/lib/utils";
import { getMemberProfileRecord } from "../memberProfileUtils";

export const metadata = {
  title: "الملف الشامل للاعب | TechnoGYM",
};

export default async function MemberProfilePage({ params }) {
  const { id } = await params;
  if (!/^\d+$/.test(id)) notFound();

  const { token } = await verifyPageAccess("/management/members");
  let memberResponse;

  try {
    memberResponse = await requestBackend(`members/${id}`, { token });
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

  return (
    <MemberProfileClient
      memberId={id}
      initialMember={member}
      initialBranches={getBranchesArray(branchesResponse)}
    />
  );
}
