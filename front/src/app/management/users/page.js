import { Suspense } from "react";
import UsersClient from "./UsersClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { safeRequestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "حسابات المستخدمين | TechnoGYM",
};

export default async function UsersPage() {
  const { token } = await verifyPageAccess("/management/users");
  const users = await safeRequestBackend("users", { token }, []);

  return (
    <Suspense fallback={null}>
      <UsersClient initialUsers={users} />
    </Suspense>
  );
}
