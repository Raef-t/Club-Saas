import { describe, expect, it } from "vitest";
import { offerSchema } from "./offersSchema";

describe("offerSchema validation", () => {
  it("validates a valid offer payload without dates", () => {
    const data = {
      branch_id: 1,
      name: "باقة الصيف الرياضية",
      description: "سباحة + لياقة",
      price: 1500,
      is_active: true,
      plans: [5, 8],
    };

    const result = offerSchema.safeParse(data);
    expect(result.success).toBe(true);
    if (result.success) {
      expect(result.data.name).toBe("باقة الصيف الرياضية");
      expect(result.data.price).toBe(1500);
      expect(result.data.plans).toEqual([5, 8]);
    }
  });

  it("requires at least one plan in the offer", () => {
    const data = {
      branch_id: "1",
      name: "باقة فارغة",
      price: "1000",
      plans: [],
    };

    const result = offerSchema.safeParse(data);
    expect(result.success).toBe(false);
    expect(result.error?.issues[0].message).toContain("يجب تحديد خطة");
  });

  it("requires positive price and valid branch", () => {
    const data = {
      branch_id: 0,
      name: "باقة",
      price: -10,
      plans: [1],
    };

    const result = offerSchema.safeParse(data);
    expect(result.success).toBe(false);
  });

  it("validates single_choice offer type correctly", () => {
    const data = {
      branch_id: 2,
      name: "عرض حصص الأيروبيك",
      offer_type: "single_choice",
      price: 200,
      plans: [10, 11, 12],
    };

    const result = offerSchema.safeParse(data);
    expect(result.success).toBe(true);
    if (result.success) {
      expect(result.data.offer_type).toBe("single_choice");
      expect(result.data.price).toBe(200);
      expect(result.data.plans).toHaveLength(3);
    }
  });
});
