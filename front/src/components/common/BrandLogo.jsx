"use client";

import { useState } from "react";
import Image from "next/image";
import { DEFAULT_BRAND_LOGO_URL } from "@/lib/clubBranding";
import { useOptionalManagementBranch } from "@/lib/ManagementBranchContext";

/**
 * Renders the product logo inside a consistently sized responsive frame.
 */
export default function BrandLogo({ src, className = "", imageClassName = "", preload = false }) {
  const branchContext = useOptionalManagementBranch();
  const requestedSrc = src || branchContext?.brandLogoUrl || DEFAULT_BRAND_LOGO_URL;
  const [failedSrc, setFailedSrc] = useState("");
  const resolvedSrc = failedSrc === requestedSrc ? DEFAULT_BRAND_LOGO_URL : requestedSrc;

  return (
    <div className={`grid place-items-center overflow-hidden rounded-2xl ${className}`}>
      <Image
        src={resolvedSrc}
        alt="شعار TechnoGYM"
        width={159}
        height={59}
        className={`h-full w-full object-contain ${imageClassName}`}
        preload={preload}
        unoptimized
        onError={() => setFailedSrc(requestedSrc)}
      />
    </div>
  );
}
