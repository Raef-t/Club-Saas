import { Suspense } from "react";
import ActivitiesCreateClient from "./ActivitiesCreateClient";

export default function CreateActivityPage() {
  return (
    <Suspense fallback={null}>
      <ActivitiesCreateClient />
    </Suspense>
  );
}
