import { cleanup, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import ProfileIdentityCard from "./ProfileIdentityCard";

vi.mock("qrcode", () => ({
  default: {
    toDataURL: vi.fn().mockResolvedValue("data:image/png;base64,cXItY29kZQ=="),
  },
}));

describe("ProfileIdentityCard", () => {
  afterEach(() => cleanup());

  it("shows the name, username, status, and generated QR image", async () => {
    render(
      <ProfileIdentityCard
        name="دانية مولوي"
        username="tec-ply-96201"
        qrCode="QR-FHL2LVXB4"
        status={{ label: "نشط", className: "text-app-green" }}
      />,
    );

    expect(screen.getByText("دانية مولوي")).toBeInTheDocument();
    expect(screen.getByText("tec-ply-96201")).toBeInTheDocument();
    expect(screen.getByText("نشط")).toBeInTheDocument();
    await waitFor(() => {
      expect(screen.getByRole("img", { name: /رمز QR الخاص بـ دانية مولوي/ })).toBeInTheDocument();
    });
  });

  it("shows an explicit fallback when the record has no QR code", () => {
    render(<ProfileIdentityCard name="موظف" username="tec-staff-100" status={{ label: "نشط" }} />);

    expect(screen.getByText("QR غير متوفر")).toBeInTheDocument();
  });
});
