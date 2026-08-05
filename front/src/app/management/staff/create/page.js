import { Suspense } from "react";
import StaffCreateClient from "./StaffCreateClient";

export default function CreateStaffPage() {
  return (
    <Suspense fallback={null}>
      <StaffCreateClient />
    </Suspense>
  );
}
