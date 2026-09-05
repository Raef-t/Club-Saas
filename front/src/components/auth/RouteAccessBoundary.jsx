"use client";

import { usePathname } from "next/navigation";
import AccessDenied from "@/components/auth/AccessDenied";
import { usePermissions } from "@/lib/PermissionContext";

export default function RouteAccessBoundary({ children }) {
  const pathname = usePathname() || "";
  const { canAccess, firstAccessiblePath } = usePermissions();

  if (!canAccess(pathname)) {
    return <AccessDenied backHref={firstAccessiblePath} />;
  }

  return children;
}
