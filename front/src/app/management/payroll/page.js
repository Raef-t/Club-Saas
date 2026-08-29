import PayrollClient from "./PayrollClient";

export const metadata = {
  title: "الرواتب | TechnoGYM",
  description: "توليد ومراجعة وتثبيت رواتب موظفي النادي.",
};

export default function PayrollPage() {
  return <PayrollClient />;
}
