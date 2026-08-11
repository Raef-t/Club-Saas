"use client";

import { useEffect } from "react";

export function GlobalScripts() {
  useEffect(() => {
    const handleWheel = () => {
      if (document.activeElement && document.activeElement.type === "number") {
        document.activeElement.blur();
      }
    };

    // Attach to window so it fires whenever the mouse wheel is used
    window.addEventListener("wheel", handleWheel, { passive: true });

    return () => {
      window.removeEventListener("wheel", handleWheel);
    };
  }, []);

  return null;
}
//test
//test
//test
//test
//test
