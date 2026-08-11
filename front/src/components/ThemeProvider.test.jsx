import { render } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { ThemeProvider } from "./ThemeProvider";

describe("ThemeProvider", () => {
  it("renders the theme bootstrap script as inert content on the client", () => {
    window.matchMedia = vi.fn().mockReturnValue({
      matches: false,
      addListener: vi.fn(),
      removeListener: vi.fn(),
    });

    const { container } = render(
      <ThemeProvider attribute="class" defaultTheme="dark" enableSystem={false}>
        <div>content</div>
      </ThemeProvider>,
    );

    expect(container.querySelector("script")).toHaveAttribute("type", "text/plain");
  });
});
