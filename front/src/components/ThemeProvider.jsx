"use client";

import { ThemeProvider as NextThemesProvider } from "next-themes";

export function ThemeProvider({ children, ...props }) {
  const scriptProps = {
    ...props.scriptProps,
    type: typeof window === "undefined" ? "text/javascript" : "text/plain",
  };

  return (
    <NextThemesProvider {...props} scriptProps={scriptProps}>
      {children}
    </NextThemesProvider>
  );
}
