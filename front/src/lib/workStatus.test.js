import { describe, expect, it } from "vitest";
import { getWorkStatusMeta, resolveWorkStatus, WORK_STATUS_OPTIONS } from "./workStatus";

describe("work status helpers", () => {
  it("exposes the documented work statuses", () => {
    expect(WORK_STATUS_OPTIONS).toEqual([
      { value: "active", label: "نشط" },
      { value: "suspended", label: "موقوف" },
      { value: "on_leave", label: "إجازة" },
    ]);
  });

  it("prefers work_status over the legacy is_active field", () => {
    expect(resolveWorkStatus({ work_status: "on_leave", is_active: true })).toBe("on_leave");
  });

  it("maps inactive legacy records to suspended", () => {
    expect(getWorkStatusMeta({ is_active: false })).toMatchObject({
      value: "suspended",
      label: "موقوف",
    });
  });
});
