import SettingsClient from "./SettingsClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";
import { getBranchesArray } from "@/lib/utils";

export const metadata = {
  title: "الإعدادات | TechnoGYM",
  description: "إدارة إعدادات الفروع والورديات والإجازات ومظهر النظام.",
};

/**
 * Loads the first branch settings on the server before rendering the workspace.
 */
export default async function SettingsPage() {
  const { token } = await verifySession();
  const branches = await requestBackend("branches", { token });
  const selectedBranchId = getBranchesArray(branches)[0]?.id;

  const [settings, shifts, holidays] = selectedBranchId
    ? await Promise.all([
        requestBackend(`branches/${selectedBranchId}/settings`, { token }),
        requestBackend(`branches/${selectedBranchId}/shifts`, { token }),
        requestBackend(`branches/${selectedBranchId}/holidays`, { token }),
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
