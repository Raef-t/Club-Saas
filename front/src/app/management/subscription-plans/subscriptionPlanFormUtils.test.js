import { describe, expect, it } from "vitest";
import {
  calculateCommissionAmount,
  calculatePrivatePlanBasePrice,
  createSuggestedSubscriptionPlanName,
  getSubscriptionPlanCoachCommission,
  isEquipmentActivity,
  isGeneralEquipmentActivity,
  isPrivateEquipmentActivity,
  isPrivateSubscriptionActivity,
} from "./subscriptionPlanFormUtils";

const generalTrainingType = {
  is_private_equipment: false,
  is_session_based: false,
  is_daily_entry: false,
  has_unlimited_subscribers: true,
};
const privateTrainingType = { ...generalTrainingType, is_private_equipment: true };
const groupClassType = {
  is_private_equipment: false,
  is_session_based: true,
  is_daily_entry: false,
  has_unlimited_subscribers: false,
};
const activities = [
  { id: 3, name: "أجهزة عام", activity_type: generalTrainingType },
  { id: 7, name: { ar: "زومبا", en: "Zumba" }, activity_type: groupClassType },
];
const coaches = [{ id: 44, person: { full_name: "كابتن دانية" } }];

describe("subscription plan form utilities", () => {
  it("recognizes only the general equipment activity", () => {
    expect(isGeneralEquipmentActivity(activities[0])).toBe(true);
    expect(
      isGeneralEquipmentActivity({ name: "اسم مخصص", activity_type: generalTrainingType }),
    ).toBe(true);
    expect(
      isGeneralEquipmentActivity({ name: "أجهزة عام", activity_type: privateTrainingType }),
    ).toBe(false);
    expect(isGeneralEquipmentActivity(activities[1])).toBe(false);
  });

  it("recognizes general and private equipment as activities without times", () => {
    expect(isEquipmentActivity({ activity_type: generalTrainingType })).toBe(true);
    expect(isEquipmentActivity({ activity_type: privateTrainingType })).toBe(true);
    expect(isEquipmentActivity({ activity_type: groupClassType })).toBe(false);
    expect(
      isEquipmentActivity({
        activity_type: { ...generalTrainingType, is_daily_entry: true },
      }),
    ).toBe(false);
  });

  it("recognizes private equipment and calculates amounts from the private-training commission", () => {
    expect(isPrivateEquipmentActivity({ activity_type: privateTrainingType })).toBe(true);
    expect(isPrivateEquipmentActivity({ activity_type: generalTrainingType })).toBe(false);

    const coachPercentage = getSubscriptionPlanCoachCommission({
      details: {
        default_commission_rate: "0.00",
        private_commission_rate: "100.00",
      },
    });

    expect(coachPercentage).toBe(100);
    expect(calculateCommissionAmount("300", coachPercentage)).toBe(300);
    expect(calculateCommissionAmount("300", 100 - coachPercentage)).toBe(0);
  });

  it("recognizes private plans and adds their coach and branch prices", () => {
    expect(
      isPrivateSubscriptionActivity({
        name: "أي اسم للنشاط",
        activity_type: privateTrainingType,
      }),
    ).toBe(true);
    expect(
      isPrivateSubscriptionActivity({
        name: "أجهزة خاص",
        activity_type: generalTrainingType,
      }),
    ).toBe(false);
    expect(calculatePrivatePlanBasePrice("200", "150")).toBe(350);
  });

  it("falls back to the general commission for legacy coach records", () => {
    expect(
      getSubscriptionPlanCoachCommission({
        details: { default_commission_rate: "50.00" },
      }),
    ).toBe(50);
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
