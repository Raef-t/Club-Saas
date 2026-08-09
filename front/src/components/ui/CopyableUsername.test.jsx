import { render, screen, fireEvent, waitFor, cleanup } from "@testing-library/react";
import { describe, expect, it, vi, beforeEach, afterEach } from "vitest";
import CopyableUsername from "./CopyableUsername";

describe("CopyableUsername", () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  afterEach(() => {
    cleanup();
  });

  it("renders the username cleanly with fallback for empty value", () => {
    const { rerender } = render(<CopyableUsername username="tec-admin-100" />);
    expect(screen.getByText("tec-admin-100")).toBeInTheDocument();

    rerender(<CopyableUsername username={null} />);
    expect(screen.getByText("غير محدد")).toBeInTheDocument();
  });

  it("copies username to clipboard and shows success indicator on click", async () => {
    const writeTextMock = vi.fn().mockResolvedValue(undefined);
    Object.assign(navigator, {
      clipboard: {
        writeText: writeTextMock,
      },
    });

    render(<CopyableUsername username="tec-coach-200" />);

    const button = screen.getByRole("button", { name: /tec-coach-200/i });
    expect(button).toBeInTheDocument();

    fireEvent.click(button);

    expect(writeTextMock).toHaveBeenCalledWith("tec-coach-200");
    await waitFor(() => {
      expect(screen.getByText("تم النسخ!")).toBeInTheDocument();
    });
  });

  it("includes hover zoom styling classes for magnification on hover", () => {
    render(<CopyableUsername username="tec-admin-100" />);
    const button = screen.getByRole("button", { name: /tec-admin-100/i });
    expect(button.className).toContain("hover:scale-125");
    expect(button.className).toContain("hover:z-50");
  });
});
