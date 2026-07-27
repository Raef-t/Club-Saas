import { describe, expect, it } from "vitest";
import {
  createBranchFormValues,
  createBranchPayload,
  createBranchStats,
  filterBranches,
  getBranchCollection,
  getBranchRecord,
} from "./branchUtils";

const branches = [
  {
    id: 1,
    name: { ar: "الرئيسي", en: "Main" },
    address: "حلب",
    gender_restriction: "mixed",
    is_active: true,
  },
  {
    id: 2,
    name: "Ladies",
    phone: "123",
    gender_restriction: "female",
    is_active: false,
  },
];

describe("branch utilities", () => {
  it("normalizes collection and record response shapes", () => {
    expect(getBranchCollection({ data: { data: branches } })).toEqual(branches);
    expect(getBranchRecord({ data: branches[0] })).toEqual(branches[0]);
  });

  it("filters by gender and searchable fields", () => {
    expect(filterBranches(branches, { search: "", gender: "female" })).toEqual([branches[1]]);
    expect(filterBranches(branches, { search: "حلب", gender: "all" })).toEqual([branches[0]]);
  });

  it("creates branch statistics", () => {
    expect(createBranchStats(branches).map((item) => Number(item.value))).toEqual([2, 1, 0, 1]);
  });

  it("creates editor values and the backend payload", () => {
    const form = createBranchFormValues(
      {
        ...branches[0],
        club: { id: 7 },
        country_code: "+963",
        phone: "555",
      },
      1,
    );

    expect(form).toMatchObject({
      club_id: "7",
      name_ar: "الرئيسي",
    });
    expect(createBranchPayload({ ...form, address: " " })).toMatchObject({
      club_id: 7,
      name: "الرئيسي",
      address: null,
    });
  });
});
