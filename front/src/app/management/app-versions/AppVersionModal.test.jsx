import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import AppVersionModal from "./AppVersionModal";

afterEach(cleanup);

describe("AppVersionModal", () => {
  it("submits the selected platform, source and publishing settings", async () => {
    const onSubmit = vi.fn(async () => true);

    render(<AppVersionModal open onClose={vi.fn()} onSubmit={onSubmit} />);

    fireEvent.click(screen.getByRole("button", { name: /iOS/ }));
    fireEvent.change(screen.getByLabelText(/رقم الإصدار/), { target: { value: "2.4.0" } });
    fireEvent.change(screen.getByLabelText(/رقم البناء/), { target: { value: "42" } });
    fireEvent.click(screen.getByRole("button", { name: "رابط مباشر" }));
    fireEvent.change(screen.getByLabelText(/رابط التحميل المباشر/), {
      target: { value: "https://example.com/trainee-app.ipa" },
    });
    fireEvent.click(screen.getByRole("checkbox", { name: "تحديث إجباري" }));
    fireEvent.change(screen.getByLabelText(/ملاحظات الإصدار/), {
      target: { value: "تحسين تجربة تسجيل الدخول" },
    });
    fireEvent.click(screen.getByRole("button", { name: "حفظ ونشر الإصدار" }));

    await waitFor(() => expect(onSubmit).toHaveBeenCalledOnce());
    const payload = onSubmit.mock.calls[0][0];

    expect(payload).toBeInstanceOf(FormData);
    expect(payload.get("platform")).toBe("ios");
    expect(payload.get("version_number")).toBe("2.4.0");
    expect(payload.get("build_number")).toBe("42");
    expect(payload.get("download_url")).toBe("https://example.com/trainee-app.ipa");
    expect(payload.get("is_force_update")).toBe("1");
    expect(payload.get("is_active")).toBe("1");
    expect(payload.get("release_notes")).toBe("تحسين تجربة تسجيل الدخول");
  });

  it("requires an application file for a new uploaded release", () => {
    const onSubmit = vi.fn();

    render(<AppVersionModal open onClose={vi.fn()} onSubmit={onSubmit} />);

    fireEvent.change(screen.getByLabelText(/رقم الإصدار/), { target: { value: "1.0.0" } });
    fireEvent.click(screen.getByRole("button", { name: "حفظ ونشر الإصدار" }));

    expect(screen.getByRole("alert")).toHaveTextContent("يرجى اختيار ملف التطبيق للرفع");
    expect(onSubmit).not.toHaveBeenCalled();
  });
});
