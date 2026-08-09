import { notFound } from "next/navigation";
import MemberProfileClient from "./MemberProfileClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";
import { getBranchesArray } from "@/lib/utils";
import { getMemberProfileRecord } from "../memberProfileUtils";

export const metadata = {
  title: "الملف الشامل للاعب | TechnoGYM",
};

export default async function MemberProfilePage({ params }) {
  const { id } = await params;
  if (!/^\d+$/.test(id)) notFound();

  const { token } = await verifySession();
  let memberResponse;
  let branchesResponse;

  try {
    [memberResponse, branchesResponse] = await Promise.all([
      requestBackend(`members/${id}`, { token }),
      requestBackend("branches", { token }),
    ]);
  } catch (error) {
    if (error?.status === 404) notFound();
    throw error;
  }

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
