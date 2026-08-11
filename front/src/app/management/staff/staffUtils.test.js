import { describe, expect, it } from "vitest";
import {
  buildStaffQueryParams,
  createStaffInitialValues,
  getStaffCollection,
  isManagerStaffRole,
} from "./staffUtils";

describe("staff utilities", () => {
  it("extracts staff from the paginated API response", () => {
    const rows = [{ id: 1 }, { id: 2 }];
    expect(getStaffCollection({ data: { data: rows } })).toEqual(rows);
  });

  it("builds the documented staff filters with work_status", () => {
    expect(
      buildStaffQueryParams({
        branchId: "5",
        role: "receptionist",
        gender: "female",
        workStatus: "on_leave",
      }),
    ).toEqual({
      branch_id: 5,
      role: "receptionist",
      gender: "female",
      work_status: "on_leave",
    });
  });

  it("identifies every manager role", () => {
    expect(isManagerStaffRole("admin")).toBe(true);
    expect(isManagerStaffRole("management_admin")).toBe(true);
    expect(isManagerStaffRole("manager")).toBe(true);
    expect(isManagerStaffRole("receptionist")).toBe(false);
  });

  it("maps staff names, branches, and shift identifiers into edit values", () => {
    const branches = [{ id: 5, name: "تكنو جيم بنات" }];
    const values = createStaffInitialValues({
      branches,
      staff: {
        role: "admin",
        employment_type: "fixed_salary",
        base_salary: "4500.00",
        is_active: true,
        work_status: "suspended",
        start_date: "2026-02-16",
        branch_name: ["تكنو جيم بنات"],
        shifts: [{ branch_shift_id: 12, branch_shift: { id: 12, branch_id: 5 } }],
        person: {
          full_name: "يمان عبدالله",
          country_code: "+963",
          phone_number: "981642443",
          address: "الرياض",
        },
      },
    });

    expect(values).toMatchObject({
      first_name: "يمان",
      last_name: "عبدالله",
      branch_ids: [5],
      shifts: [12],
      base_salary: "4500",
      work_status: "suspended",
    });
  });
});
