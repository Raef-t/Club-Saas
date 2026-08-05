import LockersEditClient from "./LockersEditClient";
import { notFound } from "next/navigation";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";
import { getLockerRecord } from "../../lockerUtils";

export const metadata = {
  title: "تعديل خزانة | نظام إدارة النادي",
};

export default async function EditLockerPage({ params }) {
  const { id } = await params;
  if (!/^\d+$/.test(id)) notFound();

  const { token } = await verifySession();
  let locker;
  let members;
  let staff;

  try {
    [locker, members, staff] = await Promise.all([
      requestBackend(`lockers/${id}`, { token }),
      requestBackend("members", { token }),
      requestBackend("staff", { token }),
    ]);
  } catch (error) {
    if (error?.status === 404) notFound();
    throw error;
  }

  const lockerRecord = getLockerRecord(locker);
  if (!lockerRecord?.id) notFound();

  return (
    <LockersEditClient
      lockerId={id}
      initialLocker={lockerRecord}
      initialMembers={members}
      initialStaff={staff}
    />
  );
}
