import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
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
  beforeEach(() => {
    vi.stubEnv("NEXT_PUBLIC_PASSWORD_HASH_SECRET_KEY", "oid900=rjfreipwhefdk");
  });

  afterEach(() => {
    cleanup();
    vi.restoreAllMocks();
    replace.mockReset();
    refresh.mockReset();
    vi.unstubAllEnvs();
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
          new_password: "119f6226667c1bc87396838134392ef4f4d38e68f1719aed7b2dff13be62d5ed",
          new_password_confirmation:
            "119f6226667c1bc87396838134392ef4f4d38e68f1719aed7b2dff13be62d5ed",
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
          new_password: "14d13db470da41ed4b8de24e3f04e097efe6d8f739201f343ffcb7662cf50eae",
          new_password_confirmation:
            "250df892138a3001f40399167d2ab8827c48562aa7d420e4c5bf514e3e026b9d",
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
