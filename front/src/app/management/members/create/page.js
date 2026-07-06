import { Suspense } from "react";
import MembersCreateClient from "./MembersCreateClient";

export default function CreateMemberPage() {
  return (
    <Suspense fallback={null}>
      <MembersCreateClient />
    </Suspense>
  );
}
