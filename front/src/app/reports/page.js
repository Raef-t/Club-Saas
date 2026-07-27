import ReportsDashboard from "./dashboard/ReportsDashboard";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "التقارير التشغيلية | TechnoGYM",
};

/**
 * Loads one report data source without blocking the remaining reports.
 */
async function loadReportResource(path, token) {
  try {
    return await requestBackend(path, { token });
  } catch {
    return null;
  }
}

/**
 * Loads current operational data and renders the reports route.
 */
export default async function ReportsPage() {
  const { token } = await verifySession();
  const [members, coaches, subscriptions, activities, attendances] = await Promise.all([
    loadReportResource("members", token),
    loadReportResource("coaches", token),
    loadReportResource("player-subscriptions", token),
    loadReportResource("activities", token),
    loadReportResource("attendances/history", token),
  ]);

  return (
    <ReportsDashboard
      initialData={{
        members,
        coaches,
        subscriptions,
        activities,
        attendances,
      }}
    />
  );
}
