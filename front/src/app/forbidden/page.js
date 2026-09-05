import AccessDenied from "@/components/auth/AccessDenied";
import { getFirstAccessiblePath } from "@/lib/permissions";
import { verifySession } from "@/lib/server/auth";

export const metadata = {
  title: "غير مصرح | TechnoGYM",
};

export default async function ForbiddenPage() {
  const { user } = await verifySession();

  return (
    <main className="dashboard-bg grid min-h-screen place-items-center p-4">
      <div className="w-full max-w-2xl">
        <AccessDenied backHref={getFirstAccessiblePath(user)} />
      </div>
    </main>
  );
}
