import RolesClient from "./RolesClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "الأدوار والصلاحيات | TechnoGYM",
};

async function loadPermissions(token) {
  try {
    return await requestBackend("permissions", { token });
  } catch {
    return null;
  }
}

export default async function RolesPage() {
  const { token } = await verifyPageAccess("/management/roles");
  const [roles, permissions] = await Promise.all([
    requestBackend("roles", { token }),
    loadPermissions(token),
  ]);

  return <RolesClient initialRoles={roles} initialPermissions={permissions} />;
}
