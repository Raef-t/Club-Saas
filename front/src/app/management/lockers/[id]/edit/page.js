import LockersEditClient from "./LockersEditClient";
import { notFound } from "next/navigation";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend, safeRequestBackend } from "@/lib/server/backend";
import { getLockerRecord } from "../../lockerUtils";

export const metadata = {
  title: "تعديل خزانة | نظام إدارة النادي",
};

export default async function EditLockerPage({ params }) {
  const { id } = await params;
  if (!/^\d+$/.test(id)) notFound();

  const { token } = await verifyPageAccess("/management/lockers");
  let locker;

  try {
    locker = await requestBackend(`lockers/${id}`, { token });
  } catch (error) {
    if (error?.status === 404) notFound();
    throw error;
  }

  const [members, coaches, staff] = await Promise.all([
    safeRequestBackend("members", { token, params: { per_page: "all" } }, []),
    safeRequestBackend("coaches", { token, params: { per_page: "all" } }, []),
    safeRequestBackend("staff", { token, params: { per_page: "all" } }, []),
  ]);

  const lockerRecord = getLockerRecord(locker);
  if (!lockerRecord?.id) notFound();

  return (
    <LockersEditClient
      lockerId={id}
      initialLocker={lockerRecord}
      initialMembers={members}
      initialCoaches={coaches}
      initialStaff={staff}
    />
  );
}
