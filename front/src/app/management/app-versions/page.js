import AppVersionsClient from "./AppVersionsClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إصدارات تطبيق المتدرب | TechnoGYM",
};

async function loadAppVersions(token) {
  try {
    return await requestBackend("app-versions", {
      token,
      params: { per_page: "all" },
    });
  } catch {
    return null;
  }
}

export default async function AppVersionsPage() {
  const { token } = await verifyPageAccess("/management/app-versions");
  const versions = await loadAppVersions(token);

  return <AppVersionsClient initialVersions={versions} />;
}
