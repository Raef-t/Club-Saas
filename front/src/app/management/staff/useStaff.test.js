import { describe, expect, it } from "vitest";
import { createStaffFormData } from "./useStaff";

describe("staff form payload", () => {
  it("sends attendance times and does not assign coach-style shifts", () => {
    const body = createStaffFormData({
      first_name: "دعاء",
      last_name: "صباغ",
      phone_number: "999999999",
      country_code: "+963",
      role: "receptionist",
      employment_type: "fixed_salary",
      base_salary: 1000,
      work_status: "suspended",
      start_date: "2026-08-29",
      start_time: "08:30",
      end_time: "16:45",
      address: "",
      reason: "",
      branch_ids: [2],
      shifts: [99],
    });

    expect(body.get("start_time")).toBe("08:30");
    expect(body.get("end_time")).toBe("16:45");
    expect(body.get("work_status")).toBe("suspended");
    expect(body.has("shifts[]")).toBe(false);
  });
});
