import { Suspense } from "react";
import UsersClient from "./UsersClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "حسابات المستخدمين | TechnoGYM",
};

export default async function UsersPage() {
  const { token } = await verifySession();
  const users = await requestBackend("users", { token });

  return (
    <Suspense fallback={null}>
      <UsersClient initialUsers={users} />
    </Suspense>
  );
}
