import { describe, expect, it } from "vitest";
import {
  createCoachSubscriptionsReportParams,
  normalizeCoachSubscriptionsReportResponse,
} from "./coachSubscriptionsReportUtils";

describe("coachSubscriptionsReportUtils", () => {
  it("builds the documented branch query", () => {
    expect(createCoachSubscriptionsReportParams(5)).toEqual({ branch_id: "5" });
  });

  it("omits the branch when all branches are selected", () => {
    expect(createCoachSubscriptionsReportParams("all")).toEqual({});
    expect(createCoachSubscriptionsReportParams()).toEqual({});
  });

  it("normalizes the documented report response", () => {
    const report = normalizeCoachSubscriptionsReportResponse({
      status: "success",
      data: {
        summary: {
          total_group_coaches: "7",
          total_group_active_players: "7",
          general_equipment_active_players: "51",
        },
        group_session_coaches: [
          {
            coach_id: 43,
            coach_name: "هبة سعيد",
            activities: ["ايروبيك", "كروسفيت"],
            active_players_count: "2",
          },
        ],
        general_equipment: {
          title: "أجهزة عام",
          activity_type_name: "تدريب عام",
          active_players_count: "51",
        },
      },
    });

    expect(report.summary).toEqual({
      total_group_coaches: 7,
      total_group_active_players: 7,
      general_equipment_active_players: 51,
    });
    expect(report.groupSessionCoaches[0]).toMatchObject({
      id: "coach-43",
      coachId: 43,
      coachName: "هبة سعيد",
      activities: ["ايروبيك", "كروسفيت"],
      activitiesLabel: "ايروبيك، كروسفيت",
      activitiesCount: 2,
      activePlayersCount: 2,
    });
    expect(report.generalEquipment).toEqual({
      title: "أجهزة عام",
      activityTypeName: "تدريب عام",
      activePlayersCount: 51,
    });
  });

  it("returns safe defaults for missing data", () => {
    expect(normalizeCoachSubscriptionsReportResponse()).toEqual({
      summary: {
        total_group_coaches: 0,
        total_group_active_players: 0,
        general_equipment_active_players: 0,
      },
      groupSessionCoaches: [],
      generalEquipment: {
        title: "أجهزة عامة",
        activityTypeName: "تدريب عام",
        activePlayersCount: 0,
      },
    });
  });
});
