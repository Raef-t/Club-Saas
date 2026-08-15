import { describe, expect, it } from "vitest";
import {
  calculateCommissionAmount,
  createSuggestedSubscriptionPlanName,
  getCoachCommissionPercentage,
  isEquipmentActivity,
  isGeneralEquipmentActivity,
  isPrivateEquipmentActivity,
} from "./subscriptionPlanFormUtils";

const activities = [
  { id: 3, name: "أجهزة عام" },
  { id: 7, name: { ar: "زومبا", en: "Zumba" } },
];
const coaches = [{ id: 44, person: { full_name: "كابتن دانية" } }];

describe("subscription plan form utilities", () => {
  it("recognizes only the general equipment activity", () => {
    expect(isGeneralEquipmentActivity(activities[0])).toBe(true);
    expect(isGeneralEquipmentActivity({ name: "اجهزة عام داخل الصالة" })).toBe(true);
    expect(isGeneralEquipmentActivity({ name: "أجهزة خاص" })).toBe(false);
    expect(isGeneralEquipmentActivity({ name: "تدريب عام" })).toBe(false);
  });

  it("recognizes general and private equipment as activities without times", () => {
    expect(isEquipmentActivity({ name: "أجهزة عام" })).toBe(true);
    expect(isEquipmentActivity({ name: "اجهزة خاص" })).toBe(true);
    expect(isEquipmentActivity({ name: "تدريب عام" })).toBe(false);
    expect(isEquipmentActivity({ name: "زومبا" })).toBe(false);
  });

  it("recognizes only private equipment for the private-training commission", () => {
    expect(isPrivateEquipmentActivity({ name: "أجهزة خاص" })).toBe(true);
    expect(isPrivateEquipmentActivity({ name: "اجهزة خاص يومي" })).toBe(true);
    expect(isPrivateEquipmentActivity({ name: "أجهزة عام" })).toBe(false);
    expect(isPrivateEquipmentActivity({ name: "تدريب خاص" })).toBe(false);
  });

  it("calculates the coach commission as the remainder of 100 percent", () => {
    expect(getCoachCommissionPercentage("20")).toBe("80");
    expect(getCoachCommissionPercentage("33.33")).toBe("66.67");
    expect(getCoachCommissionPercentage(0)).toBe("100");
    expect(getCoachCommissionPercentage("")).toBe("");
  });

  it("calculates the club and coach amounts from the activity price", () => {
    expect(calculateCommissionAmount("300", "50")).toBe(150);
    expect(calculateCommissionAmount("350", "20")).toBe(70);
    expect(calculateCommissionAmount("350", "80")).toBe(280);
    expect(calculateCommissionAmount("", "50")).toBeNull();
    expect(calculateCommissionAmount("300", "110")).toBeNull();
  });

  it("suggests a plan name from the activity and coach names", () => {
    expect(
      createSuggestedSubscriptionPlanName(
        [{ activity_id: "7", coach_id: "44" }],
        activities,
        coaches,
      ),
    ).toBe("زومبا - كابتن دانية");
  });

  it("uses the activity name while the optional general-equipment coach is empty", () => {
    expect(
      createSuggestedSubscriptionPlanName(
        [{ activity_id: "3", coach_id: "" }],
        activities,
        coaches,
      ),
    ).toBe("أجهزة عام");
  });
});
