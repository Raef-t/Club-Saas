import { describe, expect, it } from "vitest";
import {
  formatSubscriptionMoney,
  getCurrentMemberSubscription,
  getLocalDateValue,
  getSubscriptionDetail,
  getSubscriptionRows,
  isDailyEntrySubscriptionPlan,
  parseSubscriptionAmount,
} from "./subscriptionUtils";

describe("subscription utilities", () => {
  it("normalizes invalid amounts", () => {
    expect(parseSubscriptionAmount("12.5")).toBe(12.5);
    expect(parseSubscriptionAmount("invalid")).toBe(0);
  });

  it("formats subscription money consistently", () => {
    expect(formatSubscriptionMoney("1200.5")).toBe("$1,200.5");
  });

  it("supports nested and direct collection responses", () => {
    const rows = [{ id: 1 }];

    expect(getSubscriptionRows({ data: { data: rows } })).toEqual(rows);
    expect(getSubscriptionRows({ data: rows })).toEqual(rows);
    expect(getSubscriptionRows(null)).toEqual([]);
  });

  it("extracts a subscription detail safely", () => {
    expect(getSubscriptionDetail({ data: { id: 1 } })).toEqual({ id: 1 });
    expect(getSubscriptionDetail(null)).toBeNull();
  });

  it("formats a local date for subscription fields", () => {
    expect(getLocalDateValue(new Date(2026, 7, 8, 12))).toBe("2026-08-08");
    expect(getLocalDateValue(new Date("invalid"))).toBe("");
  });

  it("detects daily-entry plans from API type fields", () => {
    expect(isDailyEntrySubscriptionPlan({ type: "daily_entry" })).toBe(true);
    expect(isDailyEntrySubscriptionPlan({ plan_type: "day-pass" })).toBe(true);
    expect(isDailyEntrySubscriptionPlan({ is_daily_entry: true })).toBe(true);
  });

  it("detects localized daily-entry plan names as a fallback", () => {
    expect(isDailyEntrySubscriptionPlan({ name: { ar: "دخولية أجهزة" } })).toBe(true);
    expect(isDailyEntrySubscriptionPlan({ name: { en: "Daily Entry" } })).toBe(true);
    expect(isDailyEntrySubscriptionPlan({ name: "اشتراك أجهزة شهري" })).toBe(false);
  });

  it("selects the active subscription belonging to the requested member", () => {
    const response = {
      data: [
        { id: 8, member_id: 2, status: "active" },
        { id: 7, member: { id: 5 }, status: "frozen" },
        { id: 6, member_id: 5, status: "active" },
      ],
    };

    expect(getCurrentMemberSubscription(response, 5)).toMatchObject({ id: 6 });
    expect(getCurrentMemberSubscription(response, 99)).toBeNull();
  });

  it("falls back to the newest subscription when member identifiers are omitted", () => {
    expect(
      getCurrentMemberSubscription(
        {
          data: [
            { id: 1, status: "expired", start_date: "2025-01-01" },
            { id: 2, status: "expired", start_date: "2026-01-01" },
          ],
        },
        5,
      ),
    ).toMatchObject({ id: 2 });
  });
});
