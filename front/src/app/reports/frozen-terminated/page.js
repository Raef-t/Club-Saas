import FrozenTerminatedReportClient from "./FrozenTerminatedReportClient";
import { verifyPageAccess } from "@/lib/server/auth";

export const metadata = {
  title: "تقرير الاشتراكات المجمدة والملغاة | TechnoGYM",
};

export default async function FrozenTerminatedReportPage() {
  await verifyPageAccess("/reports/frozen-terminated");

  return <FrozenTerminatedReportClient />;
}
