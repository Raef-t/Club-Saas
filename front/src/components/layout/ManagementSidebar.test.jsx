import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import ManagementSidebar from "./ManagementSidebar";
import { PermissionProvider } from "@/lib/PermissionContext";

vi.mock("next/navigation", () => ({
  usePathname: () => "/management",
}));

function renderSidebar(permissions) {
  return render(
    <PermissionProvider user={{ roles: ["staff"], permissions }}>
      <ManagementSidebar className="" />
    </PermissionProvider>,
  );
}

describe("ManagementSidebar quick actions", () => {
  it("hides the add-player action when the user can update but cannot create members", () => {
    renderSidebar(["member.view-any", "member.update"]);

    expect(screen.queryByRole("link", { name: "إضافة لاعب" })).not.toBeInTheDocument();
  });

  it("shows the add-player action when the user has member.create", () => {
    renderSidebar(["member.view-any", "member.create"]);

    expect(screen.getByRole("link", { name: "إضافة لاعب" })).toHaveAttribute(
      "href",
      "/management/members/create",
    );
  });
});

describe("ManagementSidebar navigation", () => {
  it("hides statistics when the user only has access to another management page", () => {
    renderSidebar(["session-template.schedule"]);

    expect(screen.queryByRole("link", { name: "الإحصائيات" })).not.toBeInTheDocument();
    expect(screen.getByRole("link", { name: "جدول الدوام" })).toBeInTheDocument();
  });

  it("shows statistics when the user has attendance.dashboard", () => {
    renderSidebar(["attendance.dashboard"]);

    expect(screen.getByRole("link", { name: "الإحصائيات" })).toHaveAttribute("href", "/management");
  });
});
