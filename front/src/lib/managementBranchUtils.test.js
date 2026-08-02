import { describe, expect, it } from "vitest";
import {
  ALL_BRANCHES_VALUE,
  createManagementBranchOptions,
  filterEntitiesByBranch,
  getEntityBranchIds,
  getGenderForBranchId,
  getPreferredBranchId,
  normalizeSelectedBranchId,
} from "./managementBranchUtils";

const branches = [
  { id: 1, name: { ar: "الرئيسي", en: "Main" } },
  { id: 2, name: "الشرقي" },
];

describe("management branch utilities", () => {
  it("extracts single and multiple branch relations", () => {
    expect(getEntityBranchIds({ branch_id: 1 })).toEqual(["1"]);
    expect(getEntityBranchIds({ member: { branch_id: 4 } })).toEqual(["4"]);
    expect(
      getEntityBranchIds({
        player: { member: { branch: { id: 5 } } },
      }),
    ).toEqual(["5"]);
    expect(
      getEntityBranchIds({
        branch_ids: [1, "2"],
        branches: [{ id: 2 }, { id: 3 }],
      }),
    ).toEqual(["1", "2", "3"]);
  });

  it("filters entities only when a specific branch is selected", () => {
    const items = [
      { id: 1, branch_id: 1 },
      { id: 2, branch_ids: [2] },
    ];

    expect(filterEntitiesByBranch(items, "2")).toEqual([items[1]]);
    expect(filterEntitiesByBranch(items, ALL_BRANCHES_VALUE)).toEqual(items);
  });

  it("prefers an edit value, then the selected branch, then the first branch", () => {
    expect(
      getPreferredBranchId({
        currentBranchId: 2,
        selectedBranchId: "1",
        branches,
      }),
    ).toBe("2");
    expect(
      getPreferredBranchId({
        selectedBranchId: "2",
        branches,
      }),
    ).toBe("2");
    expect(
      getPreferredBranchId({
        selectedBranchId: ALL_BRANCHES_VALUE,
        branches,
      }),
    ).toBe("1");
  });

  it("creates localized options and rejects stale persisted selections", () => {
    expect(createManagementBranchOptions(branches)).toEqual([
      { value: "all", label: "كل الفروع" },
      { value: "1", label: "الرئيسي" },
      { value: "2", label: "الشرقي" },
    ]);
    expect(normalizeSelectedBranchId("9", branches)).toBe("all");
  });

  it("enforces a branch gender restriction and preserves the fallback for mixed branches", () => {
    const genderedBranches = [
      { id: 1, gender_restriction: "mixed" },
      { id: 2, gender_restriction: "female" },
    ];

    expect(getGenderForBranchId(genderedBranches, "2", "male")).toBe("female");
    expect(getGenderForBranchId(genderedBranches, "1", "male")).toBe("male");
  });
});
