import LoadingSpinner from "@/components/ui/LoadingSpinner";

/**
 * Renders a consistent loading state for protected dashboard routes.
 */
export default function DashboardRouteLoading() {
  return (
    <main className="dashboard-bg grid min-h-[60vh] place-items-center">
      <LoadingSpinner className="size-8" label="جاري تحميل الصفحة" />
    </main>
  );
}
