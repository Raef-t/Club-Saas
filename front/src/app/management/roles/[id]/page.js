import { notFound } from "next/navigation";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";
import RoleDetailsClient from "./RoleDetailsClient";

export const metadata = {
  title: "تفاصيل الدور والصلاحيات | TechnoGYM",
};

async function loadOptional(path, token) {
  try {
    return await requestBackend(path, { token });
  } catch {
    return null;
  }
}

export default async function RoleDetailsPage({ params }) {
  const { id } = await params;
  if (!/^\d+$/.test(id)) notFound();

  const { token } = await verifyPageAccess("/management/roles");
  const [role, roles, permissions] = await Promise.all([
    requestBackend(`roles/${id}`, { token }),
    loadOptional("roles", token),
    loadOptional("permissions", token),
  ]);

  return (
    <RoleDetailsClient
      roleId={id}
      initialRole={role}
      initialRoles={roles}
      initialPermissions={permissions}
    />
  );
}
