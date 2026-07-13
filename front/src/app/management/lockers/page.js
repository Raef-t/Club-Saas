import LockersClient from "./LockersClient";

export const metadata = {
  title: "الخزائن | نظام إدارة النادي",
  description: "إدارة الخزائن المتاحة وتتبع حالتها.",
};

export default function LockersPage() {
  return <LockersClient />;
}
