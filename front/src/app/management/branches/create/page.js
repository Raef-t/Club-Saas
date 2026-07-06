import { Suspense } from "react";
import BranchesCreateClient from "./BranchesCreateClient";

export default function CreateBranchPage() {
  return (
    <Suspense fallback={null}>
      <BranchesCreateClient />
    </Suspense>
  );
}
