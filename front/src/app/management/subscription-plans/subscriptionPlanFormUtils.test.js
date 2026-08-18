import { describe, expect, it } from "vitest";
import {
  calculateCommissionAmount,
  createSuggestedSubscriptionPlanName,
  getSubscriptionPlanCoachCommission,
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

  it("recognizes private equipment and calculates amounts from the coach commission", () => {
    expect(isPrivateEquipmentActivity({ name: "أجهزة خاص" })).toBe(true);
    expect(isPrivateEquipmentActivity({ name: "أجهزة عام" })).toBe(false);

    const coachPercentage = getSubscriptionPlanCoachCommission({
      details: { default_commission_rate: "50.00" },
    });

    expect(coachPercentage).toBe(50);
    expect(calculateCommissionAmount("300", coachPercentage)).toBe(150);
    expect(calculateCommissionAmount("300", 100 - coachPercentage)).toBe(150);
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
