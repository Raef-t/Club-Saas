"use client";

import { useEffect } from "react";
import { usePathname, useRouter } from "next/navigation";
import AccessDenied from "@/components/auth/AccessDenied";
import { usePermissions } from "@/lib/PermissionContext";

export default function RouteAccessBoundary({ children }) {
  const pathname = usePathname() || "";
  const router = useRouter();
  const { canAccess, firstAccessiblePath } = usePermissions();
  const isAllowed = canAccess(pathname);

  useEffect(() => {
    if (!isAllowed) {
      router.replace("/forbidden");
    }
  }, [isAllowed, router]);

  if (!isAllowed) {
    return <AccessDenied backHref={firstAccessiblePath} />;
  }

  return children;
}
