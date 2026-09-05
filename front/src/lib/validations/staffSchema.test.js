import { describe, expect, it } from "vitest";
import { staffFormSchema } from "./staffSchema";

const validStaff = {
  first_name: "هند",
  last_name: "عوض",
  country_code: "+963",
  phone_number: "981381990",
  gender: "female",
  role: "receptionist",
  employment_type: "fixed_salary",
  base_salary: 1000,
  work_status: "active",
  start_date: "",
  start_time: "",
  end_time: "",
  address: "",
  branch_ids: [1],
};

describe("staff form gender", () => {
  it("accepts a supported gender", () => {
    const result = staffFormSchema.safeParse(validStaff);

    expect(result.success).toBe(true);
    expect(result.data.gender).toBe("female");
  });

  it("requires gender instead of saving a null value", () => {
    const result = staffFormSchema.safeParse({ ...validStaff, gender: "" });

    expect(result.success).toBe(false);
    expect(result.error.issues).toEqual(
      expect.arrayContaining([
        expect.objectContaining({ path: ["gender"], message: "يرجى اختيار الجنس" }),
      ]),
    );
  });
});
