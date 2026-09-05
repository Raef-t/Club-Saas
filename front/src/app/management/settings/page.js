import SettingsClient from "./SettingsClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend, safeRequestBackend } from "@/lib/server/backend";
import { getBranchesArray } from "@/lib/utils";

export const metadata = {
  title: "الإعدادات | TechnoGYM",
  description: "إدارة إعدادات الفروع والورديات والإجازات ومظهر النظام.",
};

/**
 * Loads the first branch settings on the server before rendering the workspace.
 */
export default async function SettingsPage() {
  const { token } = await verifyPageAccess("/management/settings");
  const branches = await safeRequestBackend(
    "branches",
    {
      token,
      params: { per_page: "all" },
    },
    [],
  );
  const selectedBranchId = getBranchesArray(branches)[0]?.id;

  const [settings, shifts, holidays] = selectedBranchId
    ? await Promise.all([
        safeRequestBackend(`branches/${selectedBranchId}/settings`, { token }, null),
        safeRequestBackend(`branches/${selectedBranchId}/shifts`, { token }, []),
        safeRequestBackend(`branches/${selectedBranchId}/holidays`, { token }, []),
      ])
    : [null, [], []];

  return (
    <SettingsClient
      initialData={{
        branches,
        selectedBranchId,
        settings,
        shifts,
        holidays,
      }}
    />
  );
}
