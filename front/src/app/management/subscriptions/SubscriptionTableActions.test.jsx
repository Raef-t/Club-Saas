import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import SubscriptionTableActions from "./SubscriptionTableActions";

describe("subscription table actions", () => {
  afterEach(() => cleanup());

  it("keeps all available actions inside the three-dot menu", () => {
    const onView = vi.fn();
    const onDelete = vi.fn();
    const onRenew = vi.fn();

    render(
      <SubscriptionTableActions
        subscription={{ id: 17, status: "finished" }}
        canView
        canUpdate
        canDelete
        canRenew
        isBusy={false}
        onView={onView}
        onDelete={onDelete}
        onRenew={onRenew}
      />,
    );

    expect(screen.queryByText("عرض التفاصيل")).not.toBeInTheDocument();
    fireEvent.click(screen.getByRole("button", { name: "إجراءات الاشتراك" }));

    expect(screen.getByRole("menu", { name: "إجراءات الاشتراك" })).toBeInTheDocument();
    expect(screen.getByRole("menuitem", { name: "تعديل الاشتراك" })).toHaveAttribute(
      "href",
      "/management/subscriptions/create?mode=edit&id=17",
    );
    expect(screen.getByRole("menuitem", { name: "تجديد الاشتراك" })).toBeInTheDocument();
    expect(screen.getByRole("menuitem", { name: "حذف الاشتراك" })).toBeInTheDocument();

    fireEvent.click(screen.getByRole("menuitem", { name: "عرض التفاصيل" }));
    expect(onView).toHaveBeenCalledWith({ id: 17, status: "finished" });
    expect(screen.queryByRole("menu")).not.toBeInTheDocument();
  });

  it("does not offer renewal for an unfinished subscription", () => {
    render(
      <SubscriptionTableActions
        subscription={{ id: 18, status: "active" }}
        canView
        canUpdate={false}
        canDelete={false}
        canRenew
        isBusy={false}
        onView={vi.fn()}
        onDelete={vi.fn()}
        onRenew={vi.fn()}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: "إجراءات الاشتراك" }));
    expect(screen.queryByRole("menuitem", { name: "تجديد الاشتراك" })).not.toBeInTheDocument();
  });
});
