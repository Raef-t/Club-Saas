import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import LoginForm from "./LoginForm";

const replace = vi.fn();
const refresh = vi.fn();

vi.mock("next/navigation", () => ({
  useRouter: () => ({ replace, refresh }),
}));

vi.mock("@/components/common/BrandLogo", () => ({
  default: () => <div data-testid="brand-logo" />,
}));

describe("LoginForm", () => {
  afterEach(() => {
    cleanup();
    vi.restoreAllMocks();
    replace.mockReset();
    refresh.mockReset();
  });

  it("submits credentials without client-side validation and displays backend errors", async () => {
    const fetchMock = vi.spyOn(globalThis, "fetch").mockResolvedValue(
      Response.json(
        {
          message: "بيانات الدخول غير صالحة.",
          errors: {
            username: ["اسم المستخدم يحتوي على أحرف غير مسموح بها من الباك إند."],
          },
        },
        { status: 422 },
      ),
    );

    render(<LoginForm />);

    fireEvent.change(screen.getByLabelText("اسم المستخدم"), {
      target: { value: "تجربة" },
    });
    fireEvent.change(screen.getByLabelText("كلمة المرور"), {
      target: { value: "1" },
    });
    fireEvent.click(screen.getByRole("button", { name: "دخول" }));

    await waitFor(() => expect(fetchMock).toHaveBeenCalledOnce());

    expect(fetchMock).toHaveBeenCalledWith(
      "/api/backend/auth/login",
      expect.objectContaining({
        method: "POST",
        body: JSON.stringify({
          username: "تجربة",
          password: "1",
          fcm_token: "fcm_token_string_here",
        }),
      }),
    );
    expect(
      await screen.findByText("اسم المستخدم يحتوي على أحرف غير مسموح بها من الباك إند."),
    ).toBeInTheDocument();
    expect(screen.getByRole("alert")).toHaveTextContent("بيانات الدخول غير صالحة.");
  });
});
