import { describe, expect, it } from "vitest";
import {
  buildStaffQueryParams,
  createStaffInitialValues,
  createStaffProfileUpdateBody,
  getStaffCollection,
  getStaffEditHref,
  getStaffWorkStatusMeta,
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

  it("routes coach edits to the coach form and other roles to the staff form", () => {
    expect(getStaffEditHref({ id: 41, role: "coach" })).toBe(
      "/management/coaches/create?mode=edit&id=41",
    );
    expect(getStaffEditHref({ id: 41, role: "coach", coach_id: 9 })).toBe(
      "/management/coaches/create?mode=edit&id=9",
    );
    expect(getStaffEditHref({ id: 17, role: "receptionist" })).toBe(
      "/management/staff/create?mode=edit&id=17",
    );
  });

  it("maps staff names, branches, and attendance times into edit values", () => {
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
        start_time: "08:30:00",
        end_time: "16:45:00",
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
      start_time: "08:30",
      end_time: "16:45",
      base_salary: "4500",
      work_status: "suspended",
    });
    expect(values).not.toHaveProperty("shifts");
  });

  it("labels a suspended staff member as inactive without changing the API value", () => {
    expect(getStaffWorkStatusMeta("suspended")).toMatchObject({
      value: "suspended",
      label: "غير نشط",
    });
    expect(getStaffWorkStatusMeta({ role: "coach", work_status: "suspended" }).label).toBe("موقوف");
  });

  it("keeps required employment fields in personal profile updates", () => {
    expect(
      createStaffProfileUpdateBody(
        {
          id: 74,
          role: "admin",
          employment_type: "fixed_salary",
          base_salary: "1000.00",
          work_status: "active",
          start_date: "2025-09-08",
          start_time: null,
          end_time: null,
          person: { address: "شارع النيل خلف جامع الرحمن" },
        },
        {
          first_name: "عبيدة",
          last_name: "أيوبي",
          country_code: "+963",
          phone_number: "999999999",
          gender: "male",
          reason: "تحديث رقم الهاتف",
        },
      ),
    ).toEqual({
      first_name: "عبيدة",
      last_name: "أيوبي",
      country_code: "+963",
      phone_number: "999999999",
      gender: "male",
      role: "admin",
      employment_type: "fixed_salary",
      base_salary: "1000",
      work_status: "active",
      reason: "تحديث رقم الهاتف",
      start_date: "2025-09-08",
      address: "شارع النيل خلف جامع الرحمن",
    });
  });
});
