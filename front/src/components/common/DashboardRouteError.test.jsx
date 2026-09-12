import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import DashboardRouteError from "./DashboardRouteError";

describe("DashboardRouteError", () => {
  beforeEach(() => {
    vi.spyOn(console, "error").mockImplementation(() => {});
  });

  afterEach(() => {
    cleanup();
    vi.restoreAllMocks();
  });

  it("shows a backend availability message for gateway failures", () => {
    render(
      <DashboardRouteError
        error={new Error("Backend request failed with status 504.")}
        unstable_retry={() => {}}
      />,
    );

    expect(screen.getByText("الخادم غير متاح مؤقتاً")).toBeInTheDocument();
  });

  it("uses the Next.js 16 retry callback", () => {
    const retry = vi.fn();
    render(<DashboardRouteError error={new Error("Failed")} unstable_retry={retry} />);

    fireEvent.click(screen.getByRole("button", { name: "إعادة المحاولة" }));

    expect(retry).toHaveBeenCalledOnce();
  });
});
