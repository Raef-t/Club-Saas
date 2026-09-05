import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import AccountSetupForm from "./AccountSetupForm";

const replace = vi.fn();
const refresh = vi.fn();

vi.mock("next/navigation", () => ({
  useRouter: () => ({ replace, refresh }),
}));

vi.mock("@/components/common/BrandLogo", () => ({
  default: () => <div data-testid="brand-logo" />,
}));

describe("AccountSetupForm", () => {
  afterEach(() => {
    cleanup();
    vi.restoreAllMocks();
    replace.mockReset();
    refresh.mockReset();
  });

  it("sends the new unified change-password payload", async () => {
    const fetchMock = vi
      .spyOn(globalThis, "fetch")
      .mockResolvedValue(Response.json({ status: "success" }));

    render(<AccountSetupForm userId={15} displayName="أحمد محمد" systemUsername="tec-ply-75054" />);

    fireEvent.change(screen.getByLabelText(/اسم المستخدم الجديد/), {
      target: { value: "ahmed_player99" },
    });
    fireEvent.change(screen.getByLabelText(/^كلمة المرور الجديدة/), {
      target: { value: "12345678" },
    });
    fireEvent.change(screen.getByLabelText(/^تأكيد كلمة المرور الجديدة/), {
      target: { value: "12345678" },
    });
    fireEvent.click(screen.getByRole("button", { name: "حفظ ومتابعة" }));

    await waitFor(() => expect(fetchMock).toHaveBeenCalledOnce());

    expect(fetchMock).toHaveBeenCalledWith(
      "/api/backend/auth/change-password",
      expect.objectContaining({
        method: "POST",
        body: JSON.stringify({
          user_id: 15,
          new_password: "12345678",
          new_password_confirmation: "12345678",
          custom_username: "ahmed_player99",
        }),
      }),
    );
    expect(replace).toHaveBeenCalledWith("/");
  });

  it("submits values without client-side validation and displays backend errors", async () => {
    const fetchMock = vi.spyOn(globalThis, "fetch").mockResolvedValue(
      Response.json(
        {
          message: "البيانات المدخلة غير صالحة.",
          data: {
            is_available: false,
            suggestions: ["تجربة_1_1", "تجربة_1_2"],
          },
          errors: {
            custom_username: ["اسم المستخدم غير صالح."],
            new_password: ["كلمة المرور قصيرة."],
          },
        },
        { status: 422 },
      ),
    );

    render(<AccountSetupForm userId={15} displayName="أحمد محمد" systemUsername="tec-ply-75054" />);

    fireEvent.change(screen.getByLabelText(/اسم المستخدم الجديد/), {
      target: { value: "أ" },
    });
    fireEvent.change(screen.getByLabelText(/^كلمة المرور الجديدة/), {
      target: { value: "1" },
    });
    fireEvent.change(screen.getByLabelText(/^تأكيد كلمة المرور الجديدة/), {
      target: { value: "2" },
    });
    fireEvent.click(screen.getByRole("button", { name: "حفظ ومتابعة" }));

    await waitFor(() => expect(fetchMock).toHaveBeenCalledOnce());

    expect(fetchMock).toHaveBeenCalledWith(
      "/api/backend/auth/change-password",
      expect.objectContaining({
        body: JSON.stringify({
          user_id: 15,
          new_password: "1",
          new_password_confirmation: "2",
          custom_username: "أ",
        }),
      }),
    );
    expect(await screen.findByText("اسم المستخدم غير صالح.")).toBeInTheDocument();
    expect(screen.getByText("كلمة المرور قصيرة.")).toBeInTheDocument();
    expect(screen.getByRole("alert")).toHaveTextContent("البيانات المدخلة غير صالحة.");

    fireEvent.click(screen.getByRole("button", { name: "تجربة_1_1" }));

    expect(screen.getByLabelText(/اسم المستخدم الجديد/)).toHaveValue("تجربة_1_1");
    expect(screen.queryByLabelText("اقتراحات أسماء المستخدم")).not.toBeInTheDocument();
    expect(screen.queryByRole("alert")).not.toBeInTheDocument();
  });
});
