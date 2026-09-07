import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { CoachCreateForm } from "./CoachForm";

afterEach(cleanup);

const { settingsQuery } = vi.hoisted(() => ({
  settingsQuery: {
    currentData: {
      data: {
        private_subscription_commission: "15.5",
        default_coach_commission_percentage: "35",
        default_employee_salary: "10000",
      },
    },
    isFetching: false,
    error: null,
  },
}));

vi.mock("@/lib/api/branchesApi", () => {
  const shiftsQuery = { currentData: { data: [] }, isFetching: false };

  return {
    useGetBranchSettingsQuery: () => settingsQuery,
    useGetBranchShiftsQuery: () => shiftsQuery,
  };
});

beforeEach(() => {
  settingsQuery.currentData = {
    data: {
      private_subscription_commission: "15.5",
      default_coach_commission_percentage: "35",
      default_employee_salary: "10000",
    },
  };
});

vi.mock("@/lib/ManagementBranchContext", () => ({
  useManagementBranch: () => ({ selectedBranchId: "5" }),
}));

vi.mock("@/lib/TimeFormatContext", () => ({
  useTimeFormat: () => ({ formatTime: (value) => value }),
}));

describe("coach private-training commissions", () => {
  it("replaces a cached club share when the latest branch settings arrive", async () => {
    settingsQuery.currentData = {
      data: {
        private_subscription_commission: "0",
        default_coach_commission_percentage: "35",
        default_employee_salary: "10000",
      },
    };
    const props = {
      formId: "coach-form",
      branches: [{ id: 5, name: "الفرع الرئيسي" }],
      activities: [{ id: 8, name: "أجهزة خاص" }],
      onSubmit: vi.fn(),
      onCancel: vi.fn(),
    };
    const { getByRole, rerender } = render(<CoachCreateForm {...props} />);

    fireEvent.click(getByRole("button", { name: /اختر نشاطاً لإضافته/ }));
    fireEvent.click(await screen.findByRole("option", { name: "أجهزة خاص" }));

    const clubInput = await screen.findByLabelText(/نسبة النادي من التدريب الخاص/);
    const coachInput = screen.getByLabelText(/نسبة المدرب من التدريب الخاص/);
    await waitFor(() => expect(clubInput).toHaveValue(0));

    settingsQuery.currentData = {
      data: {
        private_subscription_commission: "25",
        default_coach_commission_percentage: "35",
        default_employee_salary: "10000",
      },
    };
    rerender(<CoachCreateForm {...props} />);

    await waitFor(() => {
      expect(clubInput).toHaveValue(25);
      expect(coachInput).toHaveValue(75);
    });
  });

  it("loads the club share from branch settings and calculates the coach share", async () => {
    render(
      <CoachCreateForm
        formId="coach-form"
        branches={[{ id: 5, name: "الفرع الرئيسي" }]}
        activities={[{ id: 8, name: "أجهزة خاص" }]}
        initialValues={{
          first_name: "أحمد",
          last_name: "محمد",
          branch_ids: [5],
          activity_ids: [8],
          private_club_commission_rate: "",
          private_commission_rate: "0",
          default_commission_rate: "0",
        }}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    const clubInput = await screen.findByLabelText(/نسبة النادي من التدريب الخاص/);
    const coachInput = screen.getByLabelText(/نسبة المدرب من التدريب الخاص/);

    await waitFor(() => {
      expect(clubInput).toHaveValue(15.5);
      expect(coachInput).toHaveValue(84.5);
    });

    fireEvent.change(clubInput, { target: { value: "20" } });
    expect(clubInput).toHaveValue(20);
    expect(coachInput).toHaveValue(80);
    expect(coachInput).toBeDisabled();
  });

  it("shows and submits separate activity and private-training coach shares", async () => {
    const onSubmit = vi.fn();
    const { container, getByRole } = render(
      <CoachCreateForm
        formId="coach-form"
        branches={[{ id: 5, name: "الفرع الرئيسي" }]}
        activities={[
          {
            id: 8,
            name: "أجهزة خاص",
            activity_type: { code: "private_training" },
          },
          {
            id: 9,
            name: "زومبا",
            activity_type: { code: "group_class" },
          },
        ]}
        onSubmit={onSubmit}
        onCancel={vi.fn()}
      />,
    );

    fireEvent.change(screen.getByLabelText(/الاسم الأول/), { target: { value: "أحمد" } });
    fireEvent.change(screen.getByLabelText(/اسم العائلة/), { target: { value: "محمد" } });

    fireEvent.click(getByRole("button", { name: /اختر نشاطاً لإضافته/ }));
    fireEvent.click(await screen.findByRole("option", { name: "أجهزة خاص" }));
    fireEvent.click(getByRole("button", { name: /اختر نشاطاً لإضافته/ }));
    fireEvent.click(await screen.findByRole("option", { name: "زومبا" }));

    const activityCoachInput = await screen.findByLabelText(/نسبة المدرب من الفعالية/);
    const privateClubInput = screen.getByLabelText(/نسبة النادي من التدريب الخاص/);
    const privateCoachInput = screen.getByLabelText(/نسبة المدرب من التدريب الخاص/);

    fireEvent.change(activityCoachInput, { target: { value: "15.5" } });
    fireEvent.change(privateClubInput, { target: { value: "30" } });

    expect(activityCoachInput).toHaveValue(15.5);
    expect(privateCoachInput).toHaveValue(70);

    fireEvent.submit(container.querySelector("form"));

    await waitFor(() => expect(onSubmit).toHaveBeenCalledOnce());
    expect(onSubmit.mock.calls[0][0]).toMatchObject({
      default_commission_rate: 15.5,
      private_commission_rate: 70,
    });
  });

  it("shows both saved commission rates while editing when activity metadata is unavailable", () => {
    render(
      <CoachCreateForm
        formId="coach-form"
        branches={[{ id: 5, name: "الفرع الرئيسي" }]}
        activities={[]}
        initialValues={{
          first_name: "أحمد",
          last_name: "محمد",
          branch_ids: [5],
          employment_type: "commission_based",
          work_types: ["equipment", "activities"],
          default_commission_rate: "15.5",
          private_commission_rate: "70",
          private_club_commission_rate: "30",
        }}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    expect(screen.getByLabelText(/نسبة المدرب من الفعالية/)).toHaveValue(15.5);
    expect(screen.getByLabelText(/نسبة المدرب من التدريب الخاص/)).toHaveValue(70);
  });

  it("restores the base salary after general equipment is removed and selected again", async () => {
    const { getByRole } = render(
      <CoachCreateForm
        formId="coach-form"
        branches={[{ id: 5, name: "الفرع الرئيسي" }]}
        activities={[
          {
            id: 7,
            name: "أجهزة عام",
            activity_type: { code: "general_training" },
          },
          {
            id: 8,
            name: "أجهزة خاص",
            activity_type: { code: "private_training" },
          },
        ]}
        initialValues={{
          first_name: "أحمد",
          last_name: "محمد",
          branch_ids: [5],
          activity_ids: [7, 8],
          employment_type: "hybrid",
          work_types: ["equipment"],
          base_salary: "10000",
          private_commission_rate: "70",
          private_club_commission_rate: "30",
        }}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    expect(screen.getByLabelText(/الراتب الأساسي/)).toHaveValue(10000);

    fireEvent.click(screen.getByText("أجهزة عام").closest("div").querySelector("button"));
    await waitFor(() => expect(screen.queryByLabelText(/الراتب الأساسي/)).not.toBeInTheDocument());

    fireEvent.click(getByRole("button", { name: /اختر نشاطاً لإضافته/ }));
    fireEvent.click(await screen.findByRole("option", { name: "أجهزة عام" }));

    await waitFor(() => expect(screen.getByLabelText(/الراتب الأساسي/)).toHaveValue(10000));
  });

  it("preserves both commission splits when an unrelated activity is removed", async () => {
    render(
      <CoachCreateForm
        formId="coach-form"
        branches={[{ id: 5, name: "الفرع الرئيسي" }]}
        activities={[
          {
            id: 7,
            name: "أجهزة عام",
            activity_type: { code: "general_training" },
          },
          {
            id: 8,
            name: "أجهزة خاص",
            activity_type: { code: "private_training" },
          },
          {
            id: 9,
            name: "زومبا",
            activity_type: { code: "group_class" },
          },
        ]}
        initialValues={{
          first_name: "أحمد",
          last_name: "محمد",
          branch_ids: [5],
          activity_ids: [7, 8, 9],
          employment_type: "hybrid",
          work_types: ["equipment", "activities"],
          base_salary: "10000",
          default_commission_rate: "40",
          private_commission_rate: "100",
          private_club_commission_rate: "0",
        }}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    expect(screen.getByLabelText(/نسبة النادي من التدريب الخاص/)).toHaveValue(0);
    expect(screen.getByLabelText(/نسبة المدرب من التدريب الخاص/)).toHaveValue(100);
    expect(screen.getByLabelText(/نسبة النادي من الفعالية/)).toHaveValue(60);
    expect(screen.getByLabelText(/نسبة المدرب من الفعالية/)).toHaveValue(40);

    fireEvent.click(screen.getByText("أجهزة عام").closest("div").querySelector("button"));

    await waitFor(() => {
      expect(screen.queryByText("أجهزة عام")).not.toBeInTheDocument();
      expect(screen.getByLabelText(/نسبة النادي من التدريب الخاص/)).toHaveValue(0);
      expect(screen.getByLabelText(/نسبة المدرب من التدريب الخاص/)).toHaveValue(100);
      expect(screen.getByLabelText(/نسبة النادي من الفعالية/)).toHaveValue(60);
      expect(screen.getByLabelText(/نسبة المدرب من الفعالية/)).toHaveValue(40);
    });
  });

  it("shows the highlighted commission card for a percentage-based activity", async () => {
    const { getByRole } = render(
      <CoachCreateForm
        formId="coach-form"
        branches={[{ id: 5, name: "الفرع الرئيسي" }]}
        activities={[
          {
            id: 9,
            name: "زومبا",
            activity_type: { name: { ar: "حصة جماعية" } },
          },
        ]}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    fireEvent.click(getByRole("button", { name: /اختر نشاطاً لإضافته/ }));
    fireEvent.click(await screen.findByRole("option", { name: "زومبا" }));

    const clubInput = await screen.findByLabelText(/نسبة النادي من الفعالية/);
    const coachInput = screen.getByLabelText(/نسبة المدرب من الفعالية/);

    expect(clubInput).toHaveValue(65);
    expect(clubInput).toBeDisabled();
    expect(coachInput).toHaveValue(35);

    fireEvent.change(coachInput, { target: { value: "40" } });
    expect(coachInput).toHaveValue(40);
    await waitFor(() => expect(clubInput).toHaveValue(60));
  });
});
