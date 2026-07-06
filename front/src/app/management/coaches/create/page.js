import { Suspense } from "react";
import CoachesCreateClient from "./CoachesCreateClient";

export default function CreateCoachPage() {
  return (
    <Suspense fallback={null}>
      <CoachesCreateClient />
    </Suspense>
  );
}
